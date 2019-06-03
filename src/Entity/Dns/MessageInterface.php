<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns;

use JsonSerializable;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\MessageSectionAwareInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QueryInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;

/**
 * @see https://tools.ietf.org/html/rfc1035#section-4.1
 * @codeCoverageIgnore
 */
interface MessageInterface extends MessageSectionAwareInterface, JsonSerializable
{
    public function getHeader(): HeaderInterface;

    /**
     * @return QueryInterface[]
     */
    public function getQuestion(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAnswer(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAuthority(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAdditional(): array;
}
