<?php declare(strict_types=1);

use Http\Adapter\Guzzle6\Client;
use NoGlitchYo\Dealdoh\Client\DohClient;
use NoGlitchYo\Dealdoh\Client\StdClient;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverUdpTransport;
use NoGlitchYo\Dealdoh\DohProxy;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactory;
use NoGlitchYo\Dealdoh\Service\DnsPoolResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

$app = new App;

$app->any(
    '/dns-query',
    function (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $dnsMessageFactory = new MessageFactory();
        $dnsResolver = new DnsPoolResolver(
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
                new StdClient(
                    $dnsMessageFactory,
                    new DnsOverTcpTransport(),
                    new DnsOverUdpTransport()
                ),
            ]
        );

        $dnsProxy = new DohProxy(
            $dnsResolver,
            $dnsMessageFactory,
            new DohHttpMessageFactory($dnsMessageFactory)
        );

        return $dnsProxy->forward($request);
    }
);

$app->run();
