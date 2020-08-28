<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

/**
 * A DnsUpstreamPool stores a collection of DnsUpstreamInterface.
 *
 * @codeCoverageIgnore
 */
interface DnsUpstreamPoolInterface
{
    public function addUpstream(DnsUpstreamInterface $dnsUpstream): self;

    /**
     * @return DnsUpstream[]
     */
    public function getUpstreams(): array;
}
