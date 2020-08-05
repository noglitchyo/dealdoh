<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client\Transport;

interface DnsTransportInterface
{
    public function send(string $address, string $dnsWireMessage): string;
}
