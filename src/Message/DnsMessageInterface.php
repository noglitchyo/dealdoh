<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message;

use NoGlitchYo\DoDoh\Message\Section\QueryInterface;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecordInterface;

/**
 * @codeCoverageIgnore
 */
interface DnsMessageInterface extends MessageSectionAwareInterface
{
    public function getHeader(): HeaderInterface;

    /**
     * @return QueryInterface[]
     */
    public function getQuestions(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAnswers(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAuthority(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAdditional(): array;
}
