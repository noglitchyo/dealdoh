<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message;

use NoGlitchYo\Dealdoh\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Message\Section\ResourceRecordSection;

/**
 * @codeCoverageIgnore
 */
class Header implements HeaderInterface
{
    use MessageSectionAwareTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var bool
     */
    private $qr;

    /**
     * @var int
     */
    private $opcode;

    /**
     * @var bool
     */
    private $aa;

    /**
     * @var bool
     */
    private $tc;

    /**
     * @var bool
     */
    private $rd;

    /**
     * @var bool
     */
    private $ra;

    /**
     * @var int
     */
    private $z;

    /**
     * @var int
     */
    private $rcode;

    public function __construct(
        int $id,
        bool $qr,
        int $opcode,
        bool $aa,
        bool $tc,
        bool $rd,
        bool $ra,
        int $z,
        int $rcode
    ) {
        $this->id = $id;
        $this->qr = $qr;
        $this->opcode = $opcode;
        $this->aa = $aa;
        $this->tc = $tc;
        $this->rd = $rd;
        $this->ra = $ra;
        $this->z = $z;
        $this->rcode = $rcode;
        $this->setQuestionSection(new QuestionSection());
        $this->setAnswerSection(new ResourceRecordSection());
        $this->setAdditionalSection(new ResourceRecordSection());
        $this->setAuthoritySection(new ResourceRecordSection());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQdCount(): int
    {
        return $this->questionSection ? count($this->questionSection->getQueries()) : 0;
    }

    public function getAnCount(): int
    {
        return $this->answerSection ? count($this->answerSection->getRecords()) : 0;
    }

    public function getNsCount(): int
    {
        return $this->authoritySection ? count($this->authoritySection->getRecords()) : 0;
    }

    public function getArCount(): int
    {
        return $this->additionalSection ? count($this->additionalSection->getRecords()) : 0;
    }

    public function isQr(): bool
    {
        return $this->qr;
    }

    public function getOpcode(): int
    {
        return $this->opcode;
    }

    public function isAa(): bool
    {
        return $this->aa;
    }

    public function isTc(): bool
    {
        return $this->tc;
    }

    public function isRd(): bool
    {
        return $this->rd;
    }

    public function isRa(): bool
    {
        return $this->ra;
    }

    public function getZ(): int
    {
        return $this->z;
    }

    public function getRcode(): int
    {
        return $this->rcode;
    }
}
