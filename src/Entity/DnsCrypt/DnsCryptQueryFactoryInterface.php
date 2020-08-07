<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface DnsCryptQueryFactoryInterface
{
    public function getDnsCryptQuery(
        string $clientMagic,
        string $clientPublicKey,
        string $clientNonce,
        EncryptedQueryInterface $encryptedQuery
    ): DnsCryptQueryInterface;
}
