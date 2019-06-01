<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh;

use Exception;
use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Factory\DnsMessageFactory;
use NoGlitchYo\Dealdoh\Factory\DnsMessageFactoryInterface;
use NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactoryInterface;
use NoGlitchYo\Dealdoh\Helper\Base64UrlCodecHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class HttpProxy
{
    /**
     * @var DnsResolverInterface
     */
    private $dnsResolver;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var DnsMessageFactoryInterface
     */
    private $dnsMessageFactory;
    /**
     * @var DohHttpMessageFactoryInterface
     */
    private $dohHttpMessageFactory;

    public function __construct(
        DnsResolverInterface $dnsResolver,
        DnsMessageFactoryInterface $dnsMessageFactory,
        DohHttpMessageFactoryInterface $dohHttpMessageFactory,
        LoggerInterface $logger = null
    ) {
        $this->dnsResolver = $dnsResolver;
        $this->logger = $logger ?? new NullLogger();
        $this->dnsMessageFactory = $dnsMessageFactory;
        $this->dohHttpMessageFactory = $dohHttpMessageFactory;
    }

    public function forward(ServerRequestInterface $serverRequest): ResponseInterface
    {
        try {
            switch ($serverRequest->getMethod()) {
                case 'GET':
                    $dnsQuery = $serverRequest->getQueryParams()['dns'] ?? null;
                    if (!$dnsQuery) {
                        throw new InvalidArgumentException('Query parameter `dns` is mandatory.');
                    }
                    $dnsRequestMessage = $this->dnsMessageFactory->createMessageFromDnsWireMessage(
                        Base64UrlCodecHelper::decode($dnsQuery)
                    );
                    break;
                case 'POST':
                    $dnsRequestMessage = $this->dnsMessageFactory->createMessageFromDnsWireMessage(
                        (string)$serverRequest->getBody()
                    );
                    break;
                default:
                    throw new Exception('Request method is not supported.');
            }
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Failed to create DNS message: %s', $t->getMessage()));
            throw $t;
        }

        try {
            $dnsResponseMessage = $this->dnsResolver->resolve($dnsRequestMessage);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf('Failed to resolve DNS query: %s', $t->getMessage()),
                [
                    'dnsRequestMessage' => $dnsRequestMessage,
                ]
            );
            throw $t;
        }

        $this->logger->info(
            sprintf("Resolved DNS query with method %s", $serverRequest->getMethod()),
            [
                'dnsRequestMessage'  => $dnsRequestMessage,
                'dnsResponseMessage' => $dnsResponseMessage,
            ]
        );

        return $this->dohHttpMessageFactory->createResponseFromMessage($dnsResponseMessage);
    }
}
