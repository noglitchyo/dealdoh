<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

/**
 * @codeCoverageIgnore
 */
class Header implements HeaderInterface
{
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

    /**
     * @var MessageInterface
     */
    private $message;

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
    }

    public function withMessage(MessageInterface $message): HeaderInterface
    {
        $new = clone $this;
        $new->message = $message;

        return $new;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQdCount(): int
    {
        return count($this->message->getQuestion());
    }

    public function getAnCount(): int
    {
        return count($this->message->getAnswer());
    }

    public function getNsCount(): int
    {
        return count($this->message->getAuthority());
    }

    public function getArCount(): int
    {
        return count($this->message->getAdditional());
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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'qr' => $this->qr,
            'opcode' => $this->opcode,
            'aa' => $this->aa,
            'tc' => $this->tc,
            'rd' => $this->rd,
            'ra' => $this->ra,
            'z' => $this->z,
            'rcode' => $this->rcode,
            'qdCount' => $this->getQdCount(),
            'anCount' => $this->getAnCount(),
            'nsCount' => $this->getNsCount(),
            'arCount' => $this->getArCount(),
        ];
    }
}
