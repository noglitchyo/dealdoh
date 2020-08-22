<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper;

use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class HttpResponseMapper implements HttpResponseMapperInterface
{
    /**
     * @var MessageMapperInterface
     */
    private $messageMapper;

    public function __construct(MessageMapperInterface $messageMapper)
    {
        $this->messageMapper = $messageMapper;
    }

    public function createResponseFromMessage(MessageInterface $dnsMessage): ResponseInterface
    {
        $dnsWireQuery = $this->messageMapper->createDnsWireMessageFromMessage($dnsMessage);

        $headers = [
            'Content-Type' => 'application/dns-message',
            'Content-Length' => strlen($dnsWireQuery),
        ];

        $maxAge = $this->getMaxAge($dnsMessage);
        if ($maxAge !== null) {
            $headers['Cache-Control'] = 'max-age=' . $maxAge;
        }

        return new Response(
            200,
            $headers,
            $dnsWireQuery
        );
    }

    private function getMaxAge(MessageInterface $dnsMessage): ?int
    {
        $ttl = [];
        foreach ($dnsMessage->getAnswer() as $rr) {
            $ttl[] = $rr->getTtl();
        }

        return !empty($ttl) ? min($ttl) : null;
    }
}
