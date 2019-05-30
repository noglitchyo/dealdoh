<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Exception;

use Exception;
use NoGlitchYo\DoDoh\DnsUpstream;
use Throwable;

class UpstreamNotSupportedException extends Exception
{
    public function __construct(DnsUpstream $dnsUpstream, Throwable $previous = null)
    {
        $message = sprintf('Upstream %s is not supported', $dnsUpstream->getUri());

        parent::__construct($message, 0, $previous);
    }
}