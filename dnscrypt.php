<?php declare(strict_types=1);

require 'vendor/autoload.php';

use NoGlitchYo\Dealdoh\Client\DnsCryptClient;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use Socket\Raw\Factory;

//use NoGlitchYo\Dealdoh\Entity\DnsUpstream;

$dnsCryptClient = new DnsCryptClient(new MessageFactory(), new Factory());

$messageFactory = new MessageFactory();

$dnsRequestMessage = $messageFactory->create()
    ->withQuestionSection(new Message\Section\QuestionSection([new Query("google.fr", 1, 1)]));
//$dnsUpstream = new DnsUpstream(
//    'sdns://AQcAAAAAAAAAETUxLjE1LjEwNi4xNzY6NDQzIGcUiAnFqewnNLjh8DUYpcePX07pXc3sDOf2U-vpI55WHjIuZG5zY3J5cHQtY2VydC5hbXMuY2hhcmlzLmNvbQ',
//    'charis'
//);

$dnsCryptUpstream = new DnsCryptUpstream(
    '51.158.166.97:443',
    '2.dnscrypt-cert.acsacsar-ams.com',
    '0327f3cf927e995f46fb2381e07c1c764ef25f5d8442ce48bdaee4577a06b651',
);

$dnsCryptClient->resolve($dnsCryptUpstream, $dnsRequestMessage);
