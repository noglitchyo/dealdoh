<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Message\Section;

use JsonSerializable;

/**
 * @codeCoverageIgnore
 */
class ResourceRecordSection implements JsonSerializable
{
    /**
     * @var ResourceRecordInterface[]
     */
    private $records = [];

    public function add(ResourceRecordInterface $record): self
    {
        $this->records[] = $record;

        return $this;
    }

    /**
     * @return ResourceRecordInterface[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function jsonSerialize(): array
    {
        return $this->records;
    }
}
