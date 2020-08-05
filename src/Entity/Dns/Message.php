<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\MessageSectionAwareTrait;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactory;

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

    /**
     * @param HeaderInterface            $header
     * @param QuestionSection|null       $questionSection
     * @param ResourceRecordSection|null $answerSection
     * @param ResourceRecordSection|null $additionalSection
     * @param ResourceRecordSection|null $authoritySection
     */
    public function __construct(
        HeaderInterface $header,
        QuestionSection $questionSection = null, // TODO: Does a question should always be mandatory? Good question!
        ResourceRecordSection $answerSection = null,
        ResourceRecordSection $additionalSection = null,
        ResourceRecordSection $authoritySection = null
    ) {
        $this->header = $header;
        $this->questionSection = $questionSection ?? new QuestionSection();
        $this->answerSection = $answerSection ?? new ResourceRecordSection();
        $this->additionalSection = $additionalSection ?? new ResourceRecordSection();
        $this->authoritySection = $authoritySection ?? new ResourceRecordSection();

        // TODO: these methods does not guarantee immutability on section of the headers
        $this->header = $this->header->withQuestionSection($this->questionSection);
        $this->header = $this->header->withAnswerSection($this->answerSection);
        $this->header = $this->header->withAuthoritySection($this->authoritySection);
        $this->header = $this->header->withAdditionalSection($this->additionalSection);
    }

    /**
     * @param bool $isResponse
     * @param int  $rcode
     *
     * @deprecated Use MessageFactory::create() instead.
     * @see MessageFactory::create()
     *
     * @return static
     */
    public static function createWithDefaultHeader(
        bool $isResponse = false,
        int $rcode = HeaderInterface::RCODE_OK
    ): self {
        return new static(new Header(0, $isResponse, 0, false, false, false, false, 0, $rcode));
    }

    public function withHeader(HeaderInterface $header): MessageInterface
    {
        $new = clone $this;

        $header = $header->withQuestionSection($this->questionSection);
        $header = $header->withAnswerSection($this->answerSection);
        $header = $header->withAuthoritySection($this->authoritySection);
        $header = $header->withAdditionalSection($this->additionalSection);

        $new->header = $header;

        return $new;
    }

    public function withQuestionSection(QuestionSection $questionSection)
    {
        $new = clone $this;
        $new->questionSection = $questionSection;
        $new->header = $this->header->withQuestionSection($questionSection);

        return $new;
    }

    public function withAnswerSection(ResourceRecordSection $answerSection)
    {
        $new = clone $this;
        $new->answerSection = $answerSection;
        $new->header = $this->header->withAnswerSection($answerSection);

        return $new;
    }

    public function withAdditionalSection(ResourceRecordSection $additionalSection)
    {
        $new = clone $this;
        $new->additionalSection = $additionalSection;
        $new->header = $this->header->withAdditionalSection($additionalSection);

        return $new;
    }

    public function withAuthoritySection(ResourceRecordSection $authoritySection)
    {
        $new = clone $this;
        $new->authoritySection = $authoritySection;
        $new->header = $this->header->withAuthoritySection($authoritySection);

        return $new;
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

    public function jsonSerialize(): array
    {
        return [
            'header'     => $this->header,
            'question'   => $this->questionSection,
            'answer'     => $this->answerSection,
            'authority'  => $this->authoritySection,
            'additional' => $this->additionalSection,
        ];
    }
}
