<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

class TransportHelper
{
    /**
     * Create a suitable address to be used with DnsTransportInterface whether it is an IPV6 or IPV4 address
     *
     * @param string $proto
     * @param string $address
     * @param int    $port
     *
     * @return string
     */
    public static function createAddress(string $proto, string $address, int $port): string
    {
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $proto . '://' . $address . ':' . $port;
        }

        return $proto . '://[' . $address . ']' . ':' . $port;
    }
}
