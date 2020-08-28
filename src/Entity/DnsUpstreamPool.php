<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use Exception;
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
     *
     * @param string $jsonUpstreamPool
     *
     * @return static
     * @throws Exception
     */
    public static function fromJson(string $jsonUpstreamPool): self
    {
        $dnsUpstreamFactory = new DnsUpstreamFactory();

        $dnsUpstreams = json_decode($jsonUpstreamPool, true);

        $upstreams = [];

        foreach ($dnsUpstreams as $dnsUpstream) {
            // TODO: cover this with test case
            if (!is_array($dnsUpstream)) {
                $dnsUpstream['uri'] = $dnsUpstream;
            }

            $upstreams[] = $dnsUpstreamFactory->create($dnsUpstream['uri'], $dnsUpstream['code'] ?? null);
        }

        return new static($dnsUpstreams);
    }

    /**
     * @param DnsUpstreamInterface[] $dnsUpstreams
     *
     * @throws Exception
     */
    public function __construct(array $dnsUpstreams = [])
    {
        foreach ($dnsUpstreams as $dnsUpstream) {
            $this->addUpstream($dnsUpstream);
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
