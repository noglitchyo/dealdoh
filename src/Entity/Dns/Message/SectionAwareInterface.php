<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface SectionAwareInterface
{
    /**
     * @param QuestionSection $questionSection
     *
     * @return MessageInterface
     */
    public function withQuestionSection(QuestionSection $questionSection);

    /**
     * @param ResourceRecordSection $answerSectionSection
     *
     * @return MessageInterface
     */
    public function withAnswerSection(ResourceRecordSection $answerSectionSection);

    /**
     * @param ResourceRecordSection $authoritySectionSection
     *
     * @return MessageInterface
     */
    public function withAuthoritySection(ResourceRecordSection $authoritySectionSection);

    /**
     * @param ResourceRecordSection $additionalSectionSection
     *
     * @return MessageInterface
     */
    public function withAdditionalSection(ResourceRecordSection $additionalSectionSection);
}
