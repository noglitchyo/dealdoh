<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message\Section;

use React\Dns\Model\Record as ReactDnsRecord;

/**
 * @codeCoverageIgnore
 */
class ResourceRecord extends ReactDnsRecord implements ResourceRecordInterface
{
    public function __construct(string $name, int $type, int $class, int $ttl = 0, $data = null)
    {
        parent::__construct($name, $type, $class, $ttl, $data);
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
