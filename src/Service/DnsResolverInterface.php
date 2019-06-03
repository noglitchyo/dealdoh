<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface DnsResolverInterface
{
    public function resolve(MessageInterface $dnsMessage): MessageInterface;
}
