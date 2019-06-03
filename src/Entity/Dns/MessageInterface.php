<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\MessageSectionAwareInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QueryInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;

/**
 * @codeCoverageIgnore
 */
interface MessageInterface extends MessageSectionAwareInterface
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
