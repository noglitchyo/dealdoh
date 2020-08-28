<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;

interface DnsClientInterface
{
    /**
     * Query the given DNS upstream with the given DNS message
     *
     * @param DnsUpstreamInterface $dnsUpstream
     * @param MessageInterface     $dnsRequestMessage
     *
     * @return MessageInterface
     */
    public function query(DnsUpstreamInterface $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface;

    /**
     * Return true if the client can send queries to the provided $dnsUpstream
     *
     * @param DnsUpstreamInterface $dnsUpstream
     *
     * @return bool
     */
    public function supports(DnsUpstreamInterface $dnsUpstream): bool;
}
