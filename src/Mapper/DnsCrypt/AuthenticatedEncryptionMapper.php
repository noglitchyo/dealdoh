<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\AuthenticatedEncryptionInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\XChacha20AuthenticatedEncryption;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\XSalsa20AuthenticatedEncryption;

class AuthenticatedEncryptionMapper implements AuthenticatedEncryptionMapperInterface
{
    /**
     * Create an AuthenticatedEncryption instance from the given certificate
     *
     * @param CertificateInterface $certificate
     *
     * @return AuthenticatedEncryptionInterface
     * @throws Exception
     */
    public function createFromCertificate(
        CertificateInterface $certificate
    ): AuthenticatedEncryptionInterface {
        switch ($certificate->getEsVersion()) {
            case CertificateInterface::ES_VERSION_1:
                return new XSalsa20AuthenticatedEncryption(
                    $certificate->getClientMagic(),
                    $certificate->getResolverPublicKey()
                );
            case CertificateInterface::ES_VERSION_2:
                return new XChacha20AuthenticatedEncryption(
                    $certificate->getClientMagic(),
                    $certificate->getResolverPublicKey()
                );
            default:
                throw new Exception("Encryption system is not supported");
        }
    }
}
