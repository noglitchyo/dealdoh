<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use DateTimeImmutable;
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
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Factory\DnsCrypt\DnsCryptCertificateFactory;
use NoGlitchYo\Dealdoh\Service\DnsCryptServiceInterface;

class DnsCryptClient implements DnsClientInterface
{
    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var DnsCryptServiceInterface
     */
    private $dnsCryptService;

    public function __construct(
        MessageFactoryInterface $messageFactory,
        DnsCryptServiceInterface $dnsCryptQueryFactory
    ) {
        $this->messageFactory = $messageFactory;
        $this->dnsCryptService = $dnsCryptQueryFactory;
    }

    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        if (!$dnsUpstream instanceof DnsCryptUpstream) {
            throw new InvalidArgumentException('Upstream must be an instance of ' . DnsCryptUpstream::class);
        }

        $certificate = $this->pickCertificate($dnsUpstream);
        $clientDnsWireQuery = $this->messageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);

        $es = $this->dnsCryptService->getEncryptionSystem($certificate);

        $dnsQuery = (string)$es->encrypt($clientDnsWireQuery);

        $udpTransport = new DnsOverUdpTransport();
        $response = $udpTransport->send($dnsUpstream->getUri(), $dnsQuery);

        $dnsMessage = $this->messageFactory->createMessageFromDnsWireMessage($es->decrypt($response));

        // TODO: check if TC flag
        if ($dnsMessage->getHeader()->isTc()) {
            $tcpTransport = new DnsOverTcpTransport();
            $response = $tcpTransport->send($dnsUpstream->getUri(), $dnsQuery);
            $dnsMessage = $this->messageFactory->createMessageFromDnsWireMessage($es->decrypt($response));
        }

        // TODO: verify response integrity


        die(var_dump(json_encode($dnsMessage)));
    }

    private function pickCertificate(DnsUpstream $dnsUpstream): CertificateInterface
    {
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
        return array_shift($certificates);
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
