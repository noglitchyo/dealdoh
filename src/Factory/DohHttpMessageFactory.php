<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Factory;

use NoGlitchYo\DoDoh\Message\DnsMessageInterface;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecord;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecordInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class DohHttpMessageFactory
{
    /**
     * @var DnsMessageFactory
     */
    private $dnsMessageFactory;

    public function __construct(DnsMessageFactory $dnsMessageFactory)
    {
        $this->dnsMessageFactory = $dnsMessageFactory;
    }

    public function createResponseFromMessage(DnsMessageInterface $dnsMessage): ResponseInterface
    {
        $dnsWireQuery = $this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsMessage);

        return new Response(
            200,
            [
                'Content-Type' => 'application/dns-message',
                'Content-Length' => strlen($dnsWireQuery),
                'Cache-Control' => 'max-age=' . $this->getMaxAge($dnsMessage)
            ],
            $dnsWireQuery
        );
    }

    public function getMaxAge(DnsMessageInterface $dnsMessage): int
    {
        $ttl = [];
        foreach ($dnsMessage->getAnswers() as $rr){
            $ttl[] = $rr->getTtl();
        }

        return min($ttl);
    }
}
