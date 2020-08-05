<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Exception;

use Exception;
use Throwable;

class InvalidDnsWireMessageException extends Exception
{
    public function __construct(string $dnsWireMessage, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf("Invalid DNS wire message: `%s`", $dnsWireMessage), $code, $previous);
    }
}
