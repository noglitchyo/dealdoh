<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
interface DohHttpMessageFactoryInterface
{
    public function createResponseFromMessage(MessageInterface $dnsMessage): ResponseInterface;
}
