<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message\Section;

/**
 * @codeCoverageIgnore
 */
class ResourceRecord implements ResourceRecordInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $class;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var string|string[]|array|null
     */
    private $data;

    public function __construct(string $name, int $type, int $class, int $ttl = 0, $data = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
        $this->ttl = $ttl;
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getClass(): int
    {
        return $this->class;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getData()
    {
        return $this->data;
    }
}
