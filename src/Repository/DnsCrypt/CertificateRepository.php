<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Repository\DnsCrypt;

use DateTimeImmutable;
use Exception;
use LogicException;
use NoGlitchYo\Dealdoh\Client\StdClient;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Entity\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\MessageMapper;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;

class CertificateRepository implements CertificateRepositoryInterface
{
    /**
     * Retrieve a certificate from the resolver which will be used to send queries to this resolver
     * @param DnsCryptUpstream $dnsUpstream
     * @return CertificateInterface
     * @throws Exception
     */
    public function getCertificateForUpstream(DnsCryptUpstream $dnsUpstream): CertificateInterface
    {
        /**
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
        $dnsCertificateFactory = new \NoGlitchYo\Dealdoh\Mapper\DnsCrypt\DnsCryptCertificateMapper();
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
        $stdClient = new StdClient(new MessageMapper(), new DnsOverTcpTransport(), new DnsOverUdpTransport());

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
}