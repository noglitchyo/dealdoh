<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsStamp\DnsCryptServerStamp;
use NoGlitchYo\Dealdoh\Entity\DnsStamp\DohServerStamp;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamAddress;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use Throwable;

class DnsUpstreamFactory
{
    /**
     * @var ServerStampFactory
     */
    private $stampFactory;

    public function __construct()
    {
        $this->stampFactory = new ServerStampFactory();
    }

    /**
     * Create a DNS upstream from the provided URI with the given $code.
     * The URI can be a standard URI or a DNS stamp.
     *
     * @see https://dnscrypt.info/stamps-specifications
     *
     * @param string      $dnsUpstreamUri
     * @param string|null $code
     *
     * @return DnsUpstreamInterface
     * @throws Exception
     */
    public function create(string $dnsUpstreamUri, string $code = null): DnsUpstreamInterface
    {
        if (!$this->stampFactory::isDnsStamp($dnsUpstreamUri)) {
            return new DnsUpstream(
                DnsUpstreamAddress::create($dnsUpstreamUri),
                $code
            );
        }

        try {
            $stamp = $this->stampFactory->create($dnsUpstreamUri);
        } catch (Throwable $t) {
            throw new Exception('DNS stamp was provided but can not be parsed:' . $t->getMessage(), 0, $t);
        }

        if ($stamp instanceof DohServerStamp) {
            return new DnsUpstream($stamp->getAddress(), $code);
        }
        if ($stamp instanceof DnsCryptServerStamp) {
            return new DnsCryptUpstream($stamp->getAddress(), $stamp->getProviderName(), $stamp->getPublicKey(), $code);
        }
    }
}
