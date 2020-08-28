<?php declare(strict_types=1);

use Http\Adapter\Guzzle6\Client;
use NoGlitchYo\Dealdoh\Dns\Client\DohClient;
use NoGlitchYo\Dealdoh\Dns\Client\PlainDnsClient;
use NoGlitchYo\Dealdoh\Dns\Resolver\DnsUpstreamPoolResolver;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Mapper\HttpResponseMapper;
use NoGlitchYo\Dealdoh\Mapper\MessageMapper;
use NoGlitchYo\Dealdoh\Middleware\DohResolverMiddleware;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

$app = new App;

$app->any(
    '/dns-query',
    function (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $dnsMessageFactory = new MessageMapper();
        $dnsResolver = new DnsUpstreamPoolResolver(
            new DnsUpstreamPool(
                [
                    'dps:53',
                    'https://cloudflare-dns.com/dns-query',
                    '8.8.8.8:53',
                ]
            ),
            [
                new DohClient(
                    new Client(
                        new \GuzzleHttp\Client([])
                    ),
                    $dnsMessageFactory
                ),
                new PlainDnsClient(
                    $dnsMessageFactory,
                    new DnsOverTcpTransport(),
                    new DnsOverUdpTransport()
                ),
            ]
        );

        $dnsProxy = new DohResolverMiddleware(
            $dnsResolver,
            $dnsMessageFactory,
            new HttpResponseMapper($dnsMessageFactory)
        );

        return $dnsProxy->forward($request);
    }
);

$app->run();
