<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Client;

use NoGlitchYo\DoDoh\DnsUpstream;
use NoGlitchYo\DoDoh\Message\DnsMessageInterface;

interface DnsClientInterface
{
    public function resolve(DnsUpstream $dnsUpstream, DnsMessageInterface $dnsRequestMessage): DnsMessageInterface;

    /**
     * Indicate whether or not the given DNS upstream can be used by the client
     *
     * @param DnsUpstream $dnsUpstream
     * @return bool
     */
    public function supports(DnsUpstream $dnsUpstream): bool;
}
