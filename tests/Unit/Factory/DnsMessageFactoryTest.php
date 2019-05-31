<?php declare(strict_types=1);

namespace Unit\Factory;

use NoGlitchYo\DoDoh\Factory\DnsMessageFactory;
use NoGlitchYo\DoDoh\Helper\Base64UrlCodecHelper;
use NoGlitchYo\DoDoh\Message\DnsMessage;
use NoGlitchYo\DoDoh\Message\DnsMessageInterface;
use NoGlitchYo\DoDoh\Message\Header;
use NoGlitchYo\DoDoh\Message\HeaderInterface;
use NoGlitchYo\DoDoh\Message\Section\Query;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecord;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecordInterface;
use PHPUnit\Framework\TestCase;
use React\Dns\Protocol\Parser;

/**
 * @covers \NoGlitchYo\DoDoh\Factory\DnsMessageFactory
 */
class DnsMessageFactoryTest extends TestCase
{
    /**
     * @var DnsMessageFactory
     */
    private $sut;

    /**
     * @var Parser
     */
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->sut = new DnsMessageFactory();
    }

    public function testCreateDnsWireMessageFromMessageReturnString(): void
    {
        $dnsMessage = new DnsMessage(new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK));
        $this->assertIsString($this->sut->createDnsWireMessageFromMessage($dnsMessage));
    }

    /**
     * @dataProvider provideDnsMessages
     */
    public function testCreateDnsWireMessageFromMessageReturnValidMessage(
        DnsMessageInterface $dnsMessage,
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
        DnsMessageInterface $expectedDnsMessage,
        string $dnsWireMessageBase64Encoded
    ): void {
        $message = $this->sut->createMessageFromBase64($dnsWireMessageBase64Encoded);

        $this->assertEquals($expectedDnsMessage, $message);
    }

    public function provideDnsMessages(): array
    {
        return [
            'simple message with header' => [
                new DnsMessage(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ),
                "AAABAAAAAAAAAAAA",
            ],
            'message with question'      => [
                (new DnsMessage(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addQuestion(new Query("test", 1, 1)),
                "AAABAAABAAAAAAAABHRlc3QAAAEAAQ",
            ],
            'message with answers'       => [
                (new DnsMessage(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addAnswer(new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')),
                "AAABAAAAAAEAAAAABHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with authority'     => [
                (new DnsMessage(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addAuthority(new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')),
                "AAABAAAAAAAAAQAABHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with additional'    => [
                (new DnsMessage(
                    new Header(0, false, 0, false, false, true, false, 0, HeaderInterface::RCODE_OK)
                ))->addAdditional(new ResourceRecord("test", ResourceRecordInterface::TYPE_A, 1, 60, '127.0.0.1')),
                "AAABAAAAAAAAAAABBHRlc3QAAAEAAQAAADwABH8AAAE",
            ],
            'message with all sections'  => [
                (new DnsMessage(
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
                "AAABAAABAAEAAQABBXF1ZXJ5AAABAAEGYW5zd2VyAAABAAEAAAA8AAR_AAABCWF1dGhvcml0eQAAAQABAAAAPAAEfwAAAQphZGRpdGlvbmFsAAABAAEAAAA8AAR_AAAB",
            ],
        ];
    }
}