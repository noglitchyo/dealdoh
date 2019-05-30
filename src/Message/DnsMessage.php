<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message;

class DnsMessage implements DnsMessageInterface
{
    use MessageSectionAwareTrait;

    /** @var Header */
    private $header;

    public function __construct(HeaderInterface $header)
    {
        $this->header = $header;
    }

    public function getHeader(): Header
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

    public function addAnswer(RecordInterface $answer): self
    {
        $this->answerSection->add($answer);

        return $this;
    }

    public function addAuthority(RecordInterface $authority): self
    {
        $this->authoritySection->add($authority);

        return $this;
    }

    public function addAdditional(RecordInterface $additional): self
    {
        $this->additionalSection->add($additional);

        return $this;
    }
}
