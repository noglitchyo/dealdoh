<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message;

use NoGlitchYo\DoDoh\Message\Section\QuestionSection;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecordSection;

interface MessageSectionAwareInterface
{
    public function setAdditionalSection(ResourceRecordSection $additionalSection): void;

    public function setQuestionSection(QuestionSection $additionalSection): void;

    public function setAnswerSection(ResourceRecordSection $additionalSection): void;

    public function setAuthoritySection(ResourceRecordSection $additionalSection): void;
}
