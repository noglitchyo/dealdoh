<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Helper;

class MessageHelper
{
    /**
     * Generate a random ID to be used with DNS message
     * @return int
     */
    public static function generateId()
    {
        return mt_rand(0, 0xffff);
    }
}
