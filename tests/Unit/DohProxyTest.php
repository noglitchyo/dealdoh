<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit;

use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use NoGlitchYo\Dealdoh\Dns\Client\DnsClientInterface;
use NoGlitchYo\Dealdoh\Dns\Resolver\DnsResolverInterface;
use NoGlitchYo\Dealdoh\Entity\DnsResource;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Exception\HttpProxyException;
use NoGlitchYo\Dealdoh\Factory\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Helper\UrlSafeBase64CodecHelper;
use NoGlitchYo\Dealdoh\Mapper\HttpResponseMapperInterface;
use NoGlitchYo\Dealdoh\Middleware\DohResolverMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \NoGlitchYo\Dealdoh\Middleware\DohResolverMiddleware
 */
class DohProxyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface|DnsResolverInterface
     */
    private $dnsResolverMock;

    /**
     * @var MockInterface|\NoGlitchYo\Dealdoh\Factory\MessageFactoryInterface
     */
    private $dnsMessageFactoryMock;

    /**
     * @var MockInterface|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var \NoGlitchYo\Dealdoh\Middleware\DohResolverMiddleware
     */
    private $sut;

    /**
     * @var MockInterface|\NoGlitchYo\Dealdoh\Mapper\HttpResponseMapperInterface
     */
    private $dohHttpMessageFactoryMock;

    protected function setUp(): void
    {
        $this->dnsResolverMock = Mockery::mock(DnsResolverInterface::class);
        $this->dnsMessageFactoryMock = Mockery::mock(MessageFactoryInterface::class);
        $this->dohHttpMessageFactoryMock = Mockery::mock(HttpResponseMapperInterface::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->sut = new DohResolverMiddleware(
            $this->dnsResolverMock,
            $this->dnsMessageFactoryMock,
            $this->dohHttpMessageFactoryMock,
            $this->loggerMock
        );

        parent::setUp();
    }

    public function testForwardCreateDnsMessageOnGetRequestWhenQueryParamIsValid(): void
    {
        $base64EncodedDnsRequest = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';

        $requestMock = (new ServerRequest('GET', '/dns-query'))->withQueryParams(
            [
                'dns' => $base64EncodedDnsRequest,
            ]
        );

        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsResponseMessage = Message::createWithDefaultHeader(true);
        $dnsResource = new DnsResource(
            $dnsRequestMessage,
            $dnsResponseMessage,
            new DnsUpstream('upstream'),
            Mockery::mock(DnsClientInterface::class)
        );

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with(UrlSafeBase64CodecHelper::decode($base64EncodedDnsRequest))
            ->andReturn($dnsRequestMessage);

        $this->dnsResolverMock
            ->shouldReceive('resolve')
            ->with($dnsRequestMessage)
            ->andReturn($dnsResource);

        $this->loggerMock
            ->shouldReceive('info')
            ->with(
                "Resolved DNS query with method GET",
                [
                    'dnsRequestMessage' => $dnsRequestMessage,
                    'dnsResponseMessage' => $dnsResponseMessage,
                ]
            );

        $httpResponse = new Response(200);

        $this->dohHttpMessageFactoryMock
            ->shouldReceive('createResponseFromMessage')
            ->with($dnsResource->getResponse())
            ->andReturn($httpResponse);

        $this->assertSame($httpResponse, $this->sut->forward($requestMock));
    }

    public function testForwardLogAndThrowbackExceptionWhenExceptionIsRaised(): void
    {
        $dnsWireMessage = 'wiremessage';
        $requestMock = new ServerRequest('POST', '/dns-query', [], $dnsWireMessage);

        $exception = new Exception('something wrong happened during message creation');

        $this->expectExceptionMessage('DNS message creation failed.');
        $this->expectException(HttpProxyException::class);

        $this->loggerMock
            ->shouldReceive('error')
            ->with(
                sprintf('Failed to create DNS message: %s', $exception->getMessage()),
                [
                'exception' => $exception,
                'httpRequest' => $requestMock
                ]
            );

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with($dnsWireMessage)
            ->andThrow($exception);

        $this->sut->forward($requestMock);
    }

    public function testForwardReturnHttpBadRequestWhenQueryParamIsMissing()
    {
        $requestMock = new ServerRequest('GET', '/dns-query', ['Accept' => 'application/dns-message']);
        $expectedExceptionMessage = 'Query parameter `dns` is mandatory.';

        $this->loggerMock
            ->shouldReceive('error')
            ->with(sprintf('Failed to create DNS message: %s', $expectedExceptionMessage));

        $httpResponse = $this->sut->forward($requestMock);

        $this->assertEquals(400, $httpResponse->getStatusCode());
    }

    public function testForwardCreateDnsMessageOnPostRequest(): void
    {
        $dnsWireMessage = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';

        $requestMock = new ServerRequest(
            'POST',
            '/dns-query',
            [
                'Content-Type' => 'application/dns-message',
            ],
            Stream::create($dnsWireMessage)
        );

        $dnsRequestMessage = Message::createWithDefaultHeader();
        $dnsResponseMessage = Message::createWithDefaultHeader(true);
        $dnsResource = new DnsResource(
            $dnsRequestMessage,
            $dnsResponseMessage,
            new DnsUpstream('udp://upstream:53'),
            Mockery::mock(DnsClientInterface::class)
        );

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with($dnsWireMessage)
            ->andReturn($dnsRequestMessage);

        $this->dnsResolverMock
            ->shouldReceive('resolve')
            ->with($dnsRequestMessage)
            ->andReturn($dnsResource);

        $this->loggerMock
            ->shouldReceive('info')
            ->with(
                "Resolved DNS query with method POST",
                [
                    'dnsRequestMessage' => $dnsRequestMessage,
                    'dnsResponseMessage' => $dnsResponseMessage,
                ]
            );

        $httpResponse = new Response(200);

        $this->dohHttpMessageFactoryMock
            ->shouldReceive('createResponseFromMessage')
            ->with($dnsResponseMessage)
            ->andReturn($httpResponse);

        $this->assertSame($httpResponse, $this->sut->forward($requestMock));
    }

    public function testForwardReturnMethodNotAllowedResponseWhenRequestMethodIsNotSupported(): void
    {
        $dnsWireMessage = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';
        $expectedExceptionMessage = 'Request method is not supported.';

        $requestMock = new ServerRequest(
            'PUT',
            '/dns-query',
            [
                'Content-Type' => 'application/dns-message',
            ],
            Stream::create($dnsWireMessage)
        );

        $this->loggerMock
            ->shouldReceive('error')
            ->with(sprintf('Failed to create DNS message: %s', $expectedExceptionMessage));

        $httpResponse = $this->sut->forward($requestMock);
        $this->assertEquals(405, $httpResponse->getStatusCode());
    }

    public function testForwardLogErrorAndThrowBackExceptionWhenResolveFail()
    {
        $base64EncodedDnsRequest = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';

        $requestMock = (new ServerRequest('GET', '/dns-query'))->withQueryParams(
            [
                'dns' => $base64EncodedDnsRequest,
            ]
        );

        $dnsRequestMessage = Mockery::mock(MessageInterface::class);

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with(UrlSafeBase64CodecHelper::decode($base64EncodedDnsRequest))
            ->andReturn($dnsRequestMessage);

        $exception = new Exception('Resolve failed');

        $this->dnsResolverMock
            ->shouldReceive('resolve')
            ->andThrow($exception);

        $this->loggerMock
            ->shouldReceive('error')
            ->with(
                sprintf('Failed to resolve DNS query: %s', $exception->getMessage()),
                [
                    'exception' => $exception,
                    'dnsRequestMessage' => $dnsRequestMessage,
                ]
            );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Resolving DNS message failed.');

        $this->sut->forward($requestMock);
    }
}
