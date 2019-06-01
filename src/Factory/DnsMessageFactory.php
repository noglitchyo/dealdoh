<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory;

use NoGlitchYo\Dealdoh\Helper\Base64UrlCodecHelper;
use NoGlitchYo\Dealdoh\Message\DnsMessage;
use NoGlitchYo\Dealdoh\Message\DnsMessageInterface;
use NoGlitchYo\Dealdoh\Message\Header;
use NoGlitchYo\Dealdoh\Message\Section\Query;
use NoGlitchYo\Dealdoh\Message\Section\ResourceRecord;
use React\Dns\Model\HeaderBag;
use React\Dns\Model\Message;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Protocol\Parser;

class DnsMessageFactory implements DnsMessageFactoryInterface
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

    private static function createFromMessage(Message $message): DnsMessageInterface
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
        $dnsMessage = new DnsMessage($dnsMessageHeader);

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

    public function createMessageFromDnsWireMessage(string $dnsWireMessage): DnsMessageInterface
    {
        return self::createFromMessage($this->parser->parseMessage($dnsWireMessage));
    }

    /**
     * Return a DNS message in wire format as defined in RFC-1035
     *
     * @param DnsMessageInterface $dnsMessage
     *
     * @return string
     */
    public function createDnsWireMessageFromMessage(DnsMessageInterface $dnsMessage): string
    {
        $message = new Message();
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

        foreach ($dnsMessage->getQuestions() as $query) {
            $message->questions[] = [
                'name'  => $query->getName(),
                'class' => $query->getClass(),
                'type'  => $query->getType(),
            ];
        }

        $message->answers = $dnsMessage->getAnswers();
        $message->authority = $dnsMessage->getAuthority();
        $message->additional = $dnsMessage->getAdditional();

        return $this->binaryDumper->toBinary($message);
    }

    public function createMessageFromBase64(string $query): DnsMessageInterface
    {
        return $this->createMessageFromDnsWireMessage(Base64UrlCodecHelper::decode($query));
    }
}
