<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

/**
 * @codeCoverageIgnore
 */
interface DnsUpstreamPoolInterface
{
    public function addUpstream(DnsUpstream $dnsUpstream): self;

    /**
     * @return DnsUpstream[]
     */
    public function getUpstreams(): array;
}
