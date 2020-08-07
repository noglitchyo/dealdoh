<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use NoGlitchYo\Dealdoh\Entity\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Message\Section\ResourceRecordSection;
use NoGlitchYo\Dealdoh\Factory\MessageFactory;

/**
 * @codeCoverageIgnore
 */
class Message implements MessageInterface
{
    /**
     * @var ResourceRecordSection|null *
     */
    private $authoritySection;

    /**
     * @var QuestionSection|null
     */
    private $questionSection;

    /**
     * @var ResourceRecordSection|null
     */
    private $answerSection;

    /**
     * @var ResourceRecordSection|null
     */
    private $additionalSection;

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
        QuestionSection $questionSection = null,
        ResourceRecordSection $answerSection = null,
        ResourceRecordSection $additionalSection = null,
        ResourceRecordSection $authoritySection = null
    ) {
        $this->questionSection = $questionSection ?? new QuestionSection();
        $this->answerSection = $answerSection ?? new ResourceRecordSection();
        $this->additionalSection = $additionalSection ?? new ResourceRecordSection();
        $this->authoritySection = $authoritySection ?? new ResourceRecordSection();
        $this->header = $header->withMessage($this);
    }

    /**
     * @param bool $isResponse
     * @param int  $rcode
     *
     * @return static
     * @see        \NoGlitchYo\Dealdoh\Factory\MessageFactory::create()
     *
     * @deprecated Use MessageFactory::create() instead.
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
        $new->header = $header->withMessage($new);

        return $new;
    }

    public function withQuestionSection(QuestionSection $questionSection): MessageInterface
    {
        $new = clone $this;
        $new->questionSection = $questionSection;
        $new->header = $this->header->withMessage($new);

        return $new;
    }

    public function withAnswerSection(ResourceRecordSection $answerSection): MessageInterface
    {
        $new = clone $this;
        $new->answerSection = $answerSection;
        $new->header = $this->header->withMessage($new);

        return $new;
    }

    public function withAdditionalSection(ResourceRecordSection $additionalSection): MessageInterface
    {
        $new = clone $this;
        $new->additionalSection = $additionalSection;
        $new->header = $this->header->withMessage($new);

        return $new;
    }

    public function withAuthoritySection(ResourceRecordSection $authoritySection): MessageInterface
    {
        $new = clone $this;
        $new->authoritySection = $authoritySection;
        $new->header = $this->header->withMessage($new);

        return $new;
    }

    public function getHeader(): HeaderInterface
    {
        return $this->header;
    }

    public function getQuestion(): array
    {
        return $this->questionSection ? $this->questionSection->getQueries() : [];
    }

    public function getAnswer(): array
    {
        return $this->answerSection ? $this->answerSection->getRecords() : [];
    }

    public function getAuthority(): array
    {
        return $this->authoritySection ? $this->authoritySection->getRecords() : [];
    }

    public function getAdditional(): array
    {
        return $this->additionalSection ? $this->additionalSection->getRecords() : [];
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

    /**
     * Enable recursion for the current DNS message
     *
     * @return MessageInterface
     */
    public function withRecursionEnabled(): MessageInterface
    {
        return $this->withHeader(
            new Header(
                $this->getHeader()->getId(),
                $this->getHeader()->isQr(),
                $this->getHeader()->getOpcode(),
                $this->getHeader()->isAa(),
                $this->getHeader()->isTc(),
                true, // Enable recursion (RD = 1)
                $this->getHeader()->isRa(),
                $this->getHeader()->getZ(),
                $this->getHeader()->getRcode()
            )
        );
    }
}
