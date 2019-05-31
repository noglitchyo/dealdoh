<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;

use const STR_PAD_RIGHT;

/**
 * @codeCoverageIgnore
 */
class Base64UrlCodecHelper
{
    public static function encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
