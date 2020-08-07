<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

/**
 * @codeCoverageIgnore
 */
class DnsUpstreamAddress
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string|null
     */
    private $scheme;

    public function __construct(string $host, ?int $port = null, ?string $scheme = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->scheme = $scheme;
    }

    public static function create(string $uri): self
    {
        $host = parse_url($uri, PHP_URL_HOST) ?? $uri; // means that it was not able to find a scheme or anything else
        $scheme = parse_url($uri, PHP_URL_SCHEME) ?? null;
        $port = parse_url($uri, PHP_URL_PORT) ?? null;

        return new static($host, $port, $scheme);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * TODO: Retrieve port from IPV6 addr
     * @return int
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }
}
