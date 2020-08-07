<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface CertificateRepositoryInterface
{
    public function addCertificate(CertificateInterface $certificate): void;

    public function getCertificate(): CertificateInterface;
}
