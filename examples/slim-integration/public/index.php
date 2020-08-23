<?php declare(strict_types=1);

use Http\Adapter\Guzzle6\Client;
use NoGlitchYo\Dealdoh\Dns\Client\DohClient;
use NoGlitchYo\Dealdoh\Dns\Client\StdClient;
use NoGlitchYo\Dealdoh\Dns\Resolver\DnsPoolResolver;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Mapper\HttpResponseMapper;
use NoGlitchYo\Dealdoh\Middleware\DohHttpMiddleware;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;
use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

$app = new App;

$app->any(
    '/dns-query',
    function (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $dnsMessageFactory = new \NoGlitchYo\Dealdoh\Mapper\MessageMapper();
        $dnsResolver = new DnsPoolResolver(
            new DnsUpstreamPool(
                [
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
                new StdClient(
                    $dnsMessageFactory,
                    new DnsOverTcpTransport(),
                    new DnsOverUdpTransport()
                ),
            ]
        );

        $dnsProxy = new DohHttpMiddleware(
            $dnsResolver,
            $dnsMessageFactory,
            new HttpResponseMapper($dnsMessageFactory)
        );

        return $dnsProxy->forward($request);
    }
);

$app->run();
