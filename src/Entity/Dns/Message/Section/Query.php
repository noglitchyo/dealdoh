<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message\Section;

/**
 * @codeCoverageIgnore
 */
class Query implements QueryInterface
{
    /**
     * @var string
     */
    private $qname;

    /**
     * @var int
     */
    private $qtype;

    /**
     * @var int
     */
    private $qclass;

    public function __construct(string $name, int $type, int $class)
    {
        $this->qname = $name;
        $this->qtype = $type;
        $this->qclass = $class;
    }

    public function getQtype(): int
    {
        return $this->qtype;
    }

    public function getQname(): string
    {
        return $this->qname;
    }

    public function getQclass(): int
    {
        return $this->qclass;
    }

    public function jsonSerialize(): array
    {
        return [
            'qname' => $this->qname,
            'qtype' => $this->qtype,
            'qclass' => $this->qclass,
        ];
    }
}
