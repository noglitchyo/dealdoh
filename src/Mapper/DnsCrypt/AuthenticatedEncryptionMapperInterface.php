<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper\DnsCrypt;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\AuthenticatedEncryptionInterface;

interface AuthenticatedEncryptionMapperInterface
{
    public function createFromCertificate(CertificateInterface $certificate): AuthenticatedEncryptionInterface;
}
