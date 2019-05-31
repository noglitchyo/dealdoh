<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Factory;

use NoGlitchYo\DoDoh\Message\DnsMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface DnsMessageFactoryInterface
{
    public function createMessageFromDnsWireMessage(string $dnsWireMessage): DnsMessageInterface;

    public function createDnsWireMessageFromMessage(DnsMessageInterface $dnsMessage): string;
}
