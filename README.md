# DoDoh 

DoDoh is a simple DNS over HTTPS proxy built on PHP.
- PHP 7.3
- PSR-7 implementation
- PSR-18 implementation

## Features

DoDoh go a little beyond what a simple proxy should do:

- can use multiple upstreams
- can use different protocol: standard udp/tcp, DoH
- try to provide a nice DNS abstraction layer to allow easy customizations

## Roadmap

- full unit test coverage
- DNS upstream strategy

## Why DoDoh?

DoDoh was created for development purpose: I wanted to reach my Docker containers from the browser by their hostnames.
But let's give some context:
- could not modify the /etc/hosts file
- could not change the DNS for the machine
- container domain names were well registered in a custom DNS container (thanks to: https://github.com/mageddo/dns-proxy-server)
So, I ended up with the following solution: use the DOH client from Mozilla Firefox and proxy every DNS query to DoDoh.


## Getting started

This example involve the use of 2 different DNS upstreams using different protocols.

```php
 <?php

$dnsMessageFactory = new DnsMessageFactory();
$dnsResolver = new DnsPoolResolver(
    new DnsUpstreamPool([
        '8.8.8.8:53',
        'https://cloudflare-dns.com/dns-query',
    ]),
    [
        new DohClient(
            new Client(
                new \GuzzleHttp\Client([])
            ),
            $dnsMessageFactory
        ),
        new StdClient(new Factory(), $dnsMessageFactory),
    ]
);

$dnsProxy = new HttpProxy(
    $dnsResolver,
    $dnsMessageFactory,
);

return $dnsProxy->forward($request);
```

