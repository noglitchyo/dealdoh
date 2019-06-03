<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns\Message;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;

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

    public function getQuestionSection(): QuestionSection
    {
        return $this->questionSection;
    }

    public function getAdditionalSection(): ResourceRecordSection
    {
        return $this->additionalSection;
    }

    public function getAnswerSection(): ResourceRecordSection
    {
        return $this->answerSection;
    }

    public function getAuthoritySection(): ResourceRecordSection
    {
        return $this->authoritySection;
    }
}
