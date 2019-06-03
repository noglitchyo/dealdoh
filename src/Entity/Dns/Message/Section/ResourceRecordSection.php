<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message\Section;

/**
 * @codeCoverageIgnore
 */
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
