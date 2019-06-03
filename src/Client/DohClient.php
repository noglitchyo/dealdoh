<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use GuzzleHttp\Psr7\Request;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use Psr\Http\Client\ClientInterface;

class DohClient implements DnsClientInterface
{
    /**
     *
     *
     * @var ClientInterface
     */
    private $client;

    /**
     *
     *
     * @var \NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface
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

        $response = $this->client->sendRequest($request);

        return $this->dnsMessageFactory->createMessageFromDnsWireMessage((string) $response->getBody());
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'https') !== false;
    }
}
