<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Command;

use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use NoGlitchYo\Dealdoh\Command\ResolveCommand;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Service\DnsResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ResolveCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var DnsResolverInterface|MockObject */
    private $dnsResolverMock;

    /** @var MessageFactoryInterface|MockObject */
    private $messageFactoryMock;

    /** @var ResolveCommand */
    private $sut;

    /** @var CommandTester */
    private $commandTester;

    /** @var Application */
    private $application;

    protected function setUp(): void
    {
        $this->dnsResolverMock = $this->createMock(DnsResolverInterface::class);
        $this->messageFactoryMock = $this->createMock(MessageFactoryInterface::class);
        $this->sut = new ResolveCommand($this->dnsResolverMock, $this->messageFactoryMock);
        $this->commandTester = new CommandTester($this->sut);
    }

    public function testExecuteValidateQtypeParameter()
    {
        $queryType = 'aaa';
        $queryName = 'domain.com';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`aaa` is not a valid query type.');

        $this->commandTester->execute([ResolveCommand::NAME, 'qtype' => $queryType, 'qname' => $queryName]);
    }

    public function testExecuteCallResolverWithDnsMessage()
    {
        $queryType = 'AAAA';
        $queryName = 'domain.com';

        $dnsMessage = (Message::createWithDefaultHeader())
            ->addQuestion(new Query($queryName, ResourceRecordInterface::TYPE_AAAA, ResourceRecordInterface::CLASS_IN));

        $this->dnsResolverMock
            ->expects($this->once())
            ->method('resolve')
            ->with($dnsMessage);

        $this->commandTester->execute([ResolveCommand::NAME, 'qtype' => $queryType, 'qname' => $queryName]);
    }
}
