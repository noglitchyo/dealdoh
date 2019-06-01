<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message\Section;

/**
 * @codeCoverageIgnore
 */
class Query implements QueryInterface
{
    /** @var string */
    private $name;

    /** @var int */
    private $type;

    /** @var int */
    private $class;

    public function __construct(string $name, int $type, int $class)
    {
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): int
    {
        return $this->class;
    }
}
