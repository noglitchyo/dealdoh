<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Exception\DnsClientException;
use NoGlitchYo\Dealdoh\Mapper\GoogleDns\MessageMapper;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Throwable;

/**
 * Google DOH API client
 * https://developers.google.com/speed/public-dns/docs/dns-over-https
 */
class GoogleDnsClient implements DnsClientInterface
{
    private const API_URI = 'https://dns.google.com/resolve';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var MessageMapper
     */
    private $messageMapper;

    public function __construct(ClientInterface $client, MessageMapper $messageMapper)
    {
        $this->client = $client;
        $this->messageMapper = $messageMapper;
    }

    public function query(DnsUpstreamInterface $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        // TODO: we should make as much query as entries there is in the question
        $uri = (new Uri(self::API_URI));
        $query = $dnsRequestMessage->getQuestion()[0];

        if (strlen($query->getQname()) > 253 || strlen($query->getQname()) < 1) {
            throw new InvalidArgumentException('Query name length must be between 1 and 253');
        }

        if ($query->getQtype() < 1 || $query->getQtype() > 65535) {
            throw new InvalidArgumentException('Query type must be in range [1, 65535]');
        }

        $uri = $uri->withQuery(
            http_build_query(
                [
                'name' => $query->getQname(),
                'type' => $query->getQtype()
                ]
            )
        );

        try {
            $response = $this->client->sendRequest(new Request('GET', $uri));
        } catch (Throwable $throwable) {
            throw new DnsClientException('Failed to send the request to Google DNS API', 0, $throwable);
        }

        try {
            $message = $this->messageMapper->map(json_decode((string) $response->getBody(), true));
        } catch (Throwable $throwable) {
            throw new DnsClientException('Failed to map the response from Google DNS API', 0, $throwable);
        }

        return $message;
    }

    public function supports(DnsUpstreamInterface $dnsUpstream): bool
    {
        return $dnsUpstream->getUri() === self::API_URI;
    }
}
