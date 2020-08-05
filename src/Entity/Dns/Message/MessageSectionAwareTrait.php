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

    public function withQuestionSection(QuestionSection $question)
    {
        $new = clone $this;
        $new->questionSection = $question;

        return $new;
    }

    public function withAnswerSection(ResourceRecordSection $answerSection)
    {
        $new = clone $this;
        $new->answerSection = $answerSection;

        return $new;
    }

    public function withAdditionalSection(ResourceRecordSection $additionalSection)
    {
        $new = clone $this;
        $new->additionalSection = $additionalSection;

        return $new;
    }

    public function withAuthoritySection(ResourceRecordSection $authoritySection)
    {
        $new = clone $this;
        $new->authoritySection = $authoritySection;

        return $new;
    }
}
