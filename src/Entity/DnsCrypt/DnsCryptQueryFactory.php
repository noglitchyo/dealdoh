<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

class DnsCryptQueryFactory implements DnsCryptQueryFactoryInterface
{
    public function getDnsCryptQuery(
        string $clientMagic,
        string $clientPublicKey,
        string $clientNonce,
        EncryptedQueryInterface $encryptedQuery
    ): DnsCryptQueryInterface {
        return new DnsCryptQuery($clientMagic, $clientPublicKey, $clientNonce, $encryptedQuery);
    }
}
