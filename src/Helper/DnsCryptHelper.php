<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;


class DnsCryptHelper
{
    public const PADDING_START = 0x80;

    public static function removeUdpPadding(string $message): string
    {
        return substr($message, 0, strrpos($message, static::PADDING_START));
    }


    /**
     * Prior to encryption, queries are padded using the ISO/IEC 7816-4
     * format.
     *
     * The padding starts with a byte valued 0x80 followed by a
     * variable number of NUL bytes.
     *
     * <client-query> <client-query-pad> must be at least <min-query-len>
     * <min-query-len> is a variable length, initially set to 256 bytes, and
     * must be a multiple of 64 bytes.
     *
     * @param string $clientQuery
     *
     * @return string
     */
    public static function addUdpPadding(string $clientQuery)
    {
        // Check if query greater than min query length
        $queryLength = strlen($clientQuery);
        $paddingLength = 256;

        if ($queryLength > $paddingLength) {
            $paddingLength = $queryLength + (64 - ($queryLength % 64));
        }

        return sodium_pad($clientQuery . static::PADDING_START, $paddingLength);
    }
}