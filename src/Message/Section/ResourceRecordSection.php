<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message\Section;

class ResourceRecordSection
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
}