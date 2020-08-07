<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

/**
 * Standard port for DNSCrypt upstream is 443.
 * If not, port is provided in the URI.
 */
class DnsCryptUpstream extends DnsUpstream
{
    public const TYPE = 'DNSCRYPT_UPSTREAM';

    /**
     * @var string
     */
    private $providerName;

    /**
     * @var string
     */
    private $providerPublicKey;

    /**
     * @param DnsUpstreamAddress $dnsUpstreamAddress
     * @param string             $providerName
     * @param string             $providerPublicKey
     * @param string|null        $code
     */
    public function __construct(
        DnsUpstreamAddress $dnsUpstreamAddress,
        string $providerName,
        string $providerPublicKey,
        ?string $code = null
    ) {
        parent::__construct($dnsUpstreamAddress, $code);
        $this->providerName = $providerName;
        $this->providerPublicKey = $providerPublicKey;
    }

    /**
     * Default port for DnsCrypt upstream is 443
     * @return int|null
     */
    public function getPort(): ?int
    {
        return parent::getPort() ?? 443;
    }

    public function getProviderName()
    {
        return $this->providerName;
    }

    public function getProviderPublicKey()
    {
        return $this->providerPublicKey;
    }

    public static function getType()
    {
        return static::TYPE;
    }
}
