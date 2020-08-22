<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\EncryptionSystemInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\XChacha20EncryptionSystem;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\XSalsa20EncryptionSystem;

class EncryptionSystemFactory implements EncryptionSystemFactoryInterface
{
    public function createEncryptionSystem(CertificateInterface $certificate): EncryptionSystemInterface
    {
        switch ($certificate->getEsVersion()) {
            case CertificateInterface::ES_VERSION_XSALSA20POLY1305:
                return new XSalsa20EncryptionSystem($certificate);
            case CertificateInterface::ES_VERSION_XCHACHA20POLY1305:
                return new XChacha20EncryptionSystem($certificate);
            default:
                throw new Exception("Encryption system not supported");
        }
    }
}
