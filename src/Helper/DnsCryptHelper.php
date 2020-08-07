<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;

use Exception;
use ParagonIE_Sodium_Compat;
use SodiumException;

class DnsCryptHelper
{
    public const PADDING_START = 0x80;

    public static function removePadding(string $message): string
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
     * Client queries over TCP only differ from queries sent over UDP by the
     * padding length computation and by the fact that they are prefixed with
     * their length, encoded as two little-endian bytes.
     *
     * @param string $clientQuery
     *
     * @return string
     * @throws SodiumException
     */
    public static function addPadding(string $clientQuery)
    {
        // Check if query greater than min query length
        $queryLength = strlen($clientQuery);
        $minQueryLength = 256;

        if ($queryLength > $minQueryLength) {
            $minQueryLength = $queryLength + (64 - ($queryLength % 64));
        }

        return ParagonIE_Sodium_Compat::pad($clientQuery . static::PADDING_START, $minQueryLength);
    }

    /**
     * Create a client nonce and return it with its padded version.
     * <client-nonce> length is half the nonce length
     * required by the encryption algorithm. In client queries, the other half,
     * <client-nonce-pad> is filled with NUL bytes.
     *
     * @param int $nonceLength
     *
     * @return array<client-nonce, padded-client-nonce>
     * @throws Exception
     */
    public static function createClientNonce(int $nonceLength): array
    {
        $halfNonceLength = $nonceLength / 2;
        $clientNonce = random_bytes($halfNonceLength);

        return [
            $clientNonce,
            $clientNonce . str_repeat("\0", $halfNonceLength)
        ];
    }
}
