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
