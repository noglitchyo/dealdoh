# Dealdoh
> A toy to deal DNS over HTTPS and more!

Dealdoh is a simple DNS-over-HTTPS (DoH) proxy written in PHP. 
It can be use as a middleware or a client and attempt to provide a low-level abstraction layer for DNS messaging.

![PHP from Packagist](https://img.shields.io/packagist/php-v/noglitchyo/dealdoh.svg)
[![Build Status](https://travis-ci.org/noglitchyo/dealdoh.svg?branch=master)](https://travis-ci.org/noglitchyo/dealdoh)
[![codecov](https://codecov.io/gh/noglitchyo/dealdoh/branch/master/graph/badge.svg)](https://codecov.io/gh/noglitchyo/dealdoh)
![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/noglitchyo/dealdoh.svg)
![Packagist](https://img.shields.io/packagist/l/noglitchyo/dealdoh.svg)

## Description

Dealdoh can be use in different manners and for different purposes. Dealdoh attempt to achieve the following goals:
- provide a DoH middleware PSR-15 compliant which can be use in any PHP application to act as a DNS proxy.
- provide a variety of DNS stub resolver.
- provide a large panel of DNS clients.
- provide a low-level abstraction layer for development around DNS.

Dealdoh also comes with a [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/) embedding the following features:
- an application implementing Dealdoh middleware and ready to be run as a micro-service
- a CLI client to make DNS queries, configure DNS upstreams, etc... 

## Features

- [x] Create and forward DNS messages in different format to different type of DNS upstream resolvers.
- [x] Use a pool of DNS upstream resolvers to send queries with a fallback mechanism.
- [x] Compatible with a variety of DNS protocols: RFC-1035 (TCP/UDP), RFC-8484 (DoH), Google DoH API.
- [x] Provide a DNS low-level abstraction layer for DNS development. 
- [x] Make DNS query from the command-line and provide results in JSON 

## Roadmap

- [ ] Improve robustness and compliance of current DNS clients
- [ ] Ability to choose a DNS upstream fallback/selection strategy
- [ ] Good documentation

## Getting started

If you wish to get started quickly, you might want to use [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/) 
which offers a ready-to-use implementation.

#### Requirements

- PHP 7.3
- Web server
- HTTPS enabled with valid certificates (self-signed certificates can work but it depends of the DOH client)

To get trusted certificates in a local environment, I recommend you to use [mkcert](https://github.com/FiloSottile/mkcert) which generate for you a local Certificate Authority, and create locally trusted certificates with it. Take 3 minutes to check its really simple documentation for your OS. (since installation differs on each OS)

#### Installation

- Install Dealdoh as a dependency:

`composer require noglitchyo/dealdoh`

- You will need a PSR-7 ServerRequest if you wish to directly use the `DohProxy::forward()` method. 
Please check those cool implementations below:
    * https://github.com/Nyholm/psr7 - `composer require nyholm/psr7`
    * https://github.com/guzzle/psr7 - `composer require guzzle/psr7`
    * https://github.com/zendframework/zend-diactoros - `composer require zendframework/zend-diactoros`

- Configure your middleware/entrypoint to call Dealdoh's `DohProxy::forward()`

As stated before, `DohProxy::forward()` method consumes PSR-7 ServerRequest to make the integration easier. 

The example below illustrates how to use two DNS upstream resolvers which are using different protocols. 
In this example, the used protocols are TCP/UDP (RFC-1035) and DoH (RFC-8484).
Two types of DNS client who can handle each of the DNS protocols used by our upstreams are injected to handle those upstreams.

```php
<?php
$dnsMessageFactory = new \NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory();
$dnsResolver = new \NoGlitchYo\Dealdoh\Service\DnsPoolResolver(
    new \NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool([
        'dns://8.8.8.8:53',
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

$dnsProxy = new \NoGlitchYo\Dealdoh\DohProxy(
    $dnsResolver,
    $dnsMessageFactory,
    new \NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactory($dnsMessageFactory)
);

/** @var $response \Psr\Http\Message\ResponseInterface */
$response = $dnsProxy->forward(/* Expect a \Psr\Http\Message\RequestInterface object */);
```
- Testing the installation

First, be aware that usually, DoH client/server will send/receive DNS requests on the following path:
`/dns-query` as recommended in RFC-8484. 
Make sure your Dealdoh's entrypoint has been configured to listen on this route or configure your client accordingly if it is possible.

A large variety of client already exists than you can easily find on Internet. 
For testing purpose, I advise the one below:

* Using [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/)

* Using the doh-client from [Facebook Experimental](https://github.com/facebookexperimental/doh-proxy)

To make it easier, I created a [Docker image](https://hub.docker.com/) that you can directly pull and run by running:

`docker run --name dohfb -it noglitchyo/facebookexperimental-doh-proxy doh-client --domain <DEALDOH_ENTRYPOINT> --qname google.com --dnssec --insecure`

*Replace the <DEALDOH_ENTRYPOINT> with the host of your entrypoint for Dealdoh.*

(Tips: pass the --insecure option to doh-client if you are using self-signed certificates **#notDocumented**)

Please, check [how to use the client](https://github.com/facebookexperimental/doh-proxy#doh-client).
    
* Using client from Web Browser  

Mozilla Firefox provides a [Trusted Recursive Resolver](https://wiki.mozilla.org/Trusted_Recursive_Resolver) who can be configured to query DoH servers.

I advise you to read [this really good article from Daniel Stenberg](https://daniel.haxx.se/blog/2018/06/03/inside-firefoxs-doh-engine/) 
which will give you lot of details about this TRR and how to configure it like a pro. 

Please check [the browser implementations list](https://github.com/curl/curl/wiki/DNS-over-HTTPS#supported-in-browsers-and-clients). 

#### Examples

Checkout some really simple integration examples to get a glimpse on how it can be done:

- [Slim Framework integration](examples/slim-integration/README.md) 
- [DoH + Docker + DNS + Hostname Discovery](examples/docker-firefox/README.md)
- [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/)


## Testing

If you wish to run the test, checkout the project and run the test with: 

`composer test`

## Contributing

Get started here [CONTRIBUTING.md](CONTRIBUTING.md).

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Why Dealdoh?

Dealdoh was created for development purpose. I wanted to reach my Docker containers from the browser by their hostnames. 
So I started to use a [Docker image who discover services and register their hostname into a DNS](https://github.com/mageddo/dns-proxy-server) exposed on port 53.
But I encountered the following issues:
- I could not change the /etc/hosts file
- I could not change the DNS for my computer (restrictions issue)
 
I ended up with the following solution: use the DoH client from Firefox and proxy every DNS query to a DoH proxy: Dealdoh.

## Acknowledgments

* https://github.com/reactphp/dns for their really good DNS wire format codec. 
* https://github.com/mageddo/dns-proxy-server for its amazing container hostname discovery & DNS Docker image.
Combined with Dealdoh it is amazing.
* https://github.com/facebookexperimental/doh-proxy, because their doh-client rocks!

## References

- [RFC-8484](https://tools.ietf.org/html/rfc8484)
- [RFC-1035](https://tools.ietf.org/html/rfc1035)
- [RFC-4501](https://tools.ietf.org/html/rfc4501)
- [RFC-7719](https://tools.ietf.org/html/rfc7719)
- [PSR-7](https://www.php-fig.org/psr/psr-7/)
- [PSR-15](https://www.php-fig.org/psr/psr-15/)
- [PSR-18](https://www.php-fig.org/psr/psr-18/)
- [Wiki page DNS-over-HTTPS from Curl](https://github.com/curl/curl/wiki/DNS-over-HTTPS)
