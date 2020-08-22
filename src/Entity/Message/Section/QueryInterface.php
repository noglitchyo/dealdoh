<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\Message\Section;

use JsonSerializable;

/**
 * @see                https://tools.ietf.org/html/rfc1035#section-4.1.2
 * @codeCoverageIgnore
 */
interface QueryInterface extends JsonSerializable
{
    /**
     * a domain name represented as a sequence of labels, where
     * each label consists of a length octet followed by that
     * number of octets.  The domain name terminates with the
     * zero length octet for the null label of the root.  Note
     * that this field may be an odd number of octets; no
     * padding is used.
     *
     * @return string
     */
    public function getQname(): string;

    /**
     * a two octet code which specifies the type of the query.
     * The values for this field include all codes valid for a
     * TYPE field, together with some more general codes which
     * can match more than one type of RR.
     *
     * @return int
     */
    public function getQtype(): int;

    /**
     * a two octet code that specifies the class of the query.
     * For example, the QCLASS field is IN for the Internet.
     *
     * @return int
     */
    public function getQclass(): int;
}
