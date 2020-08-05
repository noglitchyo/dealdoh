<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Mapper\GoogleDns;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use NoGlitchYo\Dealdoh\Entity\Dns\Message;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\Query;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecord;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\GoogleDns\MessageMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoGlitchYo\Dealdoh\Mapper\GoogleDns\MessageMapper
 */
class MessageMapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MessageMapper */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new MessageMapper();
    }

    /**
     * @dataProvider provideRawResults
     */
    public function testMapReturnMappedMessage(MessageInterface $expectedMessage, array $rawData): void
    {
        $this->assertJsonStringEqualsJsonString(json_encode($expectedMessage), json_encode($this->sut->map($rawData)));
    }

    public function provideRawResults(): array
    {
        return [
            [
                // Expected message
                (new Message(new Header(0, true, 0, false, false, true, true, 0, 0)))
                    ->withQuestionSection(
                        (new Message\Section\QuestionSection())
                            ->add(new Query('apple.com', 1, ResourceRecordInterface::CLASS_IN))
                    )
                    ->withAnswerSection(
                        (new Message\Section\ResourceRecordSection())
                            ->add(
                                new ResourceRecord(
                                    'apple.com',
                                    1,
                                    ResourceRecordInterface::CLASS_IN,
                                    3599,
                                    '17.178.96.59'
                                )
                            )
                            ->add(
                                new ResourceRecord(
                                    'apple.com',
                                    1,
                                    ResourceRecordInterface::CLASS_IN,
                                    3750,
                                    '17.142.160.59'
                                )
                            )
                            ->add(
                                new ResourceRecord(
                                    'apple.com',
                                    1,
                                    ResourceRecordInterface::CLASS_IN,
                                    200,
                                    '17.142.160.59'
                                )
                            )
                    )
                    ->withAdditionalSection(
                        (new Message\Section\ResourceRecordSection())
                            ->add(
                                new ResourceRecord(
                                    'grossepomme.com',
                                    1,
                                    ResourceRecordInterface::CLASS_IN,
                                    3599,
                                    '17.141.160.59'
                                )
                            )
                    ),
                // Raw data
                [
                    'Status'     => 0,
                    'TC'         => false,
                    'RD'         => true,
                    'RA'         => true,
                    'AD'         => false,
                    'CD'         => false,
                    'Question'   => [
                        [
                            'name' => 'apple.com',
                            'type' => 1,
                        ],
                    ],
                    'Answer'     => [
                        [
                            'name' => 'apple.com',
                            'type' => 1,
                            'TTL'  => 3599,
                            'data' => '17.178.96.59',
                        ],
                        [
                            'name' => 'apple.com',
                            'type' => 1,
                            'TTL'  => 3750,
                            'data' => '17.142.160.59',
                        ],
                        [
                            'name' => 'apple.com',
                            'type' => 1,
                            'TTL'  => 200,
                            'data' => '17.142.160.59',
                        ],
                    ],
                    'Additional' => [
                        [
                            'name' => 'grossepomme.com',
                            'type' => 1,
                            'TTL'  => 3599,
                            'data' => '17.141.160.59',
                        ],
                    ],
                ],
            ],
        ];
    }
}
