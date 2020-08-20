<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use LogicException;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverUdpTransport;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Factory\DnsCrypt\DnsCryptCertificateFactory;
use Socket\Raw\Factory;

use const SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES;
use const SODIUM_CRYPTO_BOX_NONCEBYTES;

class DnsCryptClient implements DnsClientInterface
{
    public const PADDING_START = 0x80;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;
    /**
     * @var Factory
     */
    private $socketFactory;

    public function __construct(MessageFactoryInterface $messageFactory, Factory $socketFactory)
    {
        $this->messageFactory = $messageFactory;
        $this->socketFactory = $socketFactory;
    }

    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        if (!$dnsUpstream instanceof DnsCryptUpstream) {
            throw new InvalidArgumentException('Upstream must be an instance of ' . DnsCryptUpstream::class);
        }

        /**
         * Step 1.
         * The client begins a DNSCrypt session by sending a regular unencrypted
         * TXT DNS query to the resolver IP address, on the DNSCrypt port, first
         * over UDP, then, in case of failure, timeout or truncation, over TCP.
         *
         * This DNS query encodes the certificate versions supported by the
         * client, as well as a public identifier of the provider requested by
         * the client.
         */
        $dnsResponseCertificatesMessage = $this->getCertificates($dnsUpstream);


        /**
         * The resolver responds with a public set of signed certificates, that
         * must be verified by the client using a previously distributed public
         * key, known as the provider public key.
         *
         * A successful response to certificate request contains one or more TXT
         * records, each record containing a certificate encoded as follows:
         *
         * <cert> ::= <cert-magic> <es-version> <protocol-minor-version> <signature>
         * <resolver-pk> <client-magic> <serial> <ts-start> <ts-end>
         * <extensions>
         *
         * Certificates made of these information, without extensions, are 116 bytes
         * long. With the addition of the cert-magic, es-version and
         * protocol-minor-version, the record is 124 bytes long.
         */
        $dnsCertificateFactory = new DnsCryptCertificateFactory();
        /** @var CertificateInterface[] $certificates */
        $certificates = [];
        foreach ($dnsResponseCertificatesMessage->getAnswer() as $record) {
            $certificates[] = $dnsCertificateFactory->createFromResourceRecord(
                $record,
                $dnsUpstream->getProviderPublicKey()
            );
        }

        /**
         * Each certificate includes a validity period, a serial number, a
         * version that defines a key exchange mechanism, an authenticated
         * encryption algorithm and its parameters, as well as a short-term
         * public key, known as the resolver public key.
         *
         * After having received a set of certificates, the client checks their
         * validity based on the current date, filters out the ones designed for
         * encryption systems that are not supported by the client, and chooses
         * the certificate with the higher serial number.
         *
         * The client picks the one with the
         * highest serial number among the currently valid ones that match a
         * supported protocol version.
         */
        $certificates = $this->filterCertificates($certificates);

        // TODO: Check supported encryptions
        // TODO: Pick the certificate with the higher serial number
        shuffle($certificates);
        $certificate = array_shift($certificates);

        $clientDnsWireQuery = $this->messageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);


        /**
         * DNSCrypt queries sent by the client must use the <client-magic>
         * header of the chosen certificate, as well as the specified encryption
         * system and public key.
         *
         * Note: sodium_crypto_box uses X25519 + Xsalsa20 + Poly1305
         */
        switch ($certificate->getEsVersion()) {
            case CertificateInterface::ES_VERSION_XSALSA20POLY1305:
                $clientKeyPair = sodium_crypto_box_keypair();
                $clientPublicKey = sodium_crypto_box_publickey($clientKeyPair);
                $clientSecretKey = sodium_crypto_box_secretkey($clientKeyPair);

                /**
                 * When using X25519-XSalsa20Poly1305, this construction requires a 24 bytes
                 * nonce, that must not be reused for a given shared secret.
                 */
                $sharedKey = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                    $clientSecretKey,
                    $certificate->getResolverPublicKey()
                );

                /**
                 * With a 24 bytes nonce, a question sent by a DNSCrypt client must be
                 * encrypted using the shared secret, and a nonce constructed as follows:
                 * 12 bytes chosen by the client followed by 12 NUL (0) bytes.
                 */
                [$clientNonce, $clientNonceWithPad] = $this->createClientNonce(SODIUM_CRYPTO_BOX_NONCEBYTES);

                $encryptedQuery = $this->encryptWithXsalsa20(
                    $this->getClientQueryWithPadding($clientDnsWireQuery),
                    // <client-query> <client-query-pad> must be at least <min-query-len>
                    $clientNonceWithPad,
                    $sharedKey
                );
                $dnsCryptQuery = new DnsCryptQuery(
                    $certificate->getClientMagic(),
                    $clientPublicKey,
                    $clientNonce,
                    $encryptedQuery
                );

                break;
            case CertificateInterface::ES_VERSION_XCHACHA20POLY1305:

                $key = sodium_crypto_aead_chacha20poly1305_ietf_keygen();

                /**
                 * With a 24 bytes nonce, a question sent by a DNSCrypt client must be
                 * encrypted using the shared secret, and a nonce constructed as follows:
                 * 12 bytes chosen by the client followed by 12 NUL (0) bytes.
                 */
                [$clientNonce, $clientNonceWithPad] = $this->createClientNonce(
                    SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES
                );

                $encryptedQuery = $this->encryptWithXchacha20(
                    $this->getClientQueryWithPadding($clientDnsWireQuery),
                    // <client-query> <client-query-pad> must be at least <min-query-len>
                    $clientNonceWithPad,
                    $key
                );
                $dnsCryptQuery = new DnsCryptQuery(
                    $certificate->getClientMagic(),
                    $key,
                    $clientNonce,
                    $encryptedQuery
                );

                break;
            default:
                throw new Exception('Not supported');
        }


        //                $response = $this->useTcp($dnsUpstream, $dnsCryptQuery);
        $response = $this->useUdp($dnsUpstream, $dnsCryptQuery);
        // TODO: check if TC flag

        // DNScrypt response is formatted as follow: <dnscrypt-response> ::= <resolver-magic> <nonce> <encrypted-response>
        //<resolver-magic> ::= 0x72 0x36 0x66 0x6e 0x76 0x57 0x6a 0x38
        //
        //<nonce> ::= <client-nonce> <resolver-nonce>
        //<client-nonce> ::= the nonce sent by the client in the related query.
        //
        //<client-pk> ::= the client's public key.
        //
        //<resolver-sk> ::= the resolver's public key.
        //
        //<resolver-nonce> ::= a unique response identifier for a given
        //(<client-pk>, <resolver-sk>) tuple. The length of <resolver-nonce>
        //depends on the chosen encryption algorithm.

        switch ($certificate->getEsVersion()) {
            case CertificateInterface::ES_VERSION_XSALSA20POLY1305:
                $respNonceLength = 24;
                $nonce = substr($response, 8, $respNonceLength);

                $ecQuery = substr($response, 8 + $respNonceLength);

                $decryptedMessage = sodium_crypto_box_open($ecQuery, $nonce, $sharedKey);

                break;
            case CertificateInterface::ES_VERSION_XCHACHA20POLY1305:

                break;
            default:
                throw new Exception('Not supported');
        }

        $message = substr($decryptedMessage, 0, strrpos($decryptedMessage, 0x80));

        $dnsMessage = $this->messageFactory->createMessageFromDnsWireMessage($message);
        die(var_dump((string)$decryptedMessage));


    }

    private function encryptWithXsalsa20(string $message, string $nonce, string $keypair)
    {
        return sodium_crypto_box($message, $nonce, $keypair);
    }

    private function encryptWithXchacha20(string $message, string $nonce, string $key)
    {
        return sodium_crypto_aead_chacha20poly1305_ietf_encrypt($message, $nonce, $nonce, $key);
    }

    /**
     * <client-nonce> length is half the nonce length
     * required by the encryption algorithm. In client queries, the other half,
     * <client-nonce-pad> is filled with NUL bytes.
     * @return array
     * @throws Exception
     */
    private function createClientNonce(int $nonceLength)
    {
        $clientNonce = random_bytes($nonceLength / 2);
        $clientNonceWithPad = $clientNonce . str_repeat(
                "\0",
                $nonceLength / 2
            ); // half the required nonce length  + 12 null bytes

        return [$clientNonce, $clientNonceWithPad];
    }

    private function useUdp(DnsCryptUpstream $dnsUpstream, DnsCryptQuery $dnsCryptQuery)
    {
        $dnsWireMessage = (string)$dnsCryptQuery;
        ["host" => $host, "port" => $port] = parse_url($dnsUpstream->getUri());
        $length = strlen($dnsWireMessage);
        $socket = @stream_socket_client('udp://' . $host . ':' . $port, $errno, $errstr, 4);
        stream_set_blocking($socket, true);
        if ($socket === false) {
            throw new Exception('Unable to connect to DNS server: <' . $errno . '> ' . $errstr);
        }

        // Must use DNS over TCP if message is bigger
        if ($length > StdClient::EDNS_SIZE) {
            throw new Exception(
                sprintf(
                    'DNS message is `%s` bytes, maximum `%s` bytes allowed. Use TCP transport instead',
                    $length,
                    StdClient::EDNS_SIZE
                )
            );
        }

        if (!@fputs($socket, $dnsWireMessage)) {
            throw new Exception('Unable to write to DNS server: <' . $errno . '> ' . $errstr);
        }
        $dnsWireResponseMessage = fread($socket, StdClient::EDNS_SIZE);
        if ($dnsWireResponseMessage === false) {
            throw new Exception('Unable to read from DNS server: Error <' . $errno . '> ' . $errstr);
        }
        fclose($socket);

        return $dnsWireResponseMessage;
    }

    private function useTcp(DnsCryptUpstream $dnsUpstream, DnsCryptQuery $dnsCryptQuery)
    {
        ["host" => $host, "port" => $port] = parse_url($dnsUpstream->getUri());

        $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 4);

        if ($socket === false) {
            throw new Exception('Unable to connect to DNS server: <' . $errno . '> ' . $errstr);
        }

        stream_set_blocking($socket, false);
        if (!@fputs($socket, (string)$dnsCryptQuery)) {
            throw new Exception('Unable to write to DNS server: <' . $errno . '> ' . $errstr);
        }
        $response = '';
        while (!feof($socket)) {
            $chunk = fread($socket, StdClient::EDNS_SIZE);
            if ($chunk === false) {
                throw new Exception('DNS message transfer from DNS server failed');
            }

            $response .= $chunk;
        }
        fclose($socket);

        return $response;
    }

    /**
     * Prior to encryption, queries are padded using the ISO/IEC 7816-4
     * format.
     *
     * The padding starts with a byte valued 0x80 followed by a
     * variable number of NUL bytes.
     *
     * <client-query> <client-query-pad> must be at least <min-query-len>
     * <min-query-len> is a variable length, initially set to 256 bytes, and
     * must be a multiple of 64 bytes.
     *
     * @param string $clientQuery
     *
     * @return string
     */
    private function getClientQueryWithPadding(string $clientQuery)
    {
        // Check if query greater than min query length
        $queryLength = strlen($clientQuery);
        $paddingLength = 256;

        if ($queryLength > $paddingLength) {
            $paddingLength = $queryLength + (64 - ($queryLength % 64));
        }

        return sodium_pad($clientQuery . static::PADDING_START, $paddingLength);
    }

    /**
     * @param CertificateInterface[] $certificates
     */
    private function filterCertificates(array $certificates)
    {
        $currentDate = new DateTimeImmutable();
        return array_filter(
            $certificates,
            function (CertificateInterface $certificate) use ($currentDate) {
                $dateStart = (new DateTimeImmutable())->setTimestamp($certificate->getTsStart());
                $dateEnd = (new DateTimeImmutable())->setTimestamp($certificate->getTsEnd());

                if ($dateStart > $currentDate) {
                    throw new LogicException('Not valid start date');
                }

                if ($dateEnd < $currentDate) {
                    return false;
                }

                return true;
            }
        );
    }

    private function getCertificates(DnsCryptUpstream $dnsCryptUpstream): MessageInterface
    {
        $stdClient = new StdClient(new MessageFactory(), new DnsOverTcpTransport(), new DnsOverUdpTransport());

        $dnsQuery = new Message(
            new Header(0, false, 0, false, false, false, false, 0, 0),
            new Message\Section\QuestionSection(
                [
                    new Query(
                        $dnsCryptUpstream->getProviderName(),
                        ResourceRecordInterface::TYPE_TXT,
                        ResourceRecordInterface::CLASS_IN
                    ),
                ]
            )
        );

        return $stdClient->resolve($dnsCryptUpstream, $dnsQuery);
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'sdns') !== false;
    }
}
