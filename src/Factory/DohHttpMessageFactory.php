<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Factory;

use NoGlitchYo\DoDoh\Message\DnsMessageInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class DohHttpMessageFactory implements DohHttpMessageFactoryInterface
{
    /**
     * @var DnsMessageFactoryInterface
     */
    private $dnsMessageFactory;

    public function __construct(DnsMessageFactoryInterface $dnsMessageFactory)
    {
        $this->dnsMessageFactory = $dnsMessageFactory;
    }

    public function createResponseFromMessage(DnsMessageInterface $dnsMessage): ResponseInterface
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

    private function getMaxAge(DnsMessageInterface $dnsMessage): ?int
    {
        $ttl = [];
        foreach ($dnsMessage->getAnswers() as $rr) {
            $ttl[] = $rr->getTtl();
        }

        return !empty($ttl) ? min($ttl) : null;
    }
}
