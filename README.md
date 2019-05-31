# DoDoh 

DoDoh is a simple DNS over HTTPS proxy as specified in https://tools.ietf.org/html/rfc8484 built on PHP.
- PHP 7.3
- PSR-7 compliant
- PSR-18 compliant
- RFC-8484 compliant (https://tools.ietf.org/html/rfc8484)

## Features

DoDoh go a little beyond what a simple proxy should do:

- [x] Can use multiple upstreams
- [x] Can use different DNS protocol: standard udp/tcp, DoH
- [x] Attempt to provide a DNS abstraction layer (from https://tools.ietf.org/html/rfc1035) to allow development on top of it and customization

## Roadmap

- [ ] Add full unit test coverage
- [ ] Improve current DNS clients
- [ ] Ability to choose a DNS upstream strategy
- [ ] Dockerized app
- [ ] Good documentation

## Why DoDoh?

DoDoh was created for development purpose: I wanted to reach my Docker containers from the browser by their hostnames.
But let's give some context:
- could not modify the /etc/hosts file
- could not change the DNS for the machine
- container domain names were well registered in a custom DNS container (thanks to: https://github.com/mageddo/dns-proxy-server)
So, I ended up with the following solution: use the DOH client from Mozilla Firefox and proxy every DNS query to DoDoh.


## Getting started

This example involve the use of two different DNS upstreams using different protocols.
We also inject two types of client who can handle each of the protocols used.

```php
<?php
$dnsMessageFactory = new \NoGlitchYo\DoDoh\Factory\DnsMessageFactory();
$dnsResolver = new \NoGlitchYo\DoDoh\DnsPoolResolver(
    new \NoGlitchYo\DoDoh\DnsUpstreamPool([
        '8.8.8.8:53',
        'https://cloudflare-dns.com/dns-query',
    ]),
    [
        new \NoGlitchYo\DoDoh\Client\DohClient(
            new \Http\Adapter\Guzzle6\Client(new \GuzzleHttp\Client()),
            $dnsMessageFactory
        ),
        new \NoGlitchYo\DoDoh\Client\StdClient(
            new \Socket\Raw\Factory(), 
            $dnsMessageFactory
        ),
    ]
);

$dnsProxy = new \NoGlitchYo\DoDoh\HttpProxy(
    $dnsResolver,
    $dnsMessageFactory,
);

/** @var \Psr\Http\Message\ResponseInterface */
$response = $dnsProxy->forward($request);
```

DoDoh take advantages of PSR-7 to make it easy to integrate with frameworks implementing it.
