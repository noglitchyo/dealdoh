<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message;

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
    )
    {
        $this->id = $id;
        $this->qr = $qr;
        $this->opcode = $opcode;
        $this->aa = $aa;
        $this->tc = $tc;
        $this->rd = $rd;
        $this->ra = $ra;
        $this->z = $z;
        $this->rcode = $rcode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQdCount(): int
    {
        return count($this->questionSection->getQueries());
    }

    public function getAnCount(): int
    {
        return count($this->answerSection->getRecords());
    }

    public function getNsCount(): int
    {
        return count($this->authoritySection->getRecords());
    }

    public function getArCount(): int
    {
        return count($this->additionalSection->getRecords());
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
