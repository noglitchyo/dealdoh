# Dealdoh 

Dealdoh is a simple DNS over HTTPS proxy to deal with DOH built with PHP.

## Features

Dealdoh go a little beyond what a simple proxy should do:

- [x] Can use multiple upstreams
- [x] Can use different DNS protocol: standard udp/tcp, DoH
- [x] Attempt to provide a DNS abstraction layer (from https://tools.ietf.org/html/rfc1035) to allow development on top of it and customization

## Roadmap

- [ ] Add full unit test coverage
- [ ] Improve current DNS clients
- [ ] Ability to choose a DNS upstream strategy
- [ ] Dockerized app
- [ ] Good documentation

## Why Dealdoh?

Dealdoh was created for development purpose: I wanted to reach my Docker containers from the browser by their hostnames.
But let's give some context:
- I could not change the /etc/hosts file
- I could not change the DNS for the machine
- My Docker container domain names were well registered in a custom DNS docker container (thanks to: https://github.com/mageddo/dns-proxy-server)
So, I ended up with the following solution: use the DOH client from Mozilla Firefox and proxy every DNS query to Dealdoh.


## Getting started

### Installation

- Install dependencies
`composer require noglitchyo/dealdoh`

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
$dnsMessageFactory = new \NoGlitchYo\Dealdoh\Factory\DnsMessageFactory();
$dnsResolver = new \NoGlitchYo\Dealdoh\DnsPoolResolver(
    new \NoGlitchYo\Dealdoh\DnsUpstreamPool([
        '8.8.8.8:53',
        'https://cloudflare-dns.com/dns-query',
    ]),
    [
        new \NoGlitchYo\Dealdoh\Client\DohClient(
            new \Http\Adapter\Guzzle6\Client(new \GuzzleHttp\Client()),
            $dnsMessageFactory
        ),
        new \NoGlitchYo\Dealdoh\Client\StdClient(
            new \Socket\Raw\Factory(), 
            $dnsMessageFactory
        ),
    ]
);

$dnsProxy = new \NoGlitchYo\Dealdoh\HttpProxy(
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
* Thanks to https://github.com/mageddo/dns-proxy-server for its amazing container hostname discovery & DNS image
* Thanks to https://github.com/facebookexperimental/doh-proxy, because their doh-client rocks!

## References

- https://tools.ietf.org/html/rfc8484
- https://tools.ietf.org/html/rfc1035
- PSR-7
- PSR-18
