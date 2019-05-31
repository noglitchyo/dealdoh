<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh;

use NoGlitchYo\Dealdoh\Message\DnsMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface DnsResolverInterface
{
    public function resolve(DnsMessageInterface $dnsMessage): DnsMessageInterface;
}
