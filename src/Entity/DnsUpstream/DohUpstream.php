<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsUpstream;

use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamAddress;

class DohUpstream extends DnsUpstream
{
    public const TYPE = 'DOH_UPSTREAM';
    /**
     * @var string
     */
    private $path;
    /**
     * @var array
     */
    private $hashes;

    /**
     * @var string
     */
    private $sni;

    /**
     * @param DnsUpstreamAddress $address
     * @param string             $path
     * @param array              $hashes
     * @param string|null        $sni
     * @param string|null        $code
     */
    public function __construct(
        DnsUpstreamAddress $address,
        string $path,
        array $hashes,
        ?string $sni = null,
        ?string $code = null
    ) {
        parent::__construct($address, $code);
        $this->path = $path;
        $this->hashes = $hashes;
        $this->sni = $sni;
    }

    public function getSNI(): ?string
    {
        return $this->sni;
    }

    public function getHashes(): array
    {
        return $this->hashes;
    }

    public function getPath(): string
    {
        return $this->path ?? '/dns-query';
    }

    /**
     * Default port for DoH upstream is 443
     * @return int|null
     */
    public function getPort(): ?int
    {
        return parent::getPort() ?? 443;
    }

    public static function getType()
    {
        return static::TYPE;
    }
}
