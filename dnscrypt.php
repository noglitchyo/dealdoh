<?php declare(strict_types=1);

require 'vendor/autoload.php';

use NoGlitchYo\Dealdoh\Client\DnsCryptClient;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Service\DnsCryptService;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;

//use NoGlitchYo\Dealdoh\Entity\DnsUpstream;

$dnsCryptClient = new DnsCryptClient(new MessageFactory(), new DnsCryptService());

$messageFactory = new MessageFactory();

$dnsRequestMessage = $messageFactory->create()
    ->withQuestionSection(new Message\Section\QuestionSection([new Query("google.fr", 1, 1)]));
//$dnsUpstream = new DnsUpstream(
//    'sdns://AQcAAAAAAAAAETUxLjE1LjEwNi4xNzY6NDQzIGcUiAnFqewnNLjh8DUYpcePX07pXc3sDOf2U-vpI55WHjIuZG5zY3J5cHQtY2VydC5hbXMuY2hhcmlzLmNvbQ',
//    'charis'
//);

$dnsCryptUpstream = new DnsCryptUpstream(
    '185.228.168.168:8443',
    'cleanbrowsing.org',
    'bcac32fad54369171f0832d6075027c3208ceef0e8e99f9418dc776065d48f29',
);

$dnsCryptClient->resolve($dnsCryptUpstream, $dnsRequestMessage);
