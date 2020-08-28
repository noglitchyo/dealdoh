<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\Transport;

use Exception;
use NoGlitchYo\Dealdoh\Dns\Client\PlainDnsClient;
use NoGlitchYo\Dealdoh\Helper\TransportHelper;

class DnsOverUdpTransport implements DnsTransportInterface
{
    public function send(string $address, int $port, string $dnsWireMessage): string
    {
        $addr = TransportHelper::createAddress('udp', $address, $port);

        $length = strlen($dnsWireMessage);
        $socket = @stream_socket_client($addr, $errno, $errstr, 4);

        if ($socket === false) {
            throw new Exception('Unable to connect to DNS server: <' . $errno . '> ' . $errstr);
        }

        // Must use DNS over TCP if message is bigger
        if ($length > PlainDnsClient::EDNS_SIZE) {
            throw new Exception(
                sprintf(
                    'DNS message is `%s` bytes, maximum `%s` bytes allowed. Use TCP transport instead',
                    $length,
                    PlainDnsClient::EDNS_SIZE
                )
            );
        }

        if (!@fputs($socket, $dnsWireMessage)) {
            throw new Exception('Unable to write to DNS server: <' . $errno . '> ' . $errstr);
        }

        $dnsWireResponseMessage = @fread($socket, PlainDnsClient::EDNS_SIZE);

        if ($dnsWireResponseMessage === false || $dnsWireResponseMessage === "") {
            throw new Exception('Unable to read from DNS server: Error <' . $errno . '> ' . $errstr);
        }
        fclose($socket);

        return $dnsWireResponseMessage;
    }
}
