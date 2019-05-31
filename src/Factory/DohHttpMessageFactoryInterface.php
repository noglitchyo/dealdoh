<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Factory;

use NoGlitchYo\DoDoh\Message\DnsMessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
interface DohHttpMessageFactoryInterface
{
    public function createResponseFromMessage(DnsMessageInterface $dnsMessage): ResponseInterface;
}
