<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use JsonSerializable;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

/**
 * @codeCoverageIgnore
 */
class DnsUpstream implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var null|string
     */
    private $scheme;

    /**
     * @var null|int
     */
    private $port;

    /**
     * @var string
     */
    private $code;

    public function __construct(string $uri, ?string $code = null)
    {
        $this->uri = $uri;
        $this->port = parse_url($uri, PHP_URL_PORT) ?? null;
        $this->scheme = parse_url($uri, PHP_URL_SCHEME) ?? null;
        $this->code = $code ?? $uri;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'uri' => $this->uri,
        ];
    }

    /**
     * Clean up the protocol from URI supported by the client but which can not be used with transport (e.g. dns://)
     *
     * @return string
     */
    public function getAddr(): string
    {
        return str_replace($this->getScheme() . '://', '', $this->getUri());
    }
}
