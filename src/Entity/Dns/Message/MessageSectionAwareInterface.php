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

    public function getAdditionalSection(): ResourceRecordSection;

    public function setQuestionSection(QuestionSection $questionSection): void;

    public function getQuestionSection(): QuestionSection;

    public function setAnswerSection(ResourceRecordSection $answerSection): void;

    public function getAnswerSection(): ResourceRecordSection;

    public function setAuthoritySection(ResourceRecordSection $authoritySection): void;

    public function getAuthoritySection(): ResourceRecordSection;
}
