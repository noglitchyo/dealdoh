<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;

interface EncryptionSystemInterface
{
    /**
     * DNSCrypt queries sent by the client must use the <client-magic>
     * header of the chosen certificate, as well as the specified encryption
     * system and public key.
     *
     * Note: sodium_crypto_box uses X25519 + Xsalsa20 + Poly1305
     *
     * @param string $message
     *
     * @return DnsCryptQuery
     */
    public function encrypt(string $message): DnsCryptQuery;

    /**
     * @param string $message
     *
     * @return string
     */
    public function decrypt(string $message): string;

    /**
     * Return true if the encryption system for the given certificate is supported
     *
     * @param CertificateInterface $certificate
     *
     * @return bool
     */
    public function supports(CertificateInterface $certificate): bool;
}
