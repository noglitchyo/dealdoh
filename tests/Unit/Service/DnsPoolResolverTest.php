<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Service;

use Exception;
use Mockery;
use Mockery\MockInterface;
use NoGlitchYo\Dealdoh\Client\DnsClientInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\DnsResource;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Exception\DnsPoolResolveFailedException;
use NoGlitchYo\Dealdoh\Exception\UpstreamNotSupportedException;
use NoGlitchYo\Dealdoh\Service\DnsPoolResolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\Dealdoh\Service\DnsPoolResolver
 */
class DnsPoolResolverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var DnsClientInterface[]|MockInterface[]
     */
    private $dnsClientsMock;

    /**
     * @var DnsUpstreamPool|MockInterface
     */
    private $dnsUpstreamPool;

    /** @var DnsPoolResolver */
    private $sut;

    protected function setUp(): void
    {
        $this->dnsUpstreamPool = new DnsUpstreamPool();
        $this->dnsClientsMock = [
            'client1' => Mockery::mock(DnsClientInterface::class),
            'client2' => Mockery::mock(DnsClientInterface::class),
        ];

        $this->sut = new DnsPoolResolver($this->dnsUpstreamPool, $this->dnsClientsMock);

        parent::setUp();
    }

    public function testResolveWithEmptyDnsPoolThrowException(): void
    {
        $dnsRequestMessageMock = Message::createWithDefaultHeader();

        $this->expectException(DnsPoolResolveFailedException::class);
        $this->expectExceptionCode(DnsPoolResolveFailedException::EC_CLIENTS_POOL_EMPTY);

        $this->sut->resolve($dnsRequestMessageMock);
    }

    public function testResolveStopAndReturnResponseIfClientSucceedToResolveWithUpstream(): void
    {
        $upstream1 = new DnsUpstream('dns://upstream1:53');
        $upstream2 = new DnsUpstream('dns://upstream2:53');
        $this->dnsUpstreamPool->addUpstream($upstream1);
        $this->dnsUpstreamPool->addUpstream($upstream2);
        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsResponseMessage = Message::createWithDefaultHeader(true);
        $dnsResource = new DnsResource($dnsRequestMessage, $dnsResponseMessage, $upstream1, $this->dnsClientsMock['client1']);

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream1)
                ->andReturn(true);
        }

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldNotReceive('supports')
                ->with($upstream2);
        }

        $this->dnsClientsMock['client1']
            ->shouldReceive('resolve')
            ->with($upstream1, $dnsRequestMessage)
            ->andReturn($dnsResponseMessage);

        $this->dnsClientsMock['client1']
            ->shouldNotReceive('resolve')
            ->with($upstream2, $dnsRequestMessage);

        $this->assertEquals($dnsResource, $this->sut->resolve($dnsRequestMessage));
    }

    public function testResolveRetryWithNextUpstreamIfDomainWasNotFoundWithUpstream(): void
    {
        $upstream1 = new DnsUpstream('dns://upstream1:53');
        $upstream2 = new DnsUpstream('dns://upstream2:53');
        $this->dnsUpstreamPool->addUpstream($upstream1);
        $this->dnsUpstreamPool->addUpstream($upstream2);
        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsResponseMessageOk = Message::createWithDefaultHeader(true);
        $dnsResponseMessageNameError = Message::createWithDefaultHeader(true, HeaderInterface::RCODE_NAME_ERROR);
        $dnsResource = new DnsResource($dnsRequestMessage, $dnsResponseMessageOk, $upstream2, $this->dnsClientsMock['client1']);

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream1)
                ->andReturn(true);
        }

        $this->dnsClientsMock['client1']
            ->shouldReceive('resolve')
            ->with($upstream1, $dnsRequestMessage)
            ->andReturn($dnsResponseMessageNameError);

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream2)
                ->andReturn(true);
        }

        $this->dnsClientsMock['client1']
            ->shouldReceive('resolve')
            ->with($upstream2, $dnsRequestMessage)
            ->andReturn($dnsResponseMessageOk);

        $this->assertEquals($dnsResource, $this->sut->resolve($dnsRequestMessage));
    }


    public function testResolveRetryWithNextUpstreamIfClientFailedToResolve(): void
    {
        $upstream1 = new DnsUpstream('dns://upstream1:53');
        $upstream2 = new DnsUpstream('dns://upstream2:53');
        $this->dnsUpstreamPool->addUpstream($upstream1);
        $this->dnsUpstreamPool->addUpstream($upstream2);
        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsResponseMessage = Message::createWithDefaultHeader(true);
        $dnsResource = new DnsResource($dnsRequestMessage, $dnsResponseMessage, $upstream2, $this->dnsClientsMock['client1']);

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream1)
                ->andReturn(true);
        }

        $this->dnsClientsMock['client1']
            ->shouldReceive('resolve')
            ->with($upstream1, $dnsRequestMessage)
            ->andThrow(Exception::class);

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream2)
                ->andReturn(true);
        }

        $this->dnsClientsMock['client1']
            ->shouldReceive('resolve')
            ->with($upstream2, $dnsRequestMessage)
            ->andReturn($dnsResponseMessage);

        $this->assertEquals($dnsResource, $this->sut->resolve($dnsRequestMessage));
    }

    public function testResolveThrowExceptionIfAllClientsFailedToResolveUpstreams(): void
    {
        $upstream1 = new DnsUpstream('dns://upstream1:53');
        $upstream2 = new DnsUpstream('dns://upstream2:53');
        $this->dnsUpstreamPool->addUpstream($upstream1);
        $this->dnsUpstreamPool->addUpstream($upstream2);
        $dnsRequestMessage = Message::createWithDefaultHeader();

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream1)
                ->andReturn(true);

            $dnsClient
                ->shouldReceive('resolve')
                ->with($upstream1, $dnsRequestMessage)
                ->andThrow(Exception::class);

            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream2)
                ->andReturn(true);

            $dnsClient
                ->shouldReceive('resolve')
                ->with($upstream2, $dnsRequestMessage)
                ->andThrow(Exception::class);
        }

        $this->expectException(DnsPoolResolveFailedException::class);
        $this->expectExceptionCode(DnsPoolResolveFailedException::EC_UPSTREAMS_FAILED);

        $this->sut->resolve($dnsRequestMessage);
    }

    public function testResolveThrowExceptionIfNoClientCanHandleUpstream(): void
    {
        $upstream1 = new DnsUpstream('dns://upstream1:53');
        $this->dnsUpstreamPool->addUpstream($upstream1);

        $dnsRequestMessage = Message::createWithDefaultHeader();

        foreach ($this->dnsClientsMock as $dnsClient) {
            $dnsClient
                ->shouldReceive('supports')
                ->with($upstream1)
                ->andReturn(false);
        }

        $this->expectException(UpstreamNotSupportedException::class);

        $this->sut->resolve($dnsRequestMessage);
    }
}
