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

use const SODIUM_CRYPTO_BOX_NONCEBYTES;

class DnsCryptClient implements DnsClientInterface
{
    const PADDING_START = 0x80;

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
         */
        $dnsResponseCertificatesMessage = $this->getCertificates($dnsUpstream);


        /**
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
         * After having received a set of certificates, the client checks their
         * validity based on the current date, filters out the ones designed for
         * encryption systems that are not supported by the client, and chooses
         * the certificate with the higher serial number
         */
        $certificates = $this->filterCertificates($certificates);

        // TODO: Check supported encryptions
        // TODO: Pick the certificate with the higher serial number
        shuffle($certificates);
        $certificate = array_shift($certificates);

        $clientDnsWireQuery = $this->messageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);

        $clientKeyPair = sodium_crypto_box_keypair();
        $clientPublicKey = sodium_crypto_box_publickey($clientKeyPair);
        $clientSecretKey = sodium_crypto_box_secretkey($clientKeyPair);

        /**
         * When using X25519-XSalsa20Poly1305, this construction requires a 24 bytes
         * nonce, that must not be reused for a given shared secret.
         */

        /**
         * With a 24 bytes nonce, a question sent by a DNSCrypt client must be
         * encrypted using the shared secret, and a nonce constructed as follows:
         * 12 bytes chosen by the client followed by 12 NUL (0) bytes.
         */
        [$clientNonce, $clientNonceWithPad] = $this->getClientNonce();
//        $clientNonceWithPad = sodium_pad($clientNonce, 24);

        $sharedKey = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $clientSecretKey,
            $certificate->getResolverPublicKey()
        );


        /**
         * DNSCrypt queries sent by the client must use the <client-magic>
         * header of the chosen certificate, as well as the specified encryption
         * system and public key.
         */
        $encryptedQuery = sodium_crypto_box(
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

        //                $response = $this->useTcp($dnsUpstream, $dnsCryptQuery);
        $response = $this->useUdp($dnsUpstream, $dnsCryptQuery);
        // TODO: check if TC flag

        // generate short term key
        die(var_dump((string)$response));
        // 1 : DNS Query non-authenticated -> dnscryptenabled resolver
        // query contains encoded certificates versions supported by the client (us) + public identifier of the provider requested

        // 2. Resolver will send us back  a public set of signed certificates that we must verify with previously provider public key


        // convert dns query to dns crypt message

        // TODO: Implement resolve() method.
    }

    /**
     * <client-nonce> length is half the nonce length
     * required by the encryption algorithm. In client queries, the other half,
     * <client-nonce-pad> is filled with NUL bytes.
     * @return array
     * @throws Exception
     */
    private function getClientNonce()
    {
        $clientNonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES / 2);
        $clientNonceWithPad = $clientNonce . str_repeat(
                "\0",
                SODIUM_CRYPTO_BOX_NONCEBYTES / 2
            ); // half the required nonce length  + 12 null bytes

        return [$clientNonce, $clientNonceWithPad];
    }

    private function useUdp(DnsCryptUpstream $dnsUpstream, DnsCryptQuery $dnsCryptQuery)
    {
        $dnsWireMessage = (string)$dnsCryptQuery;
        ["host" => $host, "port" => $port] = parse_url($dnsUpstream->getUri());
        $length = strlen($dnsWireMessage);
        $socket = @stream_socket_client('udp://' . $host . ':' . $port, $errno, $errstr, 4);

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
     * format. The padding starts with a byte valued 0x80 followed by a
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
        return sodium_pad(0x80 . $clientQuery, 256);
        // padding must start with 0x80
        // min query length is 256 bytes.
        // must be a multiple of 64 bytes.
        // filled with client-query-pad
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
