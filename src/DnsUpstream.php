<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh;

/**
 * @codeCoverageIgnore
 */
class DnsUpstream
{
    /** @var string */
    private $uri;

    /** @var null|string */
    private $scheme;

    /** @var null|int */
    private $port;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
        $parsedUri = parse_url($uri);
        $this->port = $parsedUri['port'] ?? null;
        $this->scheme = $parsedUri['scheme'] ?? null;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
