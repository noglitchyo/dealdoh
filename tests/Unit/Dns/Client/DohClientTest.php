<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Dns\Client;

use Exception;
use Mockery;
use Mockery\MockInterface;
use NoGlitchYo\Dealdoh\Dns\Client\DohClient;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Exception\DnsClientException;
use NoGlitchYo\Dealdoh\Factory\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Mapper\MessageMapperInterface;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @covers \NoGlitchYo\Dealdoh\Dns\Client\DohClient
 */
class DohClientTest extends TestCase
{
    /**
     * @var MockInterface|ClientInterface
     */
    private $clientMock;

    /**
     * @var MockInterface|\NoGlitchYo\Dealdoh\Factory\MessageFactoryInterface
     */
    private $messageMapperMock;

    /**
     * @var DohClient
     */
    private $sut;

    protected function setUp(): void
    {
        $this->clientMock        = Mockery::mock(ClientInterface::class);
        $this->messageMapperMock = Mockery::mock(MessageMapperInterface::class);

        $this->sut = new DohClient($this->clientMock, $this->messageMapperMock);

        parent::setUp();
    }

    public function testResolveCreateAndReturnDnsMessage(): void
    {
        $dnsUpstreamAddr = 'https://some-random-doh-server.com/dns-query';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);
        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsWireRequestMessage = 'somebytesindnswireformat';
        $dnsWireResponseMessage = 'somemorebytesindnswireformat';
        $dnsResponseMessage = Message::createWithDefaultHeader(true);
        $httpResponse = new Response(200, [], $dnsWireResponseMessage);

        $this->messageMapperMock->shouldReceive('createDnsWireMessageFromMessage')
            ->with($dnsRequestMessage)
            ->andReturn($dnsWireRequestMessage);

        $this->clientMock->shouldReceive('sendRequest')
            ->with(
                Mockery::on(
                    function (RequestInterface $request) use ($dnsUpstream, $dnsWireRequestMessage) {
                        return (string)$request->getUri() === $dnsUpstream->getUri()
                            && $request->getHeaderLine('Content-Type') === 'application/dns-message'
                            && (int)$request->getHeaderLine('Content-Length') === strlen($dnsWireRequestMessage);
                    }
                )
            )
            ->andReturn($httpResponse);

        $this->messageMapperMock->shouldReceive('createMessageFromDnsWireMessage')
            ->with($dnsWireResponseMessage)
            ->andReturn($dnsResponseMessage);

        $this->assertEquals($dnsResponseMessage, $this->sut->query($dnsUpstream, $dnsRequestMessage));
    }

    public function testResolveThrowDnsClientExceptionWhenSendingRequestFailed(): void
    {
        $dnsUpstreamAddr = 'https://some-random-doh-server.com/dns-query';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);
        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsWireRequestMessage = 'somebytesindnswireformat';
        $dnsWireResponseMessage = 'somemorebytesindnswireformat';
        $dnsResponseMessage = Message::createWithDefaultHeader(true);

        $this->messageMapperMock->shouldReceive('createDnsWireMessageFromMessage')
            ->with($dnsRequestMessage)
            ->andReturn($dnsWireRequestMessage);

        $this->clientMock->shouldReceive('sendRequest')
            ->with(Mockery::type(RequestInterface::class))
            ->andThrow(Exception::class);

        $this->expectException(DnsClientException::class);
        $this->expectExceptionMessage(sprintf('Failed to send the request to DoH upstream `%s`', $dnsUpstreamAddr));

        $this->assertEquals($dnsResponseMessage, $this->sut->query($dnsUpstream, $dnsRequestMessage));
    }

    public function testSupportsAcceptUpstreamWithHttps(): void
    {
        $dnsUpstreamAddr = 'https://8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertTrue($this->sut->supports($dnsUpstream));
    }

    public function testSupportsDeclineUpstreamWithOtherSchemeThanHttp(): void
    {
        $dnsUpstreamAddr = 'udp://8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertFalse($this->sut->supports($dnsUpstream));

        $dnsUpstreamAddr = '8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertFalse($this->sut->supports($dnsUpstream));
    }
}
