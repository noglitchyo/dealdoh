<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;

interface DnsClientInterface
{
    /**
     * Resolve a DNS message using the provided upstream
     *
     * @param DnsUpstream      $dnsUpstream
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     */
    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface;

    /**
     * Indicate whether or not the provided $dnsUpstream can be used by the client
     *
     * @param  DnsUpstream $dnsUpstream
     * @return bool
     */
    public function supports(DnsUpstream $dnsUpstream): bool;
}
