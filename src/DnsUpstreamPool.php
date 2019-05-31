<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh;

/**
 * @codeCoverageIgnore
 */
class DnsUpstreamPool
{
    /** @var array */
    private $dnsUpstreams;

    public function __construct(array $dnsUpstreams)
    {
        foreach ($dnsUpstreams as $dnsUpstream){
            $this->addUpstream($dnsUpstream);
        }
    }

    public function addUpstream(string $dnsUpstream): void
    {
        $this->dnsUpstreams[] = new DnsUpstream($dnsUpstream);
    }

    /**
     * @return DnsUpstream[]
     */
    public function getUpstreams(): array
    {
        return $this->dnsUpstreams;
    }
}
