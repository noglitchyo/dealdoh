<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface ClientQueryInterface
{
    /**
     * Variable length, initially set to 256 bytes, and
     * must be a multiple of 64 bytes.
     */
    const MIN_QUERY_LENGTH = 256;

    /**
     * The padding starts with a byte valued 0x80 followed by a
     * variable number of NUL bytes
     */
    const PADDING_START = 0x80;
}
