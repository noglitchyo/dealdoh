<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Factory;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use NoGlitchYo\Dealdoh\Factory\DnsUpstreamFactory;
use NoGlitchYo\Dealdoh\Mapper\HttpResponseMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\Dealdoh\Mapper\HttpResponseMapper
 */
class DnsUpstreamFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HttpResponseMapper
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new DnsUpstreamFactory();
    }

    public function testThatCreateRecognizeDnsStamp(): void
    {
        $this->markTestIncomplete();
    }
}
