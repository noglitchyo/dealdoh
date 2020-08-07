<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsStamp\DnsCryptServerStamp;
use NoGlitchYo\Dealdoh\Entity\DnsStamp\DohServerStamp;
use NoGlitchYo\Dealdoh\Entity\DnsStamp\ServerStampInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamAddress;
use NoGlitchYo\Dealdoh\Entity\ServerStampProps;
use NoGlitchYo\Dealdoh\Helper\UrlSafeBase64CodecHelper;

class ServerStampFactory
{
    /**
     * Return true if the provided URI is a dns server stamp
     *
     * @param string $uri
     *
     * @return bool
     */
    public static function isDnsStamp(string $uri): bool
    {
        return strpos(strtolower($uri ?? ''), 'sdns') !== false;
    }

    public function create(string $stamp): ServerStampInterface
    {
        if (!static::isDnsStamp($stamp)) {
            throw new Exception('Not a valid server stamp');
        }

        // Cleanup stamp from scheme
        $stamp = str_replace('sdns://', '', $stamp);

        $decodedStamp = UrlSafeBase64CodecHelper::decode($stamp);

        // Extract protocol
        $protocol = array_values(unpack('C', $decodedStamp))[0];

        // Extract props
        $props = array_values(unpack('P*', substr($decodedStamp, 1, 9)))[0];
        [$isDnsSecReady, $noLogs, $noFilters] = [
            (bool)($props >> 0 & 1),
            (bool)($props >> 1 & 1),
            (bool)($props >> 2 & 1),
        ];
        $serverStampProps = new ServerStampProps($isDnsSecReady, $noLogs, $noFilters);

        // Extract address
        $addressLength = array_values(unpack('C', substr($decodedStamp, 9, 1)))[0];


        $address = substr($decodedStamp, 10, $addressLength);
        if (strpos($address, '[') === 0) {
            // TODO: handle IPV6 address
            $address = str_replace(['[', ']'], '', $address);
        }
        $decodedStamp = substr($decodedStamp, 10 + $addressLength);

        $address = DnsUpstreamAddress::create($address);

        switch ($protocol) {
            case ServerStampInterface::DNSCRYPT_STAMP:
                return $this->createDnsCryptStamp($address, $decodedStamp, $serverStampProps);
            case ServerStampInterface::DOH_STAMP:
                return $this->createDohStamp($address, $decodedStamp, $serverStampProps);
            default:
                throw new Exception('Protocol is not supported');
        }
    }

    private function createDohStamp(
        DnsUpstreamAddress $address,
        string $decodedStamp,
        ServerStampProps $serverStampProps
    ): ServerStampInterface {
        $pos = 0;

        // Extract hashes
        $hashes = [];
        while (true) {
            // len(x) is a single byte representation of the length of x, in bytes
            $vlength = (int)array_values(unpack('C', substr($decodedStamp, $pos, 1)))[0];

            // vlen(x) is equal to len(x) if x is the last element of a set, and 0x80 | len(x) if there are more elements in the set.
            $length = $vlength & ~0x80; // not  $vlength ^ 0x80
            $hashes[] = bin2hex(substr($decodedStamp, ++$pos, $length));

            $pos += $length;

            // if nothing left (length of the last element doesn’t have the MSB set)
            if (($vlength & 0x80) != 0x80) {
                break;
            }
        }

        // Extract hostname
        $hostnameLength = array_values(unpack('C', substr($decodedStamp, $pos, 1)))[0];
        $hostname = substr($decodedStamp, ++$pos, $hostnameLength);
        $pos += $hostnameLength;

        // Extract path
        $pathLength = array_values(unpack('C', substr($decodedStamp, $pos, 1)))[0];
        $path = substr($decodedStamp, ++$pos, $pathLength);

        return new DohServerStamp($serverStampProps, $address, $hashes, $hostname, $path);
    }

    private function createDnsCryptStamp(
        DnsUpstreamAddress $address,
        string $decodedStamp,
        ServerStampProps $serverStampProps
    ): ServerStampInterface {
        $pos = 0;

        // Extract public key
        $publicKeyLength = array_values(unpack('C', substr($decodedStamp, $pos, 1)))[0];
        $publicKey = bin2hex(substr($decodedStamp, ++$pos, $publicKeyLength));
        $pos += $publicKeyLength;

        // Extract provider name
        $providerNameLength = array_values(unpack('C', substr($decodedStamp, $pos, 1)))[0];
        $providerName = substr($decodedStamp, ++$pos, $providerNameLength);

        return new DnsCryptServerStamp($serverStampProps, $address, $publicKey, $providerName);
    }
}
