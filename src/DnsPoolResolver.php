<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh;

use Exception;
use NoGlitchYo\DoDoh\Client\DnsClientInterface;
use NoGlitchYo\DoDoh\Exception\UpstreamNotSupportedException;
use NoGlitchYo\DoDoh\Message\DnsMessageInterface;
use React\Dns\Model\Message;
use Throwable;

class DnsPoolResolver implements DnsResolverInterface
{
    /**
     * @var DnsUpstreamPool
     */
    private $dnsUpstreamPool;

    /**
     * @var DnsClientInterface[]
     */
    private $dnsClients;

    public function __construct(DnsUpstreamPool $dnsUpstreamPool, array $dnsClients = [])
    {
        $this->dnsUpstreamPool = $dnsUpstreamPool;

        foreach ($dnsClients as $dnsClient) {
            $this->addClient($dnsClient);
        }
    }

    public function addClient(DnsClientInterface $dnsClient): self
    {
        $this->dnsClients[] = $dnsClient;

        return $this;
    }

    public function resolve(DnsMessageInterface $dnsMessage): DnsMessageInterface
    {
        foreach ($this->dnsUpstreamPool->getUpstreams() as $dnsUpstream) {
            try {
                $dnsResponse = $this->getClientForUpstream($dnsUpstream)->resolve($dnsUpstream, $dnsMessage);
                if ($dnsResponse->getHeader()->getRcode() === Message::RCODE_NAME_ERROR) {
                    continue; // if upstream did not find it (NXDOMAIN error), we retry with next
                }
                return $dnsResponse;
            } catch (UpstreamNotSupportedException $e) {
                throw $e; // no upstream who can not be handle should be present
            } catch (Throwable $t) {
                continue; // if upstream failed, then we should try with another one
            }
        }

        throw new Exception('Unable to resolve DNS message');
        // TODO: we should handle this scenario correctly
    }

    private function getClientForUpstream(DnsUpstream $dnsUpstream): DnsClientInterface
    {
        foreach ($this->dnsClients as $dnsClient) {
            if ($dnsClient->supports($dnsUpstream)) {
                return $dnsClient;
            }
        }

        throw new UpstreamNotSupportedException($dnsUpstream);
    }
}
