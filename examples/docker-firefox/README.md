# Hostname resolution for Docker containers from Mozilla Firefox

This integration example can be helpful in a situation where:

- [x] You are using Docker containers
- [x] Would like to reach docker container by their hostname using hostname/services 
discovery from Docker images like: 
    * [DPS](https://github.com/mageddo/dns-proxy-server)
    * [SkyDNS](https://github.com/skynetservices/skydns)
- [x] You can not change the DNS resolver for your host machine.
- [x] Want to reach your containers from your browser with their hostnames (at least).
- [x] You browser is Mozilla Firefox.

It is indeed a really specific configuration for this example, 
but it just shows how DoH can really help to get around things.

### For Windows user only
For user using Windows, you might have already noticed that you can not [reach your container by their
internal network IP](https://github.com/docker/for-win/issues/221). Let's fix that first!

**Please, read the placeholders description:**

> [DOCKER_SUBNET_IP]: Your Docker subnet (default: 172.27.0.0)
> [DOCKER_HOST_IP]: Your Docker host IP (default: 10.0.75.2)

Replace the placeholders and run the following command:

`route add [DOCKER_SUBNET_IP] mask 255.255.0.0 [DOCKER_HOST_IP] -p`

**Be extremely cautious when running this command.**

### Add your upstream to Dealdoh

You will need to add your custom DNS server as a new upstream to the Dealdoh 
[upstreams pool](https://github.com/noglitchyo/dealdoh/blob/master/src/DnsUpstreamPool.php).
Make sure Dealdoh can reach it.

### Configure your browser

**Please, read the placeholders description:**

> [DEALDOH_HOST_IP]: IP of your server hosting Dealdoh proxy

- On your browser, go to: `about:config`
- Update the following configuration entries:
    * network.trr.allow-rfc1918 = `true` (accept resolving to local IPs)
    * network.trr.custom_uri = `https://[DEALDOH_HOST_IP]/dns-query`
    * network.trr.mode = `2` (user DoH first, then fallback on default)
    * network.trr.uri = `https://[DEALDOH_HOST_IP]/dns-query`
(Tips: you should read [this article](https://daniel.haxx.se/blog/2018/06/03/inside-firefoxs-doh-engine/) to get some help with the configuration.)
- If Dealdoh is setup and your server is running, you should be able to resolve domains from Dealdoh. 
- You can check that resolve is working with `about:networking` using the DNS and DNS Lookup tab.

## Give it a try with the Docker stack

For testing purpose, a sample stack is provided.
This stack comes with:
- Nginx + PHP-FPM running Dealdoh (port 443 is exposed to the host machine)
- An image running a DNS and registering container hostnames [DPS](https://github.com/mageddo/dns-proxy-server)
- A random Nginx container with a hostname to resolve

* Let's run it:

`docker-compose up -d`

* Install project dependencies:

`docker-compose run php composer install`

* Now, let's run the embedded client for testing:

`docker-compose run dohclient doh-client --qname resolveme.please --dnssec --domain https --insecure`

* Output: 

```2019-06-01 13:36:19,261:    DEBUG: Opening connection to https
  2019-06-01 13:36:19,277:    DEBUG: Query parameters: {'dns': 'AAABAAABAAAAAAABCXJlc29sdmVtZQZwbGVhc2UAABwAAQAAKQUAAACAAAAA'}
  2019-06-01 13:36:19,278:    DEBUG: Stream ID: 1 / Total streams: 0
  2019-06-01 13:36:19,288:    DEBUG: Response headers: [(':status', '200'), ('server', 'nginx'), ('date', 'Sat, 01 Jun 2019 13:36:19 GMT'), ('content-type', 'application/dns-message'), ('content-length', '66'), ('x-powered-by', 'PHP/7.3.3'), ('cache-control', 'max-age=0'), ('x-frame-options', 'SAMEORIGIN'), ('x-xss-protection', '1; mode=block'), ('x-content-type-options', 'nosniff'), ('referrer-policy', 'no-referrer-when-downgrade'), ('content-security-policy', "default-src * data: 'unsafe-eval' 'unsafe-inline'"), ('strict-transport-security', 'max-age=31536000')]
  id 0
  opcode QUERY
  rcode NOERROR
  flags RD
  ;QUESTION
  resolveme.please. IN AAAA
  ;ANSWER
  resolveme.please. 0 IN A 192.168.128.4
  ;AUTHORITY
  ;ADDITIONAL
  2019-06-01 13:36:19,290:    DEBUG: Response trailers: {}
```

* You can now configure your browser and change the configurations as follow:
    - network.trr.uri = `https://127.0.0.1/dns-query`
    - network.trr.custom_uri = `https://127.0.0.1/dns-query`
    - network.trr.allow-rfc1918 = `true` 
    - network.trr.mode = `2`

Voilà!
