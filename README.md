# Dealdoh
> Play with DNS over HTTPS and much more!

Dealdoh is a DNS-over-HTTPS (DoH) proxy and a library around DNS messaging written in PHP.

![PHP from Packagist](https://img.shields.io/packagist/php-v/noglitchyo/dealdoh.svg)
[![Build Status](https://travis-ci.org/noglitchyo/dealdoh.svg?branch=master)](https://travis-ci.org/noglitchyo/dealdoh)
[![codecov](https://codecov.io/gh/noglitchyo/dealdoh/branch/master/graph/badge.svg)](https://codecov.io/gh/noglitchyo/dealdoh)
![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/noglitchyo/dealdoh.svg)
![Packagist](https://img.shields.io/packagist/l/noglitchyo/dealdoh.svg)

## Overview

This library gives ability to proxy DoH requests and/or to send DNS queries with various modern DNS protocols: DNSCrypt, DoH, GoogleDNS...

It attempts to achieve the following goals:
- provide high-compatibility with a large variety of DNS protocols.
- provide a well-designed abstraction layer for development around DNS in PHP.

## Features

- [x] DoH proxy middleware PSR-15/PSR-7 compliant.
- [x] Create and forward DNS messages to different type of DNS upstream resolvers.
- [x] Forward DNS query through multiple DNS upstream resolvers.
- [x] Compatible with DNS protocols: RFC-1035 (Plain DNS over TCP/UDP), RFC-8484 (DoH), Google DoH API, DNSCrypt
- [x] Abstraction layer around DNS development.
- [x] Read [DNS stamps](https://dnscrypt.info/stamps-specifications)

## Client

[dealdoh-client](https://github.com/noglitchyo/dealdoh-client/) is a CLI utility which offers a ready-to-use implementation
of this library to send and forward DNS queries.

## Library

#### Requirements

- PHP 7.3
- Web server
- Optional: HTTPS enabled with valid certificates (self-signed certificates can work but it depends of the DOH client making the queries)

#### Installation

- Run `composer require noglitchyo/dealdoh`

- `DohResolverMiddleware::forward()` method consumes PSR-7 ServerRequest. 
Some compatible implementations which can be used:
    * https://github.com/Nyholm/psr7 - `composer require nyholm/psr7`
    * https://github.com/guzzle/psr7 - `composer require guzzle/psr7`
    * https://github.com/zendframework/zend-diactoros - `composer require zendframework/zend-diactoros`
- Configure your application to call `DohResolverMiddleware::forward()`
- Testing the installation

As recommended in RFC-8484, usually, DoH client/server will send/receive DNS requests on the path: `/dns-query`. 
Your application should be configured to listen on this route.

A large variety of DoH client exists than can be used to test the installation. 

* [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/)
* [Facebook Experimental](https://github.com/facebookexperimental/doh-proxy)
  
* Using client from Web Browser  
Mozilla Firefox provides a [Trusted Recursive Resolver](https://wiki.mozilla.org/Trusted_Recursive_Resolver) who can be configured to query DoH servers.

[This article from Daniel Stenberg](https://daniel.haxx.se/blog/2018/06/03/inside-firefoxs-doh-engine/) 
provides a lot of details about TRR and how to configure it. 
Please check also [the browser implementations list](https://github.com/curl/curl/wiki/DNS-over-HTTPS#supported-in-browsers-and-clients). 

#### Example
```php
<?php
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleClientAdapter;
use NoGlitchYo\Dealdoh\Dns\Client\DnsCryptClient;
use NoGlitchYo\Dealdoh\Dns\Client\DohClient;
use NoGlitchYo\Dealdoh\Dns\Client\PlainDnsClient;
use NoGlitchYo\Dealdoh\Dns\Resolver\DnsUpstreamPoolResolver;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Mapper\DnsCrypt\AuthenticatedEncryptionMapper;
use NoGlitchYo\Dealdoh\Mapper\HttpResponseMapper;
use NoGlitchYo\Dealdoh\Mapper\MessageMapper;
use NoGlitchYo\Dealdoh\Middleware\DohResolverMiddleware;
use NoGlitchYo\Dealdoh\Repository\DnsCrypt\CertificateRepository;
use Psr\Http\Message\ResponseInterface;

$messageMapper = new MessageMapper();

// Initialize the DNS clients to use with the resolver
$dnsClients = [
    new DohClient(new GuzzleClientAdapter(new GuzzleClient()), $messageMapper),
    new PlainDnsClient($messageMapper),
    new DnsCryptClient(new AuthenticatedEncryptionMapper(), new CertificateRepository(), $messageMapper)
];

// Initialize the list of DNS upstreams to use to resolve the DNS queries
$dnsUpstreamPool = new DnsUpstreamPool([
    'dns://8.8.8.8:53',
    'https://cloudflare-dns.com/dns-query',
    'sdns://AQcAAAAAAAAAFlsyMDAxOmJjODoxODI0OjczODo6MV0gAyfzz5J-mV9G-yOB4Hwcdk7yX12EQs5Iva7kV3oGtlEgMi5kbnNjcnlwdC1jZXJ0LmFjc2Fjc2FyLWFtcy5jb20',
]);

// Initialize the DNS resolver with the list of upstreams and the list of clients able to exchange with the upstreams
$dnsResolver = new DnsUpstreamPoolResolver($dnsUpstreamPool, $dnsClients);

// Create the ResolverMiddleware with the created DnsResolver
$dohMiddleware = new DohResolverMiddleware($dnsResolver, $messageMapper, new HttpResponseMapper($messageMapper));

/** @var $response ResponseInterface */
$response = $dohMiddleware->forward(/* Expect a \Psr\Http\Message\RequestInterface object */);
```

#### More examples

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

## Acknowledgments

* https://github.com/reactphp/dns 
* https://github.com/mageddo/dns-proxy-server
* https://github.com/facebookexperimental/doh-proxy
* https://github.com/DNSCrypt/dnscrypt-proxy

## References

- [RFC-8484](https://tools.ietf.org/html/rfc8484)
- [RFC-1035](https://tools.ietf.org/html/rfc1035)
- [RFC-4501](https://tools.ietf.org/html/rfc4501)
- [RFC-7719](https://tools.ietf.org/html/rfc7719)
- [PSR-7](https://www.php-fig.org/psr/psr-7/)
- [PSR-15](https://www.php-fig.org/psr/psr-15/)
- [PSR-18](https://www.php-fig.org/psr/psr-18/)
- [DNSCrypt](https://dnscrypt.info/protocol)
- [DNS Stamps](https://dnscrypt.info/stamps-specifications)
- [Wiki page DNS-over-HTTPS from Curl](https://github.com/curl/curl/wiki/DNS-over-HTTPS)
