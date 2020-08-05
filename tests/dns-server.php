#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NoGlitchYo\Dealdoh\Tests\Stub\DnsServerStub;

$opts = [
    'message:' // base64 encoded dns Response message
];

$options = getopt('m::', $opts);

$dnsServer = new DnsServerStub();
$dnsServer->run($options['message'] ?? null);
