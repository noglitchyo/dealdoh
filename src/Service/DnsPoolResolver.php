<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Client\DnsClientInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Exception\DnsPoolResolveFailedException;
use NoGlitchYo\Dealdoh\Exception\UpstreamNotSupportedException;
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

    public function resolve(MessageInterface $dnsMessage): MessageInterface
    {
        foreach ($this->dnsUpstreamPool->getUpstreams() as $dnsUpstream) {
            try {
                $dnsClient = $this->getClientForUpstream($dnsUpstream);
                $dnsResponse = $dnsClient->resolve($dnsUpstream, $dnsMessage);
                if ($dnsResponse->getHeader()->getRcode() === HeaderInterface::RCODE_NAME_ERROR) {
                    // TODO: this behavior should be configurable
                    continue; // if upstream did not find it (NXDOMAIN error), we retry with next
                }
                return $dnsResponse;
            } catch (UpstreamNotSupportedException $e) {
                throw $e; // no upstream who can not be handle by a client should be provided
            } catch (Throwable $t) {
                continue; // if upstream failed, then we should try with another one
            }
        }
        // TODO: we should handle this scenario correctly
        throw new DnsPoolResolveFailedException('Unable to resolve DNS message');
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

    private function addClient(DnsClientInterface $dnsClient): self
    {
        $this->dnsClients[] = $dnsClient;

        return $this;
    }
}
