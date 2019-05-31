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

### Installation

- Install dependencies
`composer require noglitchyo/dodoh`

- You need a PSR-7 ServerRequest if you wish to directly use the `HttpProxy::forward()` method. Please check some cool implementations below:
    * https://github.com/Nyholm/psr7 - `composer require nyholm/psr7`
    * https://github.com/guzzle/psr7 - `composer require guzzle/psr7`
    * https://github.com/zendframework/zend-diactoros - `composer require zendframework/zend-diactoros`

- Configure your entrypoint
As stated before, `HttpProxy::forward()` method consumes PSR-7 ServerRequest to make it easier to implement on "Action" or "Middleware" classes.
The example below illustrate how to use two different DNS upstreams using different protocols.
Two types of DNS client who can handle each of the DNS protocols used by our upstreams are also injected.

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

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Thanks to https://github.com/reactphp/dns for their really good DNS wire format codec 

