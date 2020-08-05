<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Exception;

use Exception;
use NoGlitchYo\Dealdoh\Client\DnsClientInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class DnsPoolResolveFailedException extends Exception
{
    public const EC_UPSTREAMS_FAILED = 1001;
    public const EC_CLIENTS_FAILED = 1002;
    public const EC_CLIENTS_POOL_EMPTY = 1003;

    /**
     * @var DnsClientInterface[]
     */
    private $clients;

    /**
     * @var DnsUpstream[]
     */
    private $upstreams;

    public function __construct(
        $message = "",
        array $clients = [],
        array $dnsUpstreams = [],
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->clients = $clients;
        $this->upstreams = $dnsUpstreams;
    }

    public static function emptyDnsPool(): self
    {
        return new static('Upstream pool is empty.', [], [], static::EC_CLIENTS_POOL_EMPTY);
    }

    public static function unableToResolveFromClients(array $dnsClients): self
    {
        return new static('Unable to resolve from clients', $dnsClients, [], static::EC_CLIENTS_FAILED);
    }

    public static function unableToResolveFromUpstreams(array $dnsUpstreams): self
    {
        return new static(
            'Unable to resolve DNS message from upstreams',
            [],
            $dnsUpstreams,
            static::EC_UPSTREAMS_FAILED
        );
    }
}
