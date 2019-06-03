<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use React\Dns\Model\HeaderBag;
use React\Dns\Model\Message as DnsMessage;
use React\Dns\Model\Record;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Protocol\Parser;

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

    private static function createFromMessage(DnsMessage $message): MessageInterface
    {
        $dnsMessageHeader = new Header(
            (int)$message->header->get('id'),
            (bool)$message->header->get('qr'),
            (int)$message->header->get('opcode'),
            (bool)$message->header->get('aa'),
            (bool)$message->header->get('tc'),
            (bool)$message->header->get('rd'),
            (bool)$message->header->get('ra'),
            (int)$message->header->get('z'),
            (int)$message->header->get('rcode')
        );
        $dnsMessage = new Message($dnsMessageHeader);

        foreach ($message->questions as $query) {
            $dnsMessage->addQuestion(new Query($query['name'], $query['type'], $query['class']));
        }

        foreach ($message->answers as $record) {
            $dnsMessage->addAnswer(
                new ResourceRecord($record->name, $record->type, $record->class, $record->ttl, $record->data)
            );
        }

        foreach ($message->authority as $record) {
            $dnsMessage->addAuthority(
                new ResourceRecord($record->name, $record->type, $record->class, $record->ttl, $record->data)
            );
        }

        foreach ($message->additional as $record) {
            $dnsMessage->addAdditional(
                new ResourceRecord($record->name, $record->type, $record->class, $record->ttl, $record->data)
            );
        }

        return $dnsMessage;
    }

    public function createMessageFromDnsWireMessage(string $dnsWireMessage): MessageInterface
    {
        return self::createFromMessage($this->parser->parseMessage($dnsWireMessage));
    }

    /**
     * Return a DNS message in wire format as defined in RFC-1035
     *
     * @param MessageInterface $dnsMessage
     *
     * @return string
     */
    public function createDnsWireMessageFromMessage(MessageInterface $dnsMessage): string
    {
        $message = new DnsMessage();
        $header = new HeaderBag();
        $dnsHeader = $dnsMessage->getHeader();

        $header->set('id', $dnsHeader->getId());
        $header->set('opcode', $dnsHeader->getOpcode());
        $header->set('aa', (int)$dnsHeader->isAa());
        $header->set('tc', (int)$dnsHeader->isTc());
        $header->set('rd', (int)$dnsHeader->isRd());
        $header->set('ra', (int)$dnsHeader->isRa());
        $header->set('z', (int)$dnsHeader->getZ());
        $header->set('rcode', $dnsHeader->getRcode());
        $header->set('qdCount', $dnsHeader->getQdCount());
        $header->set('anCount', $dnsHeader->getAnCount());
        $header->set('arCount', $dnsHeader->getArCount());
        $header->set('nsCount', $dnsHeader->getNsCount());

        $message->header = $header;

        foreach ($dnsMessage->getQuestion() as $query) {
            $message->questions[] = [
                'name' => $query->getQname(),
                'class' => $query->getQclass(),
                'type' => $query->getQtype(),
            ];
        }

        foreach ($dnsMessage->getAnswer() as $record) {
            $message->answers[] = new Record(
                $record->getName(),
                $record->getType(),
                $record->getClass(),
                $record->getTtl(),
                $record->getData()
            );
        }

        foreach ($dnsMessage->getAuthority() as $record) {
            $message->answers[] = new Record(
                $record->getName(),
                $record->getType(),
                $record->getClass(),
                $record->getTtl(),
                $record->getData()
            );
        }

        foreach ($dnsMessage->getAdditional() as $record) {
            $message->answers[] = new Record(
                $record->getName(),
                $record->getType(),
                $record->getClass(),
                $record->getTtl(),
                $record->getData()
            );
        }

        return $this->binaryDumper->toBinary($message);
    }
}
