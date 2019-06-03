<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

/**
 * @codeCoverageIgnore
 */
class DnsUpstreamPool
{
    /**
     * @var array
     */
    private $dnsUpstreams = [];

    public function __construct(array $dnsUpstreams = [])
    {
        foreach ($dnsUpstreams as $dnsUpstream) {
            $this->addUpstream(new DnsUpstream($dnsUpstream));
        }
    }

    public function addUpstream(DnsUpstream $dnsUpstream): void
    {
        $this->dnsUpstreams[] = $dnsUpstream;
    }

    /**
     * @return DnsUpstream[]
     */
    public function getUpstreams(): array
    {
        return $this->dnsUpstreams;
    }
}
