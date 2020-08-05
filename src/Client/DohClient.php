<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Exception\DnsClientException;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Throwable;

/**
 * DoH client making DNS query as described in RFC-8484
 */
class DohClient implements DnsClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var MessageFactoryInterface
     */
    private $dnsMessageFactory;

    public function __construct(ClientInterface $client, MessageFactoryInterface $dnsMessageFactory)
    {
        $this->client = $client;
        $this->dnsMessageFactory = $dnsMessageFactory;
    }

    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        $dnsMessage = $this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);

        // TODO: should follow recommendations from https://tools.ietf.org/html/rfc8484#section-5.1 about cache
        $request = new Request(
            'POST',
            $dnsUpstream->getUri(),
            [
                'Content-Type' => 'application/dns-message',
                'Content-Length' => strlen($dnsMessage)
            ],
            $dnsMessage
        );

        try {
            $response = $this->client->sendRequest($request);
        } catch (Throwable $throwable) {
            throw new DnsClientException(
                sprintf('Failed to send the request to DoH upstream `%s`', $dnsUpstream->getUri()),
                0,
                $throwable
            );
        }

        return $this->dnsMessageFactory->createMessageFromDnsWireMessage((string)$response->getBody());
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'https') !== false;
    }
}
