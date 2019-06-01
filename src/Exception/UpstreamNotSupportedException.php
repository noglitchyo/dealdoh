<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Exception;

use Exception;
use NoGlitchYo\Dealdoh\DnsUpstream;
use Throwable;

/**
 * @codeCoverageIgnore 
 */
class UpstreamNotSupportedException extends Exception
{
    public function __construct(DnsUpstream $dnsUpstream, Throwable $previous = null)
    {
        $message = sprintf('Upstream %s is not supported', $dnsUpstream->getUri());

        parent::__construct($message, 0, $previous);
    }
}
