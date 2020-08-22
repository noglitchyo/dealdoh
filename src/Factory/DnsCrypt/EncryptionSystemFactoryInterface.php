<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\DnsCrypt;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\EncryptionSystemInterface;

interface EncryptionSystemFactoryInterface
{
    public function createEncryptionSystem(CertificateInterface $certificate): EncryptionSystemInterface;
}
