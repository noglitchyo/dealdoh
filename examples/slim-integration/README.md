# Slim Integration Example

This example illustrates how you can run Dealdoh with Slim Framework.

> Please note that you need to be in the examples/slim-integration directory before running these commands.

### Install dependencies

`composer install`

### Run the server

`php -S localhost:8888 -t public public/index.php`

### Query the server

Run a manual DNS query against the proxy:

`curl localhost:8888/dns-query?dns=AAABAAABAAAAAAABA3d3dwZnb29nbGUDY29tAAAcAAEAACkQAAAAAAAACAAIAAQAAQAA`

The above base64url encoded DNS query (in the `dns` query parameter) ask to resolve `IN AAAA` records for the domain `www.google.com`.

Dealdoh proxy will return the DNS response expressed in DNS wire format in its body: 

`�westanfordedstanfordedukAargustanfordeduhostmastestanfordeduxXl��X)�`
