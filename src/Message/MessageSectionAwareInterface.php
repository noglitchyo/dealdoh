<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message;

use NoGlitchYo\Dealdoh\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Message\Section\ResourceRecordSection;

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
