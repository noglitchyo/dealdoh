<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsResource;

/**
 * @codeCoverageIgnore
 */
interface DnsResolverInterface
{
    public function resolve(MessageInterface $dnsRequest): DnsResource;
}
