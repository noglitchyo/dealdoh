<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\MessageSectionAwareTrait;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QueryInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;

/**
 * @codeCoverageIgnore
 */
class Message implements MessageInterface
{
    use MessageSectionAwareTrait;

    /**
     * @var HeaderInterface
     */
    private $header;

    public function __construct(HeaderInterface $header)
    {
        $this->header = $header;

        $this->setQuestionSection(new QuestionSection());
        $this->setAnswerSection(new ResourceRecordSection());
        $this->setAdditionalSection(new ResourceRecordSection());
        $this->setAuthoritySection(new ResourceRecordSection());

        $this->header->setQuestionSection($this->questionSection);
        $this->header->setAnswerSection($this->answerSection);
        $this->header->setAuthoritySection($this->authoritySection);
        $this->header->setAdditionalSection($this->additionalSection);
    }

    public static function createWithDefaultHeader(
        bool $isResponse = false,
        int $rcode = HeaderInterface::RCODE_OK
    ): self {
        return new static(new Header(0, $isResponse, 0, false, false, false, false, 0, $rcode));
    }

    public function getHeader(): HeaderInterface
    {
        return $this->header;
    }

    public function getQuestion(): array
    {
        return $this->questionSection->getQueries();
    }

    public function getAnswer(): array
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

    public function jsonSerialize(): array
    {
        return [
            'header' => $this->header,
            'question' => $this->questionSection,
            'answer' => $this->answerSection,
            'authority' => $this->authoritySection,
            'additional' => $this->additionalSection
        ];
    }
}
