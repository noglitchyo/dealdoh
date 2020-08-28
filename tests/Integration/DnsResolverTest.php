<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Integration;

use NoGlitchYo\Dealdoh\Dns\Client\PlainDnsClient;
use NoGlitchYo\Dealdoh\Dns\Resolver\DnsUpstreamPoolResolver;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamPool;
use NoGlitchYo\Dealdoh\Entity\Message;
use NoGlitchYo\Dealdoh\Entity\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Factory\MessageFactory;
use NoGlitchYo\Dealdoh\Helper\UrlSafeBase64CodecHelper;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;
use NoGlitchYo\Dealdoh\Tests\Stub\DnsServerStub;
use NoGlitchYo\Dealdoh\Tests\Stub\DnsServerStubManager;
use PHPUnit\Framework\TestCase;
use Throwable;

class DohProxyTest extends TestCase
{
    /**
     * @var DnsUpstreamPoolResolver
     */
    private $sut;

    /**
     * @var \NoGlitchYo\Dealdoh\Factory\MessageFactory
     */
    private $messageFactory;

    /**
     * @var DnsServerStubManager
     */
    private $dnsServerStubManager;

    public function setUp(): void
    {
        $this->messageFactory = new MessageFactory();
        $this->dnsServerStubManager = new DnsServerStubManager();

        parent::setUp();
    }

    public function tearDown(): void
    {
        $process = $this->dnsServerStubManager->getProcess();
        $process->stop();
    }

    public function testThatDnsResolverCanResolveFromUdpUpstream()
    {
        $stubManager = $this->dnsServerStubManager;
        // Prepare DNS query
        $questionSection = (new Message\Section\QuestionSection())->add(
            new Query('google.fr', ResourceRecordInterface::TYPE_A, ResourceRecordInterface::CLASS_IN)
        );
        $dnsQueryMessage = ($this->messageFactory->create())->withQuestionSection($questionSection);

        $header = $dnsQueryMessage->getHeader();
        // Create a fake DNS response message from the DNS query message (only difference is that QR = 1)
        $expectedDnsResponseMessage = $dnsQueryMessage
            ->withHeader(
                new Header(
                    $header->getId(),
                    true,
                    $header->getOpcode(),
                    $header->isAa(),
                    $header->isTc(),
                    $header->isRd(),
                    $header->isRa(),
                    $header->getZ(),
                    $header->getRcode()
                )
            );

        $dnsServerAddress = $stubManager->create($expectedDnsResponseMessage);

        $dnsUpstreamPool = new DnsUpstreamPool(
            [
                [
                    'code' => 'google',
                    'uri'  => 'udp://' . $dnsServerAddress,
                ],
            ]
        );

        $dnsClients = [
            new PlainDnsClient($this->messageFactory, new DnsOverTcpTransport(), new DnsOverUdpTransport()),
        ];

        $this->sut = new DnsUpstreamPoolResolver($dnsUpstreamPool, $dnsClients);

        try {
            $dnsResource = $this->sut->resolve($dnsQueryMessage);
        } catch (Throwable $exception) {
            $exception->getMessage();
        }

        $action = $this->parseDnsServerOutput($stubManager->getProcess()->getIncrementalOutput());

        // Assert that server receives DNS message
        $this->assertSame(DnsServerStub::RECEIVE_ACTION, $action['name']);
        $dnsMessage = $this->messageFactory->createMessageFromDnsWireMessage(
            UrlSafeBase64CodecHelper::decode($action['data']['message'])
        );
        $this->assertFalse(
            $dnsMessage->getHeader()->isQr(),
            "DNS message sent to the server should be a DNS query and have QR = 0."
        );

        $dnsResponseMessage = $dnsResource->getResponse();
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedDnsResponseMessage),
            json_encode($dnsResponseMessage),
            'DNS message sent from server should be a DNS response and have QR = 1.'
        );
    }

    private function parseDnsServerOutput(string $output)
    {
        return json_decode($output, true);
    }
}
