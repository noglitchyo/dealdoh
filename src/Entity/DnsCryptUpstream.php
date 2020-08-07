<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

class DnsCryptUpstream extends DnsUpstream
{
    /**
     * @var string
     */
    private $providerName;
    /**
     * @var string
     */
    private $providerPublicKey;

    public function __construct(string $uri, string $providerName, string $providerPublicKey)
    {
        parent::__construct($uri);
        $this->providerName = $providerName;
        $this->providerPublicKey = $providerPublicKey;
    }

    public function getProviderName()
    {
        return $this->providerName;
    }

    public function getProviderPublicKey()
    {
        return $this->providerPublicKey;
    }
}
