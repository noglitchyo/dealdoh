<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;

use const STR_PAD_RIGHT;

/**
 * @codeCoverageIgnore
 */
class EncodingHelper
{
    public static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
