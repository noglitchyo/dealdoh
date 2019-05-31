<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Message\Section;

/**
 * @codeCoverageIgnore
 */
interface ResourceRecordInterface
{
    public const TYPE_A = 1;
    public const TYPE_NS = 2;
    public const TYPE_CNAME = 5;
    public const TYPE_SOA = 6;
    public const TYPE_PTR = 12;
    public const TYPE_MX = 15;
    public const TYPE_TXT = 16;
    public const TYPE_AAAA = 28;
    public const TYPE_SRV = 33;
    public const TYPE_ANY = 255;

    /**
     * a domain name to which this resource record pertains.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * two octets containing one of the RR type codes.  This
     * field specifies the meaning of the data in the RDATA
     * field.
     *
     * @return int
     */
    public function getType(): int;

    /**
     *  two octets which specify the class of the data in the
     * RDATA field.
     *
     * @return int
     */
    public function getClass(): int;

    /**
     * a 32 bit unsigned integer that specifies the time
     * interval (in seconds) that the resource record may be
     * cached before it should be discarded.  Zero values are
     * interpreted to mean that the RR can only be used for the
     * transaction in progress, and should not be cached.
     *
     * @return int
     */
    public function getTtl(): int;
}
