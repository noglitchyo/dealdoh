<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

class CertificateRepository implements CertificateRepositoryInterface
{
    /**
     * @var CertificateInterface[]
     */
    private $certificates;

    public function addCertificate(CertificateInterface $certificate): void
    {
        $this->certificates[] = $certificate;
    }

    public function getCertificate(): CertificateInterface
    {
        /**
         * After having received a set of certificates, the client checks their
         * validity based on the current date, filters out the ones designed for
         * encryption systems that are not supported by the client, and chooses
         * the certificate with the higher serial number.
         */
    }
}
