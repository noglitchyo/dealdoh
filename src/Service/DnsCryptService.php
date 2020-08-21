<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\EncryptionSystemInterface;
use NoGlitchYo\Dealdoh\Service\XChacha20EncryptionSystem;
use NoGlitchYo\Dealdoh\Service\XSalsa20EncryptionSystem;

class DnsCryptService implements DnsCryptServiceInterface
{
    public function getEncryptionSystem(CertificateInterface $certificate): EncryptionSystemInterface
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
