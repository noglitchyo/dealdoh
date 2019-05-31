<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message\Section;

/**
 * @codeCoverageIgnore
 */
class QuestionSection
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
}
