<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Dns;

use JsonSerializable;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\SectionAwareInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QueryInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\QuestionSection;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordSection;

/**
 * Immutable DNS message.
 *
 * TODO: The way Header, Section and Message are attached together need some improvements
 *
 * @see                https://tools.ietf.org/html/rfc1035#section-4.1
 * @codeCoverageIgnore
 */
interface MessageInterface extends JsonSerializable
{
    /**
     * @return HeaderInterface
     */
    public function getHeader(): HeaderInterface;

    /**
     * @return QueryInterface[]
     */
    public function getQuestion(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAnswer(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAuthority(): array;

    /**
     * @return ResourceRecordInterface[]
     */
    public function getAdditional(): array;

    /**
     * Return a new instance of message with the given Header
     *
     * @param HeaderInterface $header
     *
     * @return MessageInterface
     */
    public function withHeader(HeaderInterface $header): MessageInterface;

    /**
     * @param QuestionSection $questionSection
     *
     * @return MessageInterface
     */
    public function withQuestionSection(QuestionSection $questionSection): MessageInterface;

    /**
     * @param ResourceRecordSection $answerSectionSection
     *
     * @return MessageInterface
     */
    public function withAnswerSection(ResourceRecordSection $answerSectionSection): MessageInterface;

    /**
     * @param ResourceRecordSection $authoritySectionSection
     *
     * @return MessageInterface
     */
    public function withAuthoritySection(ResourceRecordSection $authoritySectionSection): MessageInterface;

    /**
     * @param ResourceRecordSection $additionalSectionSection
     *
     * @return MessageInterface
     */
    public function withAdditionalSection(ResourceRecordSection $additionalSectionSection): MessageInterface;
}
