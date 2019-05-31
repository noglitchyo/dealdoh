<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh;

use NoGlitchYo\DoDoh\Message\DnsMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface DnsResolverInterface
{
    public function resolve(DnsMessageInterface $dnsMessage): DnsMessageInterface;
}
