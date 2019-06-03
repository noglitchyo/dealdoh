<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit;

use Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use NoGlitchYo\Dealdoh\Service\DnsResolverInterface;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactoryInterface;
use NoGlitchYo\Dealdoh\Helper\Base64UrlCodecHelper;
use NoGlitchYo\Dealdoh\HttpProxy;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \NoGlitchYo\Dealdoh\HttpProxy
 */
class HttpProxyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface|DnsResolverInterface
     */
    private $dnsResolverMock;

    /**
     * @var MockInterface|\NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface
     */
    private $dnsMessageFactoryMock;

    /**
     * @var MockInterface|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var HttpProxy
     */
    private $sut;

    /**
     * @var MockInterface|DohHttpMessageFactoryInterface
     */
    private $dohHttpMessageFactoryMock;

    protected function setUp(): void
    {
        $this->dnsResolverMock = Mockery::mock(DnsResolverInterface::class);
        $this->dnsMessageFactoryMock = Mockery::mock(MessageFactoryInterface::class);
        $this->dohHttpMessageFactoryMock = Mockery::mock(DohHttpMessageFactoryInterface::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->sut = new HttpProxy(
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

        $dnsRequestMessage = Mockery::mock(MessageInterface::class);
        $dnsResponseMessage = Mockery::mock(MessageInterface::class);

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with(Base64UrlCodecHelper::decode($base64EncodedDnsRequest))
            ->andReturn($dnsRequestMessage);

        $this->dnsResolverMock
            ->shouldReceive('resolve')
            ->with($dnsRequestMessage)
            ->andReturn($dnsResponseMessage);

        $this->loggerMock
            ->shouldReceive('info')
            ->with(
                "Resolved DNS query with method GET",
                [
                    'dnsRequestMessage'  => $dnsRequestMessage,
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

    public function testForwardThrowExceptionOnGetRequestWhenQueryParamIsInvalid(): void
    {
        $invalidBase64Request = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA***+++()()()';

        $requestMock = (new ServerRequest('GET', '/dns-query'))->withQueryParams(
            [
                'dns' => $invalidBase64Request,
            ]
        );

        $exception = new Exception('dns query is malformated');

        $this->expectExceptionMessage($exception->getMessage());

        $this->loggerMock
            ->shouldReceive('error')
            ->with(sprintf('Failed to create DNS message: %s', $exception->getMessage()));

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with(Base64UrlCodecHelper::decode($invalidBase64Request))
            ->andThrow($exception);

        $this->sut->forward($requestMock);
    }

    public function testForwardThrowExceptionOnGetRequestWhenQueryParamIsMissing()
    {
        $requestMock = new ServerRequest('GET', '/dns-query', ['Accept' => 'application/dns-message']);
        $expectedExceptionMessage = 'Query parameter `dns` is mandatory.';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->loggerMock
            ->shouldReceive('error')
            ->with(sprintf('Failed to create DNS message: %s', $expectedExceptionMessage));

        $this->sut->forward($requestMock);
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

        $dnsRequestMessage = Mockery::mock(MessageInterface::class);
        $dnsResponseMessage = Mockery::mock(MessageInterface::class);

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromDnsWireMessage')
            ->with($dnsWireMessage)
            ->andReturn($dnsRequestMessage);

        $this->dnsResolverMock
            ->shouldReceive('resolve')
            ->with($dnsRequestMessage)
            ->andReturn($dnsResponseMessage);

        $this->loggerMock
            ->shouldReceive('info')
            ->with(
                "Resolved DNS query with method POST",
                [
                    'dnsRequestMessage'  => $dnsRequestMessage,
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

    public function testForwardThrowExceptionWhenRequestMethodIsNotSupported(): void
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->loggerMock
            ->shouldReceive('error')
            ->with(sprintf('Failed to create DNS message: %s', $expectedExceptionMessage));


        $this->sut->forward($requestMock);
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
            ->with(Base64UrlCodecHelper::decode($base64EncodedDnsRequest))
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
                    'dnsRequestMessage'  => $dnsRequestMessage,
                ]
            );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Resolve failed');

        $this->sut->forward($requestMock);
    }
}
