<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper\GoogleDns;

use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Entity\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecordSection;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;

/**
 * @see https://developers.google.com/speed/public-dns/docs/dns-over-https
 */
class MessageMapper
{
    public function map(array $googleDnsResponse): MessageInterface
    {
        $question = $googleDnsResponse['Question'] ?? [];
        $questionSection = new Message\Section\QuestionSection();
        foreach ($question as $query) {
            $questionSection->add(new Query($query['name'], $query['type'], ResourceRecord::CLASS_IN));
        }

        return new Message(
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
            ),
            $questionSection,
            static::mapResourceRecordSection(
                $googleDnsResponse['Answer'] ?? [],
                new Message\Section\ResourceRecordSection()
            ),
            static::mapResourceRecordSection(
                $googleDnsResponse['Additional'] ?? [],
                new Message\Section\ResourceRecordSection()
            )
        );
    }

    public static function mapResourceRecordSection(array $records, ResourceRecordSection $recordSection)
    {
        foreach ($records as $record) {
            $recordSection->add(
                new ResourceRecord(
                    $record['name'],
                    $record['type'],
                    ResourceRecord::CLASS_IN,
                    $record['TTL'],
                    $record['data']
                )
            );
        }
        return $recordSection;
    }
}
