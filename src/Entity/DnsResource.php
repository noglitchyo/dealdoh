<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use NoGlitchYo\Dealdoh\Client\DnsClientInterface;

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
     * @var DnsUpstream
     */
    private $dnsUpstream;
    /**
     * @var DnsClientInterface
     */
    private $dnsClient;

    public function __construct(
        MessageInterface $dnsRequest,
        MessageInterface $dnsResponse,
        DnsUpstream $dnsUpstream,
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

    public function getUpstream(): DnsUpstream
    {
        return $this->dnsUpstream;
    }

    public function getClient(): DnsClientInterface
    {
        return $this->dnsClient;
    }
}
