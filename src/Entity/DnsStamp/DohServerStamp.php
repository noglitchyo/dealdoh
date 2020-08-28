<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsStamp;

use NoGlitchYo\Dealdoh\Entity\DnsUpstreamAddress;
use NoGlitchYo\Dealdoh\Entity\ServerStampProps;

/**
 * @codeCoverageIgnore
 */
class DohServerStamp implements ServerStampInterface
{
    /**
     * @var ServerStampProps
     */
    private $serverStampProps;

    /**
     * @var array
     */
    private $hashes;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $path;

    /**
     * @var DnsUpstreamAddress
     */
    private $address;

    /**
     * @param ServerStampProps $serverStampProps
     * @param array            $hashes
     */
    public function __construct(
        ServerStampProps $serverStampProps,
        DnsUpstreamAddress $address,
        array $hashes,
        string $hostname,
        string $path
    ) {
        $this->serverStampProps = $serverStampProps;
        $this->hashes = $hashes;
        $this->hostname = $hostname;
        $this->path = $path;
        $this->address = $address;
    }

    public function getServerStampProps(): ServerStampProps
    {
        return $this->serverStampProps;
    }

    public function getProtocolIdentifier(): int
    {
        return static::DOH_STAMP;
    }

    /**
     * Return the IP address of the server.
     * It can be an empty string, or just a port number, represented with a preceding colon (:443).
     * In that case, the host name will be resolved to an IP address using another resolver.
     * @return string
     */
    public function getAddress(): DnsUpstreamAddress
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @return array
     */
    public function getHashes(): array
    {
        return $this->hashes;
    }
}
