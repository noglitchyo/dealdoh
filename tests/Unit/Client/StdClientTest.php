<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Client;

use Mockery;
use Mockery\MockInterface;
use NoGlitchYo\Dealdoh\Client\StdClient;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverUdpTransport;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Tests\Stub\DnsServerStubManager;
use PHPUnit\Framework\TestCase;

class StdClientTest extends TestCase
{
    /**
     * @var MockInterface|MessageFactoryInterface
     */
    private $dnsMessageFactoryMock;

    /**
     * @var StdClient
     */
    private $sut;
    /**
     * @var DnsServerStubManager
     */
    private $dnsServerStubManager;

    /**
     * TODO: these tests should mock the transports layer
     */
    protected function setUp(): void
    {
        $this->dnsMessageFactoryMock = Mockery::mock(MessageFactoryInterface::class);
        $this->dnsServerStubManager = new DnsServerStubManager();
        $this->sut = new StdClient($this->dnsMessageFactoryMock, new DnsOverTcpTransport(), new DnsOverUdpTransport());

        parent::setUp();
    }

    public function testResolveCreateAndReturnDnsMessage()
    {
        $dnsUpstreamAddr = $this->dnsServerStubManager->create();
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $dnsRequestMessage = Message::createWithDefaultHeader();
        $expectedDnsWireRequestMessage = 'somebytesindnswireformat';

        $expectedDnsResponseMessage = Message::createWithDefaultHeader(true);

        $this->dnsMessageFactoryMock->shouldReceive('createDnsWireMessageFromMessage')
            ->with(
                Mockery::on(
                    function (MessageInterface $argument) {
                        // Assert recursion was enabled
                        return $argument->getHeader()->isRd();
                    }
                )
            )
            ->andReturn($expectedDnsWireRequestMessage);

        $this->dnsMessageFactoryMock->shouldReceive('createMessageFromDnsWireMessage')
            ->andReturn($expectedDnsResponseMessage);

        $dnsResponseMessage = $this->sut->resolve($dnsUpstream, $dnsRequestMessage);

        $this->assertEquals($expectedDnsResponseMessage, $dnsResponseMessage);
    }

    public function testSupportsAcceptAllowedUpstreamsFormat()
    {
        $allowedUpstreams = [
           "udp://8.8.8.8:53",
           "8.8.8.8:53",
           "dns://8.8.8.8:53",
        ];

        foreach ($allowedUpstreams as $upstreamAddr) {
            $dnsUpstream = new DnsUpstream($upstreamAddr);

            $this->assertTrue($this->sut->supports($dnsUpstream));
        }
    }

    public function testSupportsDeclineUpstreamWithScheme()
    {
        $dnsUpstreamAddr = 'http://8.8.8.8:53';
        $dnsUpstream = new DnsUpstream($dnsUpstreamAddr);

        $this->assertFalse($this->sut->supports($dnsUpstream));
    }
}
