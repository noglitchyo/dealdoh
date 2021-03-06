<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Factory\Dns;

use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Entity\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Factory\MessageFactory;
use NoGlitchYo\Dealdoh\Helper\UrlSafeBase64CodecHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\Dealdoh\Factory\MessageFactory
 */
class DnsMessageFactoryTest extends TestCase
{
    /**
     * @var \NoGlitchYo\Dealdoh\Factory\MessageFactory
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new MessageFactory();
    }

    public function testCreateDnsWireMessageFromMessageReturnString(): void
    {
        $dnsMessage = new \NoGlitchYo\Dealdoh\Entity\Message(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));
        $this->assertIsString($this->sut->createDnsWireMessageFromMessage($dnsMessage));
    }

    /**
     * @dataProvider provideDnsMessages
     */
    public function testCreateDnsWireMessageFromMessageReturnValidMessage(
        MessageInterface $dnsMessage,
        string $expectedDnsWireMessageBase64Encoded
    ): void {
        $this->markTestIncomplete('Need to be fixed');
        $this->assertSame(
            $expectedDnsWireMessageBase64Encoded,
            UrlSafeBase64CodecHelper::encode($this->sut->createDnsWireMessageFromMessage($dnsMessage))
        );
    }

    /**
     * @dataProvider provideDnsMessages
     */
    public function testCreateMessageFromDnsWireMessage(
        MessageInterface $expectedDnsMessage,
        string $dnsWireMessageBase64Encoded
    ): void {
        $message = $this->sut->createMessageFromDnsWireMessage(
            UrlSafeBase64CodecHelper::decode($dnsWireMessageBase64Encoded)
        );

        $this->assertEqualsWithDelta($expectedDnsMessage, $message, 1);
    }

    public function provideDnsMessages(): array
    {
        $resourceRecord = new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1');

        return [
            'simple message with header' => [
                new \NoGlitchYo\Dealdoh\Entity\Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ),
                "AAABAAAAAAAAAAAA",
            ],
            'message with question'      => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->withQuestionSection(
                    (new Message\Section\QuestionSection())
                        ->add(
                            new Query("test", ResourceRecordInterface::TYPE_A, ResourceRecordInterface::CLASS_IN)
                        )
                ),
                "AAABAAABAAAAAAAABHRlc3QAAAEAAQ",
            ],
            'message with answers'       => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->withAnswerSection(
                    (new Message\Section\ResourceRecordSection())->add($resourceRecord)
                ),
                "AAABAAAAAAEAAAAABHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with authority'     => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->withAuthoritySection(
                    (new Message\Section\ResourceRecordSection())->add($resourceRecord)
                ),
                "AAABAAAAAAAAAQAABHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with additional'    => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->withAdditionalSection(
                    (new Message\Section\ResourceRecordSection())->add($resourceRecord)
                ),
                "AAABAAAAAAAAAAABBHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with all sections'  => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->withQuestionSection(
                    (new Message\Section\QuestionSection())->add(
                        new Query("query", ResourceRecordInterface::TYPE_A, ResourceRecordInterface::CLASS_IN)
                    )
                )->withAnswerSection(
                    (new Message\Section\ResourceRecordSection())->add(
                        new ResourceRecord("answer", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')
                    )
                )->withAuthoritySection(
                    (new Message\Section\ResourceRecordSection())->add(
                        new ResourceRecord("authority", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')
                    )
                )->withAdditionalSection(
                    (new Message\Section\ResourceRecordSection())->add(
                        new ResourceRecord("additional", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')
                    )
                ),
                "AAABAAABAAEAAQABBXF1ZXJ5AAABAAEGYW5zd2VyAAABAAEAAAA8AAR_AAABCWF1dGhvcml0eQAAAQABAAAAPAAEfwAAAQphZGRp" .
                "dGlvbmFsAAABAAEAAAA8AAR_AAAB",
            ],
        ];
    }
}
