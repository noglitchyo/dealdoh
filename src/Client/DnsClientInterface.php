<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

interface DnsClientInterface
{
    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface;

    /**
     * Indicate whether or not the given DNS upstream can be used by the client
     *
     * @param  DnsUpstream $dnsUpstream
     * @return bool
     */
    public function supports(DnsUpstream $dnsUpstream): bool;
}
