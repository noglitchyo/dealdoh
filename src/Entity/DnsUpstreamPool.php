<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use JsonSerializable;

/**
 * @codeCoverageIgnore
 */
class DnsUpstreamPool implements JsonSerializable
{
    /**
     * @var array
     */
    private $dnsUpstreams = [];

    public static function fromJson(string $jsonUpstreamPool): self
    {
        $upstreamPool = json_decode($jsonUpstreamPool, true);

        return new static($upstreamPool);
    }

    public function __construct(array $dnsUpstreams = [])
    {
        foreach ($dnsUpstreams as $dnsUpstream) {
            // TODO: cover this with test case
            if (!is_array($dnsUpstream)) {
                $dnsUpstream['uri'] = $dnsUpstream;
            }

            $this->addUpstream(new DnsUpstream($dnsUpstream['uri'], $dnsUpstream['code'] ?? null));
        }
    }

    public function addUpstream(DnsUpstream $dnsUpstream): self
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
