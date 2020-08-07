<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Repository\DnsCrypt;

use DateTimeImmutable;
use Exception;
use LogicException;
use NoGlitchYo\Dealdoh\Dns\Client\PlainDnsClient;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Entity\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\DnsCrypt\DnsCryptCertificateMapper;

class CertificateRepository implements CertificateRepositoryInterface
{
    /**
     * @var PlainDnsClient
     */
    private $stdClient;

    public function __construct(PlainDnsClient $stdClient)
    {
        $this->stdClient = $stdClient;
    }

    /**
     * Retrieve a certificate from the resolver which will be used to send queries to this resolver
     *
     * @param DnsCryptUpstream $dnsUpstream
     * @param array            $supportedEsVersions
     *
     * @return CertificateInterface
     * @throws Exception
     */
    public function getCertificate(
        DnsCryptUpstream $dnsUpstream,
        array $supportedEsVersions = []
    ): CertificateInterface {
        /**
         * The client begins a DNSCrypt session by sending a regular unencrypted
         * TXT DNS query to the resolver IP address, on the DNSCrypt port, first
         * over UDP, then, in case of failure, timeout or truncation, over TCP.
         *
         * This DNS query encodes the certificate versions supported by the
         * client, as well as a public identifier of the provider requested by
         * the client.
         */
        $dnsResponseWithCertificates = $this->getCertificates($dnsUpstream);


        $certificates = $this->createCertificates(
            $dnsResponseWithCertificates->getAnswer(),
            $dnsUpstream->getProviderPublicKey()
        );

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
        $certificates = $this->validateCertificates($certificates);

        $filteredCertificates = $this->filterCertificates($certificates, $supportedEsVersions);
        if (!empty($certificates) && empty($filteredCertificates)) {
            throw new Exception(
                'Encryption mechanisms attached to the certificates from the upstreams are not supported'
            );
        }

        $certificates = $this->sortCertificates($filteredCertificates);

        if (empty($certificates)) {
            throw new Exception("No certificates available");
        }

        return array_shift($certificates);
    }

    /**
     * @param ResourceRecordInterface[] $dnsMessageAnswer
     * @param string                    $providerPublicKey
     *
     * @return array|CertificateInterface[]
     * @throws Exception
     */
    private function createCertificates(array $dnsMessageAnswer, string $providerPublicKey)
    {
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
        $certificateMapper = new DnsCryptCertificateMapper();
        /** @var CertificateInterface[] $certificates */
        $certificates = [];
        foreach ($dnsMessageAnswer as $record) {
            $certificates[] = $certificateMapper->createFromResourceRecord(
                $record,
                $providerPublicKey
            );
        }

        return $certificates;
    }

    /**
     * Sort certificates from the one with the highest serial number to the lowest
     *
     * @param array $certificates
     *
     * @return array
     * @throws Exception
     */
    private function sortCertificates(array $certificates): array
    {
        $isFiltered = usort(
            $certificates,
            function (CertificateInterface $certA, CertificateInterface $certB) {
                if ($certA->getSerial() > $certB->getSerial()) {
                    return 1;
                }
                if ($certA->getSerial() < $certB->getSerial()) {
                    return -1;
                }
                return 0;
            }
        );

        if (!$isFiltered) {
            throw new Exception("Sorting certificates by serial number failed");
        }

        return $certificates;
    }

    /**
     * Filter certificates with the given supported es versions
     *
     * @param CertificateInterface[] $certificates
     * @param array                  $supportedEncryptions
     *
     * @return array
     */
    private function filterCertificates(array $certificates, array $supportedEncryptions): array
    {
        return array_filter(
            $certificates,
            function (CertificateInterface $certificate) use ($supportedEncryptions) {
                return in_array($certificate->getEsVersion(), $supportedEncryptions);
            }
        );
    }

    /**
     * @param CertificateInterface[] $certificates
     *
     * @return CertificateInterface[]
     * @throws Exception
     */
    private function validateCertificates(array $certificates): array
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
        $dnsQuery = new Message(
            new Header(0, false, 0, false, false, false, false, 0, 0),
            new QuestionSection(
                [
                    new Query(
                        $dnsCryptUpstream->getProviderName(),
                        ResourceRecordInterface::TYPE_TXT,
                        ResourceRecordInterface::CLASS_IN
                    ),
                ]
            )
        );

        return $this->stdClient->query($dnsCryptUpstream, $dnsQuery);
    }
}
