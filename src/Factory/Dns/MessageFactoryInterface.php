<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface MessageFactoryInterface
{
    public function createMessageFromDnsWireMessage(string $dnsWireMessage): MessageInterface;

    public function createDnsWireMessageFromMessage(MessageInterface $dnsMessage): string;
}
