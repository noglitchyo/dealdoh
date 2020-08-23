<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;

interface DnsClientInterface
{
    /**
     * Query the given DNS upstream with the given DNS message
     *
     * @param DnsUpstream      $dnsUpstream
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     */
    public function query(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface;

    /**
     * Return true if the client can send queries to the provided $dnsUpstream
     *
     * @param  DnsUpstream $dnsUpstream
     * @return bool
     */
    public function supports(DnsUpstream $dnsUpstream): bool;
}
