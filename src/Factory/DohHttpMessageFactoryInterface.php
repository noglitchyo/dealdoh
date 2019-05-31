<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use NoGlitchYo\Dealdoh\Message\DnsMessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
interface DohHttpMessageFactoryInterface
{
    public function createResponseFromMessage(DnsMessageInterface $dnsMessage): ResponseInterface;
}
