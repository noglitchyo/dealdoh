<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

/**
 * @codeCoverageIgnore
 */
class DnsUpstream implements DnsUpstreamInterface
{
    public const TYPE = 'DNS_UPSTREAM';

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $code;
    /**
     * @var DnsUpstreamAddress
     */
    protected $address;

    /**
     * TODO: get port from IPV6 addr
     *
     * @param DnsUpstreamAddress $address
     * @param string|null        $code
     */
    public function __construct(DnsUpstreamAddress $address, ?string $code = null)
    {
        $this->address = $address;
        $this->code = $code ?? $this->address->getHost();
    }

    public function getPort(): ?int
    {
        return $this->address->getPort();
    }

    public function getScheme(): ?string
    {
        return $this->address->getScheme();
    }

    public function getUri(): string
    {
        // TODO: should return the full original  URI
        return $this->address->getHost();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'uri'  => $this->uri,
        ];
    }

    /**
     * Clean up the protocol from URI supported by the client but which can not be used with transport (e.g. dns://)
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->address->getHost();
    }

    public static function getType()
    {
        return static::TYPE;
    }
}
