<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

class Certificate implements CertificateInterface
{
    /**
     * @var int
     */
    private $esVersion;

    /**
     * @var string
     */
    private $signature;
    /**
     * @var int
     */
    private $serial;
    /**
     * @var int
     */
    private $tsStart;
    /**
     * @var int
     */
    private $tsEnd;
    /**
     * @var string
     */
    private $clientMagic;
    /**
     * @var string
     */
    private $resolverPublicKey;

    public function __construct(
        int $esVersion,
        string $signature,
        int $serial,
        int $tsStart,
        int $tsEnd,
        string $clientMagic,
        string $resolverPublicKey
    ) {
        $this->esVersion = $esVersion;
        $this->signature = $signature;
        $this->serial = $serial;
        $this->tsStart = $tsStart;
        $this->tsEnd = $tsEnd;
        $this->clientMagic = $clientMagic;
        $this->resolverPublicKey = $resolverPublicKey;
    }

    public function getEsVersion(): int
    {
        return $this->esVersion;
    }

    public function getAuthenticatedEncryptionAlgorithm(): string
    {
        // TODO: Implement getAuthenticatedEncryptionAlgorithm() method.
    }

    public function getAuthenticatedEncryptionParameters(): array
    {
        // TODO: Implement getAuthenticatedEncryptionParameters() method.
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getResolverPublicKey(): string
    {
        return $this->resolverPublicKey;
    }

    public function getClientMagic(): string
    {
        return $this->clientMagic;
    }

    public function getSerial(): int
    {
        return $this->serial;
    }

    public function getTsStart(): int
    {
        return $this->tsStart;
    }

    public function getTsEnd(): int
    {
        return $this->tsEnd;
    }

    public function getExtensions(): void
    {
        // TODO: Implement getExtensions() method.
    }
}
