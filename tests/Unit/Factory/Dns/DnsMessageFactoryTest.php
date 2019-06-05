<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Factory\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;
use NoGlitchYo\Dealdoh\Helper\Base64UrlCodecHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory
 */
class DnsMessageFactoryTest extends TestCase
{
    /**
     * @var MessageFactory
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new MessageFactory();
    }

    public function testCreateDnsWireMessageFromMessageReturnString(): void
    {
        $dnsMessage = new Message(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));
        $this->assertIsString($this->sut->createDnsWireMessageFromMessage($dnsMessage));
    }

    /**
     * @dataProvider provideDnsMessages
     */
    public function testCreateDnsWireMessageFromMessageReturnValidMessage(
        MessageInterface $dnsMessage,
        string $expectedDnsWireMessageBase64Encoded
    ): void {
        $this->assertSame(
            $expectedDnsWireMessageBase64Encoded,
            Base64UrlCodecHelper::encode($this->sut->createDnsWireMessageFromMessage($dnsMessage))
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
            Base64UrlCodecHelper::decode($dnsWireMessageBase64Encoded)
        );

        $this->assertEqualsWithDelta($expectedDnsMessage, $message, 1);
    }

    public function provideDnsMessages(): array
    {
        return [
            'simple message with header' => [
                new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ),
                "AAABAAAAAAAAAAAA",
            ],
            'message with question'      => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addQuestion(new Query("test", 1, 1)),
                "AAABAAABAAAAAAAABHRlc3QAAAEAAQ",
            ],
            'message with answers'       => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addAnswer(new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')),
                "AAABAAAAAAEAAAAABHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with authority'     => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addAuthority(new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')),
                "AAABAAAAAAAAAQAABHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with additional'    => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addAdditional(new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')),
                "AAABAAAAAAAAAAABBHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with all sections'  => [
                (new Message(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addQuestion(
                    new Query("query", 1, 1)
                )->addAnswer(
                    new ResourceRecord("answer", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')
                )->addAuthority(
                    new ResourceRecord("authority", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')
                )->addAdditional(
                    new ResourceRecord("additional", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')
                ),
                "AAABAAABAAEAAQABBXF1ZXJ5AAABAAEGYW5zd2VyAAABAAEAAAA8AAR_AAABCWF1dGhvcml0eQAAAQABAAAAPAAEfwAAAQphZGRp" .
                "dGlvbmFsAAABAAEAAAA8AAR_AAAB",
            ],
        ];
    }
}
