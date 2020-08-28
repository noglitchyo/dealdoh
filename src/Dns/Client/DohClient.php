<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream\DohUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Exception\DnsClientException;
use NoGlitchYo\Dealdoh\Mapper\MessageMapperInterface;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
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
     * @var MessageMapperInterface
     */
    private $messageMapper;

    public function __construct(
        ClientInterface $client,
        MessageMapperInterface $messageMapper
    ) {
        $this->client = $client;
        $this->messageMapper = $messageMapper;
    }

    public function query(DnsUpstreamInterface $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        if (!$dnsUpstream instanceof DohUpstream) {
            throw new InvalidArgumentException('Upstream must be an instance of ' . DohUpstream::class);
        }

        $dnsMessage = $this->messageMapper->createDnsWireMessageFromMessage($dnsRequestMessage);

        // TODO: should follow recommendations from https://tools.ietf.org/html/rfc8484#section-5.1 about cache
        $request = new Request( // TODO: remove this later, since SNI can not be transmit with guzzle
            'POST',
            'https://' . $dnsUpstream->getHost() . $dnsUpstream->getPath(),
            [
                'Content-Type'   => 'application/dns-message',
                'Content-Length' => strlen($dnsMessage),
            ],
            $dnsMessage
        );

        try {
            $response = @file_get_contents(
                (string) $request->getUri(),
                false,
                $this->createContextFromRequest($request, $dnsUpstream)
            );
        } catch (Throwable $throwable) {
            throw new DnsClientException(
                sprintf('Failed to send the request to DoH upstream `%s`', $dnsUpstream->getUri()),
                0,
                $throwable
            );
        }

        return $this->messageMapper->createMessageFromDnsWireMessage($response);
    }

    private function createContextFromRequest(RequestInterface $request, DnsUpstreamInterface $dnsUpstream)
    {
        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ": " . $request->getHeaderLine($name) . "\r\n";
        }

        return stream_context_create(
            [
                'http' => [
                    'method'  => $request->getMethod(),
                    'header'  => implode("", $headers),
                    'content' => (string)$request->getBody(),
                ],
                'ssl'  => [
                    'peer_name' => $dnsUpstream->getSNI(),
                ],
            ]
        );
    }

    public function supports(DnsUpstreamInterface $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'https') !== false ||
            $dnsUpstream::getType() === DohUpstream::TYPE;
    }
}
