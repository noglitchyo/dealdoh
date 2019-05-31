<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Tests\Unit\Factory;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use NoGlitchYo\DoDoh\Factory\DnsMessageFactoryInterface;
use NoGlitchYo\DoDoh\Factory\DohHttpMessageFactory;
use NoGlitchYo\DoDoh\Message\DnsMessage;
use NoGlitchYo\DoDoh\Message\Header;
use NoGlitchYo\DoDoh\Message\HeaderInterface;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecord;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\DoDoh\Factory\DohHttpMessageFactory
 */
class DohHttpMessageFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var DnsMessageFactoryInterface|MockInterface */
    private $dnsMessageFactoryMock;

    /** @var DohHttpMessageFactory */
    private $sut;

    protected function setUp(): void
    {
        $this->dnsMessageFactoryMock = Mockery::mock(DnsMessageFactoryInterface::class);

        $this->sut = new DohHttpMessageFactory($this->dnsMessageFactoryMock);
    }

    public function testCreateResponseFromMessageReturnValidHttpDnsMessage(): void
    {
        $dnsMessage = new DnsMessage(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));

        $dnsMessageLength = 10;
        $dnsWireQuery = random_bytes($dnsMessageLength);

        $this->dnsMessageFactoryMock
            ->shouldReceive('createDnsWireMessageFromMessage')
            ->andReturn($dnsWireQuery);

        $expectedResponse = new Response(
            200,
            [
                'Content-Type' => 'application/dns-message',
                'Content-Length' => strlen($dnsWireQuery),
            ],
            Stream::create($dnsWireQuery)
        );

        $response = $this->sut->createResponseFromMessage($dnsMessage);

        $this->assertEquals($expectedResponse->getHeaders(), $response->getHeaders());
        $this->assertEquals((string) $expectedResponse->getBody(), (string) $response->getBody());
    }

    public function testCreateResponseUseLowestTtlFromAnswersForCacheControlHeader(): void
    {
        $dnsMessage = new DnsMessage(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));
        $dnsMessage->addAnswer(new ResourceRecord('answerWithLowestTtl', 1, 1, 20));
        $dnsMessage->addAnswer(new ResourceRecord('answerWithHighestTtl', 1, 1, 60));

        $dnsMessageLength = 10;
        $dnsWireQuery = random_bytes($dnsMessageLength);

        $this->dnsMessageFactoryMock
            ->shouldReceive('createDnsWireMessageFromMessage')
            ->andReturn($dnsWireQuery);

        $expectedResponse = new Response(
            200,
            [
                'Content-Type' => 'application/dns-message',
                'Content-Length' => strlen($dnsWireQuery),
                'Cache-Control' => 'max-age=' . 20
            ],
            Stream::create($dnsWireQuery)
        );
        $response = $this->sut->createResponseFromMessage($dnsMessage);
        $this->assertEquals($expectedResponse->getHeaders(), $response->getHeaders());
        $this->assertEquals((string) $expectedResponse->getBody(), (string) $response->getBody());
    }
}