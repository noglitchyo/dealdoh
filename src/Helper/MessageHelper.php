<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;

class MessageHelper
{
    public static function generateId()
    {
        return mt_rand(0, 0xffff);
    }
}
