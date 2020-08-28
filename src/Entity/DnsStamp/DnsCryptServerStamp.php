<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsStamp;

use NoGlitchYo\Dealdoh\Entity\DnsUpstreamAddress;
use NoGlitchYo\Dealdoh\Entity\ServerStampProps;

/**
 * @codeCoverageIgnore
 */
class DnsCryptServerStamp implements ServerStampInterface
{
    /**
     * @var ServerStampProps
     */
    private $serverStampProps;

    /**
     * @var DnsUpstreamAddress
     */
    private $address;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $providerName;

    /**
     * @param ServerStampProps   $serverStampProps
     * @param DnsUpstreamAddress $address
     * @param string             $publicKey
     * @param string             $providerName
     */
    public function __construct(
        ServerStampProps $serverStampProps,
        DnsUpstreamAddress $address,
        string $publicKey,
        string $providerName
    ) {
        $this->serverStampProps = $serverStampProps;
        $this->address = $address;
        $this->publicKey = $publicKey;
        $this->providerName = $providerName;
    }

    public function getServerStampProps(): ServerStampProps
    {
        return $this->serverStampProps;
    }

    public function getProtocolIdentifier(): int
    {
        return static::DNSCRYPT_STAMP;
    }

    /**
     * Return the IP address, as a string, with a port number if the server is not accessible
     * over the standard port for the protocol (443).
     * IPv6 strings must be included in square brackets: [fe80::6d6d:f72c:3ad:60b8]. Scopes are permitted.
     * @return string
     */
    public function getAddress(): DnsUpstreamAddress
    {
        return $this->address;
    }

    /**
     * Return  the DNSCrypt provider name.
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }

    /**
     * Return the DNSCrypt provider’s Ed25519 public key, as 32 raw bytes.
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
