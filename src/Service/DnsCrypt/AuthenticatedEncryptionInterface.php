<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\DnsCrypt;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;

interface AuthenticatedEncryptionInterface
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
}
