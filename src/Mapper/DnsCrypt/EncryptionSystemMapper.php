<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\AuthenticatedEncryptionInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\XChacha20AuthenticatedEncryption;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\XSalsa20AuthenticatedEncryption;

class EncryptionSystemMapper implements EncryptionSystemMapperInterface
{
    public function createEncryptionSystem(CertificateInterface $certificate): AuthenticatedEncryptionInterface
    {
        switch ($certificate->getEsVersion()) {
            case CertificateInterface::ES_VERSION_XSALSA20POLY1305:
                return new XSalsa20AuthenticatedEncryption($certificate);
            case CertificateInterface::ES_VERSION_XCHACHA20POLY1305:
                return new XChacha20AuthenticatedEncryption($certificate);
            default:
                throw new Exception("Encryption system not supported");
        }
    }
}
