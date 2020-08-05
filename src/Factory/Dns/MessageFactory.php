<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\Dns;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Exception\InvalidDnsWireMessageException;
use NoGlitchYo\Dealdoh\Helper\MessageHelper;
use React\Dns\Model\Message as ReactDnsMessage;
use React\Dns\Model\Record as ReactDnsRecord;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Protocol\Parser;
use React\Dns\Query\Query as ReactDnsQuery;

class MessageFactory implements MessageFactoryInterface
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var BinaryDumper
     */
    private $binaryDumper;

    public function __construct()
    {
        $this->parser = new Parser();
        $this->binaryDumper = new BinaryDumper();
    }

    /**
     * @param int  $id
     * @param bool $qr
     * @param int  $opcode
     * @param bool $isAa
     * @param bool $isTc
     * @param bool $isRd
     * @param bool $isRa
     * @param int  $z
     * @param int  $rcode
     *
     * @return MessageInterface
     */
    public function create(
        int $id = null,
        bool $qr = false,
        int $opcode = HeaderInterface::RCODE_OK,
        bool $isAa = false,
        bool $isTc = false,
        bool $isRd = false,
        bool $isRa = false,
        int $z = 0,
        int $rcode = HeaderInterface::RCODE_OK
    ): MessageInterface {
        if (!$id) {
            $id = MessageHelper::generateId();
        }

        return new Message(new Header($id, $qr, $opcode, $isAa, $isTc, $isRd, $isRa, $z, $rcode));
    }

    public function createMessageFromDnsWireMessage(string $dnsWireMessage): MessageInterface
    {
        try {
            $dnsWireMessage = $this->parser->parseMessage($dnsWireMessage);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidDnsWireMessageException();
        }

        return self::createFromReactDnsMessage($dnsWireMessage);
    }

    /**
     * Return a DNS message in wire format as defined in RFC-1035
     * If ID of the given message is equal to 0, a new ID will be generated
     *
     * @param MessageInterface $dnsMessage
     *
     * @return string
     */
    public function createDnsWireMessageFromMessage(MessageInterface $dnsMessage): string
    {
        $message = new ReactDnsMessage();
        $dnsHeader = $dnsMessage->getHeader();
        // TODO: Id should not be modified here...
        $message->id = ($dnsHeader->getId() != 0) ? $dnsHeader->getId() : MessageHelper::generateId();
        $message->opcode = $dnsHeader->getOpcode();
        $message->aa = (int)$dnsHeader->isAa();
        $message->tc = (int)$dnsHeader->isTc();
        $message->rd = (int)$dnsHeader->isRd();
        $message->ra = (int)$dnsHeader->isRa();
        $message->qr = $dnsHeader->isQr();
        $message->rcode = $dnsHeader->getRcode();

        foreach ($dnsMessage->getQuestion() as $query) {
            $message->questions[] = new ReactDnsQuery(
                $query->getQname(),
                $query->getQtype(),
                $query->getQclass(),
            );
        }

        $message->answers = static::mapResourceRecordToReactDnsRecords($dnsMessage->getAnswer());
        $message->authority = static::mapResourceRecordToReactDnsRecords($dnsMessage->getAuthority());
        $message->additional = static::mapResourceRecordToReactDnsRecords($dnsMessage->getAdditional());

        return $this->binaryDumper->toBinary($message);
    }

    private static function mapResourceRecordToReactDnsRecords(array $records): array
    {
        $newRecords = [];
        foreach ($records as $record) {
            $newRecords[] = new ReactDnsRecord(
                $record->getName(),
                $record->getType(),
                $record->getClass(),
                $record->getTtl(),
                $record->getData()
            );
        }
        return $newRecords;
    }

    private static function mapResourceRecordSection(
        array $records,
        ResourceRecordSection $recordSection
    ): ResourceRecordSection {
        foreach ($records as $record) {
            $recordSection->add(
                new ResourceRecord($record->name, $record->type, $record->class, $record->ttl, $record->data)
            );
        }
        return $recordSection;
    }

    private static function createFromReactDnsMessage(ReactDnsMessage $message): MessageInterface
    {
        $dnsMessageHeader = new Header(
            (int)$message->id,
            (bool)$message->qr,
            (int)$message->opcode,
            (bool)$message->aa,
            (bool)$message->tc,
            (bool)$message->rd,
            (bool)$message->ra,
            0, // TODO: it does not exist on React DNS message
            (int)$message->rcode
        );

        $questionSection = new Message\Section\QuestionSection();
        foreach ($message->questions as $query) {
            $questionSection->add(new Query($query->name, $query->type, $query->class));
        }

        return new Message(
            $dnsMessageHeader,
            $questionSection,
            static::mapResourceRecordSection($message->answers, new ResourceRecordSection()),
            static::mapResourceRecordSection($message->additional, new ResourceRecordSection()),
            static::mapResourceRecordSection($message->authority, new ResourceRecordSection())
        );
    }
}
