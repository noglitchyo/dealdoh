<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

class EncryptedQuery implements EncryptedQueryInterface
{
    /**
     * @var string
     */
    private $sharedKey;

    /**
     * @var string
     */
    private $clientNonce;

    /**
     * @var string
     */
    private $clientNoncePad;

    /**
     * @var string
     */
    private $clientQuery;


    public function __construct(
        string $sharedKey,
        string $clientNonce,
        string $clientNoncePad,
        string $clientQuery
    ) {
        $this->sharedKey      = $sharedKey;
        $this->clientNonce    = $clientNonce;
        $this->clientNoncePad = $clientNoncePad;
        $this->clientQuery    = $clientQuery;
    }

    public function getSharedKey(): string
    {
        return $this->sharedKey;
    }

    public function getClientNonce(): string
    {
        return $this->clientNonce;
    }

    public function getClientNoncePad(): string
    {
        return $this->clientNoncePad;
    }

    public function getClientQuery(): string
    {
        return $this->clientQuery;
    }

    public function __toString()
    {
        return implode(
            '',
            [
                $this->sharedKey,
                $this->clientNonce,
                $this->clientNoncePad,
                $this->clientQuery
            ]
        );
    }
}
