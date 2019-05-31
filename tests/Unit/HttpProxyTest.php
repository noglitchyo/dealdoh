<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Tests\Unit;

use Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use NoGlitchYo\DoDoh\DnsResolverInterface;
use NoGlitchYo\DoDoh\Factory\DnsMessageFactoryInterface;
use NoGlitchYo\DoDoh\Factory\DohHttpMessageFactoryInterface;
use NoGlitchYo\DoDoh\HttpProxy;
use NoGlitchYo\DoDoh\Message\DnsMessageInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \NoGlitchYo\DoDoh\HttpProxy
 */
class HttpProxyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface|DnsResolverInterface
     */
    private $dnsResolverMock;

    /**
     * @var MockInterface|DnsMessageFactoryInterface
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
        $this->dnsMessageFactoryMock = Mockery::mock(DnsMessageFactoryInterface::class);
        $this->dohHttpMessageFactoryMock = Mockery::mock(DohHttpMessageFactoryInterface::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->sut = new HttpProxy($this->dnsResolverMock, $this->dnsMessageFactoryMock, $this->dohHttpMessageFactoryMock, $this->loggerMock);

        parent::setUp();
    }

    public function testForwardCreateDnsMessageOnGetRequestWhenQueryParamIsValid()
    {
        $base64EncodedDnsRequest = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';

        $requestMock = (new ServerRequest('GET', '/dns-query'))->withQueryParams([
            'dns' => $base64EncodedDnsRequest
        ]);

        $dnsRequestMessage = Mockery::mock(DnsMessageInterface::class);
        $dnsResponseMessage = Mockery::mock(DnsMessageInterface::class);

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromBase64')
            ->with($base64EncodedDnsRequest)
            ->andReturn($dnsRequestMessage);

        $this->dnsResolverMock
            ->shouldReceive('resolve')
            ->with($dnsRequestMessage)
            ->andReturn($dnsResponseMessage);

        $this->loggerMock
            ->shouldReceive('info')
            ->with("Resolved DNS query with method GET", [
                'dnsRequestMessage' => $dnsRequestMessage,
                'dnsResponseMessage' => $dnsResponseMessage
            ]);

        $httpResponse = new Response(200);

        $this->dohHttpMessageFactoryMock
            ->shouldReceive('createResponseFromMessage')
            ->with($dnsResponseMessage)
            ->andReturn($httpResponse);

        $this->assertSame($httpResponse, $this->sut->forward($requestMock));
    }

    public function testForwardThrowExceptionOnGetRequestWhenQueryParamIsInvalid()
    {
        $invalidBase64Request = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';

        $requestMock = (new ServerRequest('GET', '/dns-query'))->withQueryParams([
            'dns' => $invalidBase64Request
        ]);

        $exception = new Exception('dns query is malformated');

        $this->expectExceptionMessage($exception->getMessage());

        $this->loggerMock
            ->shouldReceive('error')
            ->with(sprintf('Failed to create DNS message: %s', $exception->getMessage()));

        $this->dnsMessageFactoryMock
            ->shouldReceive('createMessageFromBase64')
            ->with($invalidBase64Request)
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

    public function testForwardCreateDnsMessageOnPostRequest()
    {
        $dnsWireMessage = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';

        $requestMock = new ServerRequest(
            'POST',
            '/dns-query',
            [
                'Content-Type' => 'application/dns-message'
            ],
            Stream::create($dnsWireMessage)
        );

        $dnsRequestMessage = Mockery::mock(DnsMessageInterface::class);
        $dnsResponseMessage = Mockery::mock(DnsMessageInterface::class);

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
            ->with("Resolved DNS query with method POST", [
                'dnsRequestMessage' => $dnsRequestMessage,
                'dnsResponseMessage' => $dnsResponseMessage
            ]);

        $httpResponse = new Response(200);

        $this->dohHttpMessageFactoryMock
            ->shouldReceive('createResponseFromMessage')
            ->with($dnsResponseMessage)
            ->andReturn($httpResponse);

        $this->assertSame($httpResponse, $this->sut->forward($requestMock));
    }

    public function testForwardThrowExceptionWhenRequestMethodIsNotSupported()
    {
        $dnsWireMessage = 'AAABAAABAAAAAAABA3NzbAdnc3RhdGljA2NvbQAAAQABAAApEAAAAAAAAAgACAAEAAEAAA';
        $expectedExceptionMessage = 'Request method is not supported.';

        $requestMock = new ServerRequest(
            'PUT',
            '/dns-query',
            [
                'Content-Type' => 'application/dns-message'
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
}
