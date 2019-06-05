<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper\GoogleDns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

/**
 * @see https://developers.google.com/speed/public-dns/docs/dns-over-https
 */
class MessageMapper
{
    public function map(array $googleDnsResponse): MessageInterface
    {
        $message = new Message(
            new Header(
                0,
                true,
                0,
                false,
                $googleDnsResponse['TC'],
                $googleDnsResponse['RD'],
                $googleDnsResponse['RA'],
                0,
                $googleDnsResponse['Status']
            )
        );

        $question = $googleDnsResponse['Question'] ?? [];
        foreach ($question as $query) {
            $message->addQuestion(new Query($query['name'], $query['type'], ResourceRecord::CLASS_IN));
        }

        $answer = $googleDnsResponse['Answer'] ?? [];
        foreach ($answer as $rr) {
            $message->addAnswer(
                new ResourceRecord(
                    $rr['name'],
                    $rr['type'],
                    ResourceRecord::CLASS_IN,
                    $rr['TTL'],
                    $rr['data']
                )
            );
        }

        $additional = $googleDnsResponse['Additional'] ?? [];
        foreach ($additional as $rr) {
            $message->addAdditional(new ResourceRecord(
                $rr['name'],
                $rr['type'],
                ResourceRecord::CLASS_IN,
                $rr['TTL'],
                $rr['data']
            ));
        }

        return $message;
    }
}
