# Dealdoh
> A toy to deal DNS over HTTPS and more!

Dealdoh is a simple DNS-over-HTTPS (DoH) proxy written in PHP. 
It can be use as a middleware or a client and attempt to provide a low-level abstraction for DNS.

![PHP from Packagist](https://img.shields.io/packagist/php-v/noglitchyo/dealdoh.svg)
[![Build Status](https://travis-ci.org/noglitchyo/dealdoh.svg?branch=master)](https://travis-ci.org/noglitchyo/dealdoh)
[![codecov](https://codecov.io/gh/noglitchyo/dealdoh/branch/master/graph/badge.svg)](https://codecov.io/gh/noglitchyo/dealdoh)
![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/noglitchyo/dealdoh.svg)
![Packagist](https://img.shields.io/packagist/l/noglitchyo/dealdoh.svg)

## Description

Dealdoh can be use in different manners and for different purposes:
- as a middleware in a web server and acts as a DNS proxy
- as a client, using the provided command-line client to make DNS queries with [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/).
- as a low-level abstraction layer for development around DNS.

## Features

- [x] Create and forward DNS messages in different format to different type of upstreams.
- [x] Use a pool of DNS upstreams to send queries with a fallback mechanism.
- [x] Use different DNS protocol: RFC-1035 (TCP/UDP), RFC-8484 (DoH), Google DoH API.
- [x] Provide a DNS low-level abstraction layer for DNS development. 
- [x] Make DNS query from the command-line and provide results in JSON 

## Roadmap

- [ ] Improve the current DNS clients
- [ ] Ability to choose a DNS upstream fallback/selection strategy
- [ ] Dockerized application
- [ ] Good documentation

## Getting started

As mentionned above, there is multiple ways to use Dealdoh.
Let's see what can be done at the time with Dealdoh.

### As a DoH proxy middleware

If you wish to get started quickly, check Please, check out [dealdoh-client](https://github.com/noglitchyo/dealdoh-client/) 
which offers a ready-to-use implementation.

#### Requirements

- A web server
- HTTPS enabled with valid certificates (self-signed certificates can work but it depends of the DOH client)

To get valid certificates in a local environment, I recommend you to use [mkcert](https://github.com/FiloSottile/mkcert) which generate for you a local Certificate Authority, and create locally trusted certificates with it. Take 3 minutes to check its really simple documentation for your OS. (since installation differs on each OS)

- PHP 7.3

#### Installation

- You will need to install Dealdoh as a dependency in your project:

`composer require noglitchyo/dealdoh`

- You will need a PSR-7 ServerRequest if you wish to directly use the `HttpProxy::forward()` method. 
Please check those cool implementations below:
    * https://github.com/Nyholm/psr7 - `composer require nyholm/psr7`
    * https://github.com/guzzle/psr7 - `composer require guzzle/psr7`
    * https://github.com/zendframework/zend-diactoros - `composer require zendframework/zend-diactoros`

- Configure your middleware/entrypoint to call Dealdoh's HttpProxy

As stated before, `HttpProxy::forward()` method consumes PSR-7 ServerRequest to make integration easier 
when implementing on "Action"/"Middleware" classes. 

The example below illustrates how to use two DNS upstreams which are using different protocols. 
In this example, the used protocols are UDP (RFC-1035) and DoH (RFC-8484).
Two types of DNS client who can handle each of the DNS protocols used by our upstreams are injected to handle those upstreams.

```php
<?php
$dnsMessageFactory = new \NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory();
$dnsResolver = new \NoGlitchYo\Dealdoh\Service\DnsPoolResolver(
    new \NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool([
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
    new \NoGlitchYo\Dealdoh\Factory\DohHttpMessageFactory($dnsMessageFactory)
);

/** @var $response \Psr\Http\Message\ResponseInterface */
$response = $dnsProxy->forward(/* Expect a \Psr\Http\Message\RequestInterface object */);
```
- Testing the installation

First, you need to know that most of implemented DoH client/server will send/receive DNS requests on the following path:
`/dns-query`. 
Make sure your Dealdoh's entrypoint has been configured to listen on this route or configure your client accordingly if it is possible.

A large variety of client already exists than you can easily find on Internet. For testing purpose, I advise the one below:  

* Using the doh-client from [Facebook Experimental](https://github.com/facebookexperimental/doh-proxy)

To make it easier, I created a [Docker image](https://hub.docker.com/) that you can directly pull and run by running:

`docker run --name dohfb -it noglitchyo/facebookexperimental-doh-proxy doh-client --domain <DEALDOH_ENTRYPOINT> --qname google.com --dnssec --insecure`

*Replace the <DEALDOH_ENTRYPOINT> with the host of your entrypoint for Dealdoh.*

(Tips: pass the --insecure option to doh-client if you are using self-signed certificates **#notDocumented**)

Please, check [how to use the client](https://github.com/facebookexperimental/doh-proxy#doh-client).
    
* Using your client browser  

Firefox provides a [Trusted Recursive Resolver](https://wiki.mozilla.org/Trusted_Recursive_Resolver) who can be configured to query DoH servers.

I advise you to read [this really good article from Daniel Stenberg](https://daniel.haxx.se/blog/2018/06/03/inside-firefoxs-doh-engine/) 
which will give you lot of details about this TRR and how to configure it like a pro. 

Please check [the browser implementations list](https://github.com/curl/curl/wiki/DNS-over-HTTPS#supported-in-browsers-and-clients). 

#### Examples

Checkout some really simple integration examples to get a glimpse on how it can be done:

- [Slim Framework integration](examples/slim-integration/README.md) 
- [DoH + Docker + DNS + Hostname Discovery](examples/docker-firefox/README.md)

### As a DNS command-line client

Please, check out [Dealdoh client](https://github.com/noglitchyo/dealdoh-client/) for this.

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
- [PSR-7](https://www.php-fig.org/psr/psr-7/)
- [PSR-18](https://www.php-fig.org/psr/psr-18/)
- [Wiki page DNS-over-HTTPS from Curl](https://github.com/curl/curl/wiki/DNS-over-HTTPS)
