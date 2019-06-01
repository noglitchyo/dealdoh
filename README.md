# Dealdoh
> Deal DNS over HTTPS

Dealdoh is a simple DNS over HTTPS proxy powered by PHP.

![PHP from Packagist](https://img.shields.io/packagist/php-v/noglitchyo/dealdoh.svg)
[![Build Status](https://travis-ci.org/noglitchyo/dealdoh.svg?branch=master)](https://travis-ci.org/noglitchyo/dealdoh)
[![codecov](https://codecov.io/gh/noglitchyo/dealdoh/branch/master/graph/badge.svg)](https://codecov.io/gh/noglitchyo/dealdoh)

## Features

Dealdoh go a little beyond what a simple proxy should do:

- [x] It can use multiple upstreams at once and provide a fallback mechanism.
- [x] It can use different DNS protocol: RFC-1035 (TCP/UDP), RFC-8484 (DoH)
- [x] Attempt to provide a DNS abstraction layer to allow easy development on top of it.

## Roadmap

- [ ] Improve the current DNS clients
- [ ] Add Google DOH API client (https://developers.google.com/speed/public-dns/docs/dns-over-https)
- [ ] Ability to choose a DNS upstream fallback/selection strategy
- [ ] Dockerized application
- [ ] Good documentation

## Why Dealdoh?

Dealdoh was created for development purpose. I wanted to reach my Docker containers from the browser by their hostnames. 
So I started to use a [Docker image who discover services and register their hostname into a DNS](https://github.com/mageddo/dns-proxy-server) exposed on port 53.
But I encountered the following issues:
- I could not change the /etc/hosts file
- I could not change the DNS for my computer (restrictions issue)
 
I ended up with the following solution: use the DoH client from Firefox and proxy every DNS query to a DoH proxy: Dealdoh.

## Getting started

### Requirements

- A web server
- HTTPS enabled (self-signed certificates can do depending on the DOH client)

### Installation

- `composer require noglitchyo/dealdoh`

- You need a PSR-7 ServerRequest if you wish to directly use the `HttpProxy::forward()` method. 
Please check those cool implementations below:
    * https://github.com/Nyholm/psr7 - `composer require nyholm/psr7`
    * https://github.com/guzzle/psr7 - `composer require guzzle/psr7`
    * https://github.com/zendframework/zend-diactoros - `composer require zendframework/zend-diactoros`

- Configure your dealdoh entrypoint

As stated before, `HttpProxy::forward()` method consumes PSR-7 ServerRequest to make it easier to implement on "Action"/"Middleware" classes.
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
- Testing the installation

First, you need to know that most of implemented DoH client/server will send/receive DNS requests on the following path:
`/dns-query`. Make sure your Dealdoh proxy has been configured to listen on this route or configure the client accordingly.

Multiple options exists: 

* Using the doh-client from [Facebook Experimental](https://github.com/facebookexperimental/doh-proxy)

To make it easier, I created a [Docker image](https://hub.docker.com/) that you can use by running:

`docker run --name dohfb -it noglitchyo/facebookexperimental-doh-proxy doh-client --domain dealdoh.proxy.addr --qname whatismyip.com --dnssec --insecure`

(Tips: pass the --insecure option to doh-client if you are using self-signed certificates **#notDocumented**)

Please, check [how to use the client](https://github.com/facebookexperimental/doh-proxy#doh-client).
    
* Using your client browser  

Firefox provides a [Trusted Recursive Resolver](https://wiki.mozilla.org/Trusted_Recursive_Resolver) who can be configured to query DoH servers.

I advise you to read [this really good article from Daniel Stenberg](https://daniel.haxx.se/blog/2018/06/03/inside-firefoxs-doh-engine/) 
which will give you lot of details about this TRR and how to configure it like a pro. 

### Examples

Checkout some really simple integration examples to get a glimpse:

- [Slim Framework](examples/slim-integration/README.md) 

## Contributing

Get started here [CONTRIBUTING.md](CONTRIBUTING.md).

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* https://github.com/reactphp/dns for their really good DNS wire format codec. 
* https://github.com/mageddo/dns-proxy-server for its amazing container hostname discovery & DNS Docker image.
Combined with Dealdoh it is amazing.
* https://github.com/facebookexperimental/doh-proxy, because their doh-client rocks!

## References

- https://tools.ietf.org/html/rfc8484
- https://tools.ietf.org/html/rfc1035
- PSR-7
- PSR-18
