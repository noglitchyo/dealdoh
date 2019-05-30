<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh;

use Exception;
use InvalidArgumentException;
use NoGlitchYo\DoDoh\Factory\DnsMessageFactory;
use NoGlitchYo\DoDoh\Factory\DohHttpMessageFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class HttpProxy
{
    /**
     * @var DnsPoolResolver
     */
    private $dnsResolver;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var DnsMessageFactory
     */
    private $dnsMessageFactory;

    public function __construct(DnsPoolResolver $dnsResolver, DnsMessageFactory $dnsMessageFactory, LoggerInterface $logger = null)
    {
        $this->dnsResolver = $dnsResolver;
        $this->logger = $logger ?? new NullLogger();
        $this->dnsMessageFactory = $dnsMessageFactory;
    }

    public function forward(ServerRequestInterface $serverRequest): ResponseInterface
    {
        try {
            switch ($serverRequest->getMethod()) {
                case 'GET':
                    $dnsQuery = $serverRequest->getQueryParams()['dns'];
                    if (!$dnsQuery) {
                        throw new InvalidArgumentException('Query parameter `dns` is mandatory.');
                    }
                    $dnsRequestMessage = $this->dnsMessageFactory->createMessageFromBase64($dnsQuery);
                    break;
                case 'POST':
                    $dnsRequestMessage = $this->dnsMessageFactory->createMessageFromDnsWireMessage((string)$serverRequest->getBody());
                    break;
                default:
                    throw new Exception('Request method is not supported.');
            }
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Failed to initialize DNS request message: %s', $t->getMessage()));
            throw $t;
        }

        try {
            $dnsResponseMessage = $this->dnsResolver->resolve($dnsRequestMessage);
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Failed to resolve DNS query: %s', $t->getMessage()));
            throw $t;
        }

        $this->logger->info(
            sprintf(
                "Resolved DNS query with method %s and query: %s",
                $serverRequest->getMethod(),
                $this->dnsMessageFactory->convertMessageToBase64($dnsRequestMessage)
            ),
            [
                'dnsRequestMessage' => $dnsRequestMessage,
                'dnsResponseMessage' => $dnsResponseMessage
            ]
        );

        $dohHttpResponseFactory = new DohHttpMessageFactory($this->dnsMessageFactory);

        return $dohHttpResponseFactory->createResponseFromMessage($dnsResponseMessage);
    }
}
