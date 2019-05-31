<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh\Message;

/**
 * @codeCoverageIgnore
 */
interface HeaderInterface extends MessageSectionAwareInterface
{
    /**
     * No error condition
     */
    const RCODE_OK = 0;

    /**
     * Format error - The name server was unable to interpret the query.
     */
    const RCODE_FORMAT_ERROR = 1;

    /**
     * Server failure - The name server was unable to process this query due to a problem with the name server.
     */
    const RCODE_SERVER_FAILURE = 2;

    /**
     * Name Error - Meaningful only for responses from an authoritative name server, this code signifies
     * that the domain name referenced in the query does not exist.
     */
    const RCODE_NAME_ERROR = 3;

    /**
     *  Not Implemented - The name server does not support the requested kind of query.
     */
    const RCODE_NOT_IMPLEMENTED = 4;

    /**
     * Refused - The name server refuses to perform the specified operation for policy reasons.
     * For example, a name server may not wish to provide the information to the particular requester,
     * or a name server may not wish to perform a particular operation (e.g., zone transfer) for particular data.
     */
    const RCODE_REFUSED = 5;

    /**
     * A 16 bit identifier assigned by the program that
     * generates any kind of query.  This identifier is copied
     * the corresponding reply and can be used by the requester
     * to match up replies to outstanding queries.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * an unsigned 16 bit integer specifying the number of
     * entries in the question section.
     *
     * @return int
     */
    public function getQdCount(): int;

    /**
     * an unsigned 16 bit integer specifying the number of
     * resource records in the answer section.
     *
     * @return int
     */
    public function getAnCount(): int;

    /**
     * an unsigned 16 bit integer specifying the number of name
     * server resource records in the authority records section.
     *
     * @return int
     */
    public function getNsCount(): int;

    /**
     * an unsigned 16 bit integer specifying the number of
     * resource records in the additional records section.
     *
     * @return int
     */
    public function getArCount(): int;

    /**
     * A one bit field that specifies whether this message is a
     * query (0), or a response (1).
     *
     * @return bool
     */
    public function isQr(): bool;

    /**
     * A four bit field that specifies kind of query in this
     * message.  This value is set by the originator of a query
     * and copied into the response.  The values are:
     *
     * 0               a standard query (QUERY)
     *
     * 1               an inverse query (IQUERY)
     *
     * 2               a server status request (STATUS)
     *
     * 3-15            reserved for future use
     *
     * @return  int
     */
    public function getOpcode(): int;

    /**
     * Authoritative Answer - this bit is valid in responses,
     * and specifies that the responding name server is an
     * authority for the domain name in question section.
     *
     * Note that the contents of the answer section may have
     * multiple owner names because of aliases. The AA bit
     * corresponds to the name which matches the query name, or
     * the first owner name in the answer section.
     *
     * @return bool
     */
    public function isAa(): bool;

    /**
     * TrunCation - specifies that this message was truncated
     * due to length greater than that permitted on the
     * transmission channel.
     *
     * @return bool
     */
    public function isTc(): bool;

    /**
     * Recursion Desired - this bit may be set in a query and
     * is copied into the response.  If RD is set, it directs
     * the name server to pursue the query recursively.
     * Recursive query support is optional.
     *
     * @return bool
     */
    public function isRd(): bool;

    /**
     * Recursion Available - this be is set or cleared in a
     * response, and denotes whether recursive query support is
     * available in the name server.
     *
     * @return bool
     */
    public function isRa(): bool;

    /**
     * Reserved for future use.  Must be zero in all queries
     * and responses.
     *
     * @return int
     */
    public function getZ(): int;


    /**
     * Response code - this 4 bit field is set as part of responses.
     * 0               No error condition
     *
     * 1               Format error - The name server was unable to interpret the query.
     *
     * 2               Server failure - The name server was unable to process this query due to a problem with the name server.
     *
     * 3               Name Error - Meaningful only for responses from an authoritative name server, this code signifies
     * that the domain name referenced in the query does not exist.
     *
     * 4               Not Implemented - The name server does not support the requested kind of query.
     *
     * 5               Refused - The name server refuses to perform the specified operation for policy reasons.
     * For example, a name server may not wish to provide the information to the particular requester,
     * or a name server may not wish to perform a particular operation (e.g., zone transfer) for particular data.
     *
     * 6-15            Reserved for future use.
     *
     * @return int
     */
    public function getRcode(): int;
}
