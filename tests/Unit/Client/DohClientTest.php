<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Client;

use Mockery;
use NoGlitchYo\Dealdoh\Client\DohClient;
use NoGlitchYo\Dealdoh\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\DnsMessageFactoryInterface;
use NoGlitchYo\Dealdoh\Message\DnsMessage;
use NoGlitchYo\Dealdoh\Message\Header;
use NoGlitchYo\Dealdoh\Message\HeaderInterface;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Mockery\MockInterface;

/**
 * @covers \NoGlitchYo\Dealdoh\Client\DohClient
 */
class DohClientTest extends TestCase
{
    /**
     * @var MockInterface|ClientInterface
     */
    private $clientMock;

    /**
     * @var MockInterface|DnsMessageFactoryInterface
     */
    private $dnsMessageFactoryMock;

    /**
     * @var DohClient
     */
    private $sut;

    protected function setUp(): void
    {
        $this->clientMock = Mockery::mock(ClientInterface::class);
        $this->dnsMessageFactoryMock = Mockery::mock(DnsMessageFactoryInterface::class);

        $this->sut = new DohClient($this->clientMock, $this->dnsMessageFactoryMock);

        parent::setUp();
    }

    public function testResolveCreateAndReturnDnsMessage()
    {
        $dnsUpstreamAddr = 'https://some-random-doh-server.com/dns-query';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);
        $dnsRequestMessage = new DnsMessage(
            new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
        );

        $dnsWireRequestMessage = 'somebytesindnswireformat';
        $dnsWireResponseMessage = 'somemorebytesindnswireformat';
        $dnsResponseMessage = new DnsMessage(
            new Header(0, true, 0, false, false, false, false, 0, HeaderInterface::RCODE_OK)
        );
        $httpResponse = new Response(200, [], $dnsWireResponseMessage);

        $this->dnsMessageFactoryMock->shouldReceive('createDnsWireMessageFromMessage')
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

        $this->dnsMessageFactoryMock->shouldReceive('createMessageFromDnsWireMessage')
            ->with($dnsWireResponseMessage)
            ->andReturn($dnsResponseMessage);

        $this->assertEquals($dnsResponseMessage, $this->sut->resolve($dnsUpstream, $dnsRequestMessage));
    }

    public function testSupportsAcceptUpstreamWithHttps()
    {
        $dnsUpstreamAddr = 'https://8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertTrue($this->sut->supports($dnsUpstream));
    }

    public function testSupportsDeclineUpstreamWithOtherSchemeThanHttp()
    {
        $dnsUpstreamAddr = 'udp://8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertFalse($this->sut->supports($dnsUpstream));

        $dnsUpstreamAddr = '8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertFalse($this->sut->supports($dnsUpstream));
    }
}
