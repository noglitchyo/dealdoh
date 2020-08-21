<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\EncryptionSystemInterface;

interface DnsCryptServiceInterface
{
    public function getEncryptionSystem(CertificateInterface $certificate): EncryptionSystemInterface;
}
