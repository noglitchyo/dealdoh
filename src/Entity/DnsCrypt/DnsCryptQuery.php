<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

class DnsCryptQuery implements DnsCryptQueryInterface
{
    /**
     * @var string
     */
    private $clientMagic;
    /**
     * @var string
     */
    private $clientPublicKey;
    /**
     * @var string
     */
    private $clientNonce;
    /**
     * @var string
     */
    private $encryptedQuery;

    /**
     * @param string $clientMagic
     * @param string $clientPublicKey
     * @param string $clientNonce
     * @param string $encryptedQuery
     */
    public function __construct(
        string $clientMagic,
        string $clientPublicKey,
        string $clientNonce,
        string $encryptedQuery
    )
    {
        $this->clientMagic     = $clientMagic;
        $this->clientPublicKey = $clientPublicKey;
        $this->clientNonce     = $clientNonce;
        $this->encryptedQuery  = $encryptedQuery;
    }

    /**
     * @return string
     */
    public function getEncryptedQuery(): string
    {
        return $this->encryptedQuery;
    }

    /**
     * @return string
     */
    public function getClientNonce(): string
    {
        return $this->clientNonce;
    }

    /**
     * @return string
     */
    public function getClientPublicKey(): string
    {
        return $this->clientPublicKey;
    }

    /**
     * @return string
     */
    public function getClientMagic(): string
    {
        return $this->clientMagic;
    }

    public function __toString()
    {
        return implode(
            '',
            [
                $this->clientMagic,
                $this->clientPublicKey,
                $this->clientNonce,
                $this->encryptedQuery,
            ]
        );
    }
}
