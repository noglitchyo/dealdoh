<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message;

use NoGlitchYo\Dealdoh\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Message\Section\ResourceRecordSection;

/**
 * @codeCoverageIgnore
 */
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

    public function setQuestionSection(QuestionSection $section): void
    {
        $this->questionSection = $section;
    }

    public function setAdditionalSection(ResourceRecordSection $section): void
    {
        $this->additionalSection = $section;
    }

    public function setAnswerSection(ResourceRecordSection $section): void
    {
        $this->answerSection = $section;
    }

    public function setAuthoritySection(ResourceRecordSection $section): void
    {
        $this->authoritySection = $section;
    }
}
