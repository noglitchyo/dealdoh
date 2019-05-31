# Domain Name Resolution from Mozilla Firefox

## Windows Setup (only for Windows user!) 
The following setup suppose that:
- you are running on Windows 10 with Hyper-V manager
- you have administrator permissions
- you are using Mozilla Firefox
- you have a local HTTPS server

### Add a route configuration
- <DOCKER_SUBNET_IP> : Default Docker subnet (i.e: 172.27.0.0)
- <DOCKER_HOST_IP> : Default Docker host IP (i.e: 10.0.75.2)

Run the following command:
`route add <DOCKER_SUBNET_IP> mask 255.255.0.0 <DOCKER_HOST_IP> -p`

## Configure your browser

- On your browser, go to: `about:config`
- Update the following configuration entries:
    * network.trr.allow-rfc1918 = `true`
    * network.trr.custom_uri = `https://127.0.0.1/dns-query`
    * network.trr.mode = `2` (user DoH first, then fallback on default)
    * network.trr.uri = `https://127.0.0.1/dns-query`
- Check resolve with `about:networking` on DNS and DNS Lookup tab.
