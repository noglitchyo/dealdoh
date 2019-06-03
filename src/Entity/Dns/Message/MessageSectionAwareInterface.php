<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;

/**
 * @codeCoverageIgnore
 */
interface MessageSectionAwareInterface
{
    public function setAdditionalSection(ResourceRecordSection $additionalSection): void;

    public function setQuestionSection(QuestionSection $additionalSection): void;

    public function setAnswerSection(ResourceRecordSection $additionalSection): void;

    public function setAuthoritySection(ResourceRecordSection $additionalSection): void;
}
