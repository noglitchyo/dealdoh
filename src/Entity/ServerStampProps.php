<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

/**
 * @codeCoverageIgnore
 */
class ServerStampProps
{
    /**
     * @var bool
     */
    private $isDnsSecReady;

    /**
     * @var bool
     */
    private $keepLogs;

    /**
     * @var bool
     */
    private $blockDomains;

    public function __construct(bool $isDnsSecReady, bool $keepLogs, bool $blockDomains)
    {
        $this->isDnsSecReady = $isDnsSecReady;
        $this->keepLogs = $keepLogs;
        $this->blockDomains = $blockDomains;
    }
}
