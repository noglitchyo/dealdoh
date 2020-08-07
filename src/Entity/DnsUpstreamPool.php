<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use JsonSerializable;
use NoGlitchYo\Dealdoh\Factory\DnsUpstreamFactory;

/**
 * A DnsUpstreamPool stores a collection of DnsUpstreamInterface.
 *
 * @codeCoverageIgnore
 */
class DnsUpstreamPool implements DnsUpstreamPoolInterface, JsonSerializable
{
    /**
     * @var DnsUpstream[]
     */
    private $dnsUpstreams = [];

    /**
     * Create a new DnsUpstreamPool from a JSON list of upstreams
     * @param string $jsonUpstreamPool
     *
     * @return static
     */
    public static function fromJson(string $jsonUpstreamPool): self
    {
        $upstreamPool = json_decode($jsonUpstreamPool, true);

        return new static($upstreamPool);
    }

    public function __construct(array $dnsUpstreams = [])
    {
        $dnsUpstreamFactory = new DnsUpstreamFactory();

        foreach ($dnsUpstreams as $dnsUpstream) {
            // TODO: cover this with test case
            if (!is_array($dnsUpstream)) {
                $dnsUpstream['uri'] = $dnsUpstream;
            }

            $this->addUpstream(
                $dnsUpstreamFactory->create($dnsUpstream['uri'], $dnsUpstream['code'] ?? null)
            );
        }
    }

    public function addUpstream(DnsUpstreamInterface $dnsUpstream): DnsUpstreamPoolInterface
    {
        $this->dnsUpstreams[] = $dnsUpstream;

        return $this;
    }

    /**
     * @return DnsUpstream[]
     */
    public function getUpstreams(): array
    {
        return $this->dnsUpstreams;
    }

    public function jsonSerialize(): array
    {
        return $this->dnsUpstreams;
    }
}
