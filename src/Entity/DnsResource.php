<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use NoGlitchYo\Dealdoh\Dns\Client\DnsClientInterface;

/**
 * A DNS Resource is a DNS request message which has been resolved through a DNS upstream with a DNS client and for
 * which a DNS response message has been created.
 *
 * @codeCoverageIgnore
 */
class DnsResource
{
    /**
     * @var MessageInterface
     */
    private $dnsRequest;

    /**
     * @var MessageInterface
     */
    private $dnsResponse;

    /**
     * @var DnsUpstreamInterface
     */
    private $dnsUpstream;

    /**
     * @var DnsClientInterface
     */
    private $dnsClient;

    /**
     * @param MessageInterface     $dnsRequest  The query for this DNS resource
     * @param MessageInterface     $dnsResponse The response for this DNS resource
     * @param DnsUpstreamInterface $dnsUpstream The DNS upstream which was queried
     * @param DnsClientInterface   $dnsClient   The DNS client which was used to execute the query
     */
    public function __construct(
        MessageInterface $dnsRequest,
        MessageInterface $dnsResponse,
        DnsUpstreamInterface $dnsUpstream,
        DnsClientInterface $dnsClient
    ) {
        $this->dnsRequest = $dnsRequest;
        $this->dnsResponse = $dnsResponse;
        $this->dnsUpstream = $dnsUpstream;
        $this->dnsClient = $dnsClient;
    }

    public function getRequest(): MessageInterface
    {
        return $this->dnsRequest;
    }

    public function getResponse(): MessageInterface
    {
        return $this->dnsResponse;
    }

    public function getUpstream(): DnsUpstreamInterface
    {
        return $this->dnsUpstream;
    }

    public function getClient(): DnsClientInterface
    {
        return $this->dnsClient;
    }
}
