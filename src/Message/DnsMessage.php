<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message;

use NoGlitchYo\Dealdoh\Message\Section\QueryInterface;
use NoGlitchYo\Dealdoh\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Message\Section\ResourceRecordSection;

/**
 * @codeCoverageIgnore
 */
class DnsMessage implements DnsMessageInterface
{
    use MessageSectionAwareTrait;

    /** @var Header */
    private $header;

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

    public function __construct(HeaderInterface $header)
    {
        $this->header = $header;

        $this->setQuestionSection(new QuestionSection());
        $this->setAnswerSection(new ResourceRecordSection());
        $this->setAdditionalSection(new ResourceRecordSection());
        $this->setAuthoritySection(new ResourceRecordSection());

        $this->header->setQuestionSection($this->getQuestionSection());
        $this->header->setAnswerSection($this->getAnswerSection());
        $this->header->setAuthoritySection($this->getAuthoritySection());
        $this->header->setAdditionalSection($this->getAdditionalSection());

    }

    public function getHeader(): HeaderInterface
    {
        return $this->header;
    }

    public function getQuestions(): array
    {
        return $this->questionSection->getQueries();
    }

    public function getAnswers(): array
    {
        return $this->answerSection->getRecords();
    }

    public function getAuthority(): array
    {
        return $this->authoritySection->getRecords();
    }

    public function getAdditional(): array
    {
        return $this->additionalSection->getRecords();
    }

    public function addQuestion(QueryInterface $query): self
    {
        $this->questionSection->add($query);

        return $this;
    }

    public function addAnswer(ResourceRecordInterface $answer): self
    {
        $this->answerSection->add($answer);

        return $this;
    }

    public function addAuthority(ResourceRecordInterface $authority): self
    {
        $this->authoritySection->add($authority);

        return $this;
    }

    public function addAdditional(ResourceRecordInterface $additional): self
    {
        $this->additionalSection->add($additional);

        return $this;
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
