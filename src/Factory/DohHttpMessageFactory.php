<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class DohHttpMessageFactory implements DohHttpMessageFactoryInterface
{
    /**
     * @var MessageFactoryInterface
     */
    private $dnsMessageFactory;

    public function __construct(MessageFactoryInterface $dnsMessageFactory)
    {
        $this->dnsMessageFactory = $dnsMessageFactory;
    }

    public function createResponseFromMessage(MessageInterface $dnsMessage): ResponseInterface
    {
        $dnsWireQuery = $this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsMessage);

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
