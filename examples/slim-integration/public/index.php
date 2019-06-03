<?php declare(strict_types=1);

use Http\Adapter\Guzzle6\Client;
use NoGlitchYo\Dealdoh\Client\DohClient;
use NoGlitchYo\Dealdoh\Client\StdClient;
use NoGlitchYo\Dealdoh\Service\DnsPoolResolver;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactory;
use NoGlitchYo\Dealdoh\HttpProxy;
use Slim\App;
use Socket\Raw\Factory;

require __DIR__ . '/../vendor/autoload.php';

$app = new App;

$app->any(
    '/dns-query',
    function (ServerRequestInterface $request, ResponseInterface $response, $args) {
        $dnsMessageFactory = new MessageFactory();
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
                    new Factory(),
                    $dnsMessageFactory
                ),
            ]
        );

        $dnsProxy = new HttpProxy(
            $dnsResolver,
            $dnsMessageFactory,
            new DohHttpMessageFactory($dnsMessageFactory)
        );

        return $dnsProxy->forward($request);
    }
);

$app->run();
