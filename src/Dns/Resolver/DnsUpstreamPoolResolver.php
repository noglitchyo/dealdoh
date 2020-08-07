<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Resolver;

use NoGlitchYo\Dealdoh\Dns\Client\DnsClientInterface;
use NoGlitchYo\Dealdoh\Entity\DnsResource;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPoolInterface;
use NoGlitchYo\Dealdoh\Entity\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Exception\DnsUpstreamPoolResolveFailedException;
use NoGlitchYo\Dealdoh\Exception\UpstreamNotSupportedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Allow to resolve a DNS query through a `pool` of resolvers.
 * A pool of resolver is represented by a resolver which implements DnsResolverInterface and wraps multiple
 * DnsResolverInterface.
 * Resolvers in the pool are picked one by one until one successfully resolves the query.
 */
class DnsUpstreamPoolResolver implements DnsResolverInterface
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
     * For each upstream, check if there is compatible DNS clients and attempt to resolve the query with each of them
     * until success.
     *
     * @param MessageInterface $dnsRequest
     *
     * @return DnsResource
     * @throws DnsUpstreamPoolResolveFailedException
     * @throws UpstreamNotSupportedException
     */
    public function resolve(MessageInterface $dnsRequest): DnsResource
    {
        if (!$this->dnsUpstreamPool->getUpstreams()) {
            throw DnsUpstreamPoolResolveFailedException::emptyDnsPool();
        }

        $dnsUpstreams = $this->dnsUpstreamPool->getUpstreams();

        foreach ($dnsUpstreams as $key => $dnsUpstream) { // TODO: the resolve strategy should be configurable
            $compatibleDnsClients = $this->getSupportedClientsForUpstream($dnsUpstream);
            if (empty($compatibleDnsClients)) {
                throw new UpstreamNotSupportedException($dnsUpstream);
            }

            foreach ($compatibleDnsClients as $dnsClient) {
                try {
                    $dnsResponse = $dnsClient->query($dnsUpstream, $dnsRequest);
                    if ($dnsResponse->getHeader()->getRcode() === HeaderInterface::RCODE_NAME_ERROR) {
                        $this->logger->info(
                            sprintf('DNS query could not be resolved with upstream `%s`', $dnsUpstream->getCode())
                        );
                        break; // DNS query could not be resolved, retry with the next upstream until out of upstreams.
                    }
                    return new DnsResource($dnsRequest, $dnsResponse, $dnsUpstream, $dnsClient);
                } catch (Throwable $t) {
                    $this->logger->warning(
                        "Resolving from client failed:" . $t->getMessage(),
                        [
                            "client"    => $dnsClient,
                            "upstream"  => $dnsUpstream,
                            "exception" => $t,
                        ]
                    );
                    continue; // Retry with the next client until out of clients for the upstream.
                }
            }
        }

        throw DnsUpstreamPoolResolveFailedException::unableToResolveFromUpstreams($dnsUpstreams);
    }

    /**
     * @param DnsUpstreamInterface $dnsUpstream
     *
     * @return DnsClientInterface[]
     */
    private function getSupportedClientsForUpstream(DnsUpstreamInterface $dnsUpstream): array
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
