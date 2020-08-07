<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\Transport;

interface DnsTransportInterface
{
    public function send(string $address, int $port, string $dnsWireMessage): string;
}
