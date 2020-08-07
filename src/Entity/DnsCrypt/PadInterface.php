<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface PadInterface
{
    public function getLength(): int;

    public function getValue(): string;
}
