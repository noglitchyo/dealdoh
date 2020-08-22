<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Entity\DnsResource;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface DnsResolverInterface
{
    public function resolve(MessageInterface $dnsRequest): DnsResource;
}
