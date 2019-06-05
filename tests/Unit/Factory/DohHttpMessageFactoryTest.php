<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Factory;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactory
 */
class DohHttpMessageFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface|MockInterface */
    private $dnsMessageFactoryMock;

    /** @var DohHttpMessageFactory */
    private $sut;

    protected function setUp(): void
    {
        $this->dnsMessageFactoryMock = Mockery::mock(\NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface::class);

        $this->sut = new DohHttpMessageFactory($this->dnsMessageFactoryMock);
    }

    public function testCreateResponseFromMessageReturnValidHttpDnsMessage(): void
    {
        $dnsMessage = new Message(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));

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
        $dnsMessage = new Message(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));
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
