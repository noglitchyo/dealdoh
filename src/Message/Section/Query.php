<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message\Section;

use React\Dns\Query\Query as ReactDnsQuery;

/**
 * @codeCoverageIgnore
 */
class Query extends ReactDnsQuery implements QueryInterface
{
    public function __construct(string $name, int $type, int $class, ?int $currentTime = null)
    {
        parent::__construct($name, $type, $class, $currentTime);
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
