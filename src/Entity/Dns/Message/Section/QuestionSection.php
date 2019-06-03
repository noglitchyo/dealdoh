<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message\Section;

use JsonSerializable;

/**
 * @codeCoverageIgnore
 */
class QuestionSection implements JsonSerializable
{
    /**
     * @var QueryInterface[]
     */
    private $queries = [];

    public function add(QueryInterface $query): self
    {
        $this->queries[] = $query;

        return $this;
    }

    /**
     * @return QueryInterface[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }


    public function jsonSerialize(): array
    {
        return $this->queries;
    }
}
