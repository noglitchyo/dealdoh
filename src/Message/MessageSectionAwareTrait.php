<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message;

use NoGlitchYo\DoDoh\Message\Section\QuestionSection;
use NoGlitchYo\DoDoh\Message\Section\ResourceRecordSection;

trait MessageSectionAwareTrait
{
    /**
     * @var QuestionSection
     */
    private $questionSection;

    /**
     * @var ResourceRecordSection
     */
    private $additionalSection;

    /**
     * @var ResourceRecordSection
     */
    private $answerSection;

    /**
     * @var ResourceRecordSection
     */
    private $authoritySection;

    public function setAdditionalSection(ResourceRecordSection $additionalSection): void
    {
        $this->additionalSection = $additionalSection;
    }

    public function setQuestionSection(QuestionSection $additionalSection): void
    {
        $this->questionSection = $additionalSection;
    }

    public function setAnswerSection(ResourceRecordSection $additionalSection): void
    {
        $this->answerSection = $additionalSection;
    }

    public function setAuthoritySection(ResourceRecordSection $additionalSection): void
    {
        $this->authoritySection = $additionalSection;
    }
}