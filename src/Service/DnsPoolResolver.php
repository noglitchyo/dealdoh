<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service;

use NoGlitchYo\Dealdoh\Client\DnsClientInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsResource;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPoolInterface;
use NoGlitchYo\Dealdoh\Exception\DnsPoolResolveFailedException;
use NoGlitchYo\Dealdoh\Exception\UpstreamNotSupportedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class DnsPoolResolver implements DnsResolverInterface
{
    /**
     * @var DnsUpstreamPoolInterface
     */
    private $dnsUpstreamPool;

    /**
     * @var DnsClientInterface[]
     */
    private $dnsClients;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DnsUpstreamPoolInterface $dnsUpstreamPool,
        array $dnsClients,
        LoggerInterface $logger = null
    ) {
        $this->dnsUpstreamPool = $dnsUpstreamPool;
        $this->logger = $logger ?? new NullLogger();

        foreach ($dnsClients as $dnsClient) {
            $this->addClient($dnsClient);
        }
    }

    /**
     * @throws DnsPoolResolveFailedException
     * @throws UpstreamNotSupportedException
     */
    public function resolve(MessageInterface $dnsRequest): DnsResource
    {
        if (!$this->dnsUpstreamPool->getUpstreams()) {
            throw DnsPoolResolveFailedException::emptyDnsPool();
        }

        $dnsUpstreams = $this->dnsUpstreamPool->getUpstreams();

        foreach ($dnsUpstreams as $key => $dnsUpstream) { // TODO: the resolve strategy should be configurable
            $dnsClients = $this->getSupportedClientsForUpstream($dnsUpstream);
            if (empty($dnsClients)) {
                throw new UpstreamNotSupportedException($dnsUpstream);
            }

            foreach ($dnsClients as $dnsClient) {
                try {
                    $dnsResponse = $dnsClient->resolve($dnsUpstream, $dnsRequest);
                    if ($dnsResponse->getHeader()->getRcode() === HeaderInterface::RCODE_NAME_ERROR) {
                        break; // Domain is not found on upstream, retry with the next upstream until out of upstreams.
                    }
                    return new DnsResource($dnsRequest, $dnsResponse, $dnsUpstream, $dnsClient);
                } catch (Throwable $t) {
                    $this->logger->warning(
                        "Resolving from client failed:" . $t->getMessage(),
                        [
                            "client" => $dnsClient,
                            "upstream" => $dnsUpstream,
                            "exception" => $t
                        ]
                    );
                    continue; // Retry with the next client until out of clients for the upstream.
                }
            }
        }

        throw DnsPoolResolveFailedException::unableToResolveFromUpstreams($dnsUpstreams);
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return !empty($this->getSupportedClientsForUpstream($dnsUpstream));
    }

    /**
     * @param DnsUpstream $dnsUpstream
     * @return DnsClientInterface[]
     */
    private function getSupportedClientsForUpstream(DnsUpstream $dnsUpstream): array
    {
        $dnsClients = [];

        foreach ($this->dnsClients as $dnsClient) {
            if ($dnsClient->supports($dnsUpstream)) {
                $dnsClients[] = $dnsClient;
            }
        }

        return $dnsClients;
    }

    private function addClient(DnsClientInterface $dnsClient): self
    {
        $this->dnsClients[] = $dnsClient;

        return $this;
    }
}
