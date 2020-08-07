<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper;

use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
interface HttpResponseMapperInterface
{
    public function createResponseFromMessage(MessageInterface $dnsMessage): ResponseInterface;
}
