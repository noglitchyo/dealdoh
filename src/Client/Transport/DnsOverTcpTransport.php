<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client\Transport;

use Exception;
use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Client\StdClient;

/**
 * Implement DNS Transport over TCP
 *
 * @see https://tools.ietf.org/html/rfc7766
 */
class DnsOverTcpTransport implements DnsTransportInterface
{
    public function send(string $address, string $dnsWireMessage): string
    {
        list("host" => $host, "port" => $port) = parse_url($address);

        $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 4);

        if ($socket === false) {
            throw new Exception('Unable to connect to DNS server: <' . $errno . '> ' . $errstr);
        }

        $dnsWireMessage = pack('n', strlen($dnsWireMessage)) . $dnsWireMessage;
        stream_set_blocking($socket, false);
        if (!@fputs($socket, $dnsWireMessage)) {
            throw new Exception('Unable to write to DNS server: <' . $errno . '> ' . $errstr);
        }
        $dnsWireResponseMessage = '';
        while (!feof($socket)) {
            $chunk = fread($socket, StdClient::EDNS_SIZE);
            if ($chunk === false) {
                throw new Exception('DNS message transfer from DNS server failed');
            }

            $dnsWireResponseMessage .= $chunk;
        }

        if (!$this->hasHeader($dnsWireResponseMessage)) {
            throw new Exception("DNS message corrupted: no header was found.");
        }

        if (!$this->hasData($dnsWireMessage)) {
            throw new Exception('DNS message corrupted: no data were found.');
        }

        fclose($socket);

        return substr($dnsWireResponseMessage, 2, $this->getLength($dnsWireResponseMessage));
    }

    /**
     * Check if message has data.
     *
     * @param string $dnsWireMessage
     *
     * @return bool
     */
    private function hasData(string $dnsWireMessage)
    {
        return strlen($dnsWireMessage) > $this->getLength($dnsWireMessage);
    }

    /**
     * Check if message has header
     * Response header is 12 bytes min.
     *
     * @param string $dnsWireMessage
     *
     * @return bool
     */
    private function hasHeader(string $dnsWireMessage)
    {
        return strlen($dnsWireMessage) >= 12;
    }

    /**
     * Retrieve length of the message from the first 2 bytes
     *
     * @see https://tools.ietf.org/html/rfc7766#section-8
     *
     * @param string $dnsWireMessage
     *
     * @return mixed
     */
    private function getLength(string $dnsWireMessage)
    {
        return unpack('n', $dnsWireMessage)[1];
    }
}
