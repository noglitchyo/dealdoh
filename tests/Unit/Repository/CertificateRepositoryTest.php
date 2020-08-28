<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Repository;

use Mockery;
use NoGlitchYo\Dealdoh\Dns\Client\PlainDnsClient;
use NoGlitchYo\Dealdoh\Repository\DnsCrypt\CertificateRepository;
use PHPUnit\Framework\TestCase;

class CertificateRepositoryTest extends TestCase
{
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlainDnsClient
     */
    private $stdClientMock;

    /**
     * @var CertificateRepository
     */
    private $sut;

    public function setUp(): void
    {
        $this->stdClientMock = Mockery::mock(PlainDnsClient::class);
        $this->sut = new CertificateRepository($this->stdClientMock);
        parent::setUp();
    }

    public function testThatQueryIsSendOverTcpWhenFailureOverUdp()
    {
        $this->markTestIncomplete();
    }

    public function testThatQueryIsSendOverTcpWhenTruncationOverUdp()
    {
        $this->markTestIncomplete();
    }

    public function testThatQueryIsSendOverTcpWhenTimeoutOverUdp()
    {
        $this->markTestIncomplete();
    }

    public function testThatGetCertificatesPickCertificateWithHighestSerial()
    {

    }
}
