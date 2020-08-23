<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Tests\Unit\Dns\Client;

use PHPUnit\Framework\TestCase;

class DnsCryptClientTest extends TestCase
{
    public function testThatQueryIsPaddedWhenSentOverUdp()
    {
        $this->markTestIncomplete();
        /**
         * Prior to encryption, queries are padded using the ISO/IEC 7816-4
         * format. The padding starts with a byte valued 0x80 followed by a
         * variable number of NUL bytes.
         *
         * <client-query> <client-query-pad> must be at least <min-query-len>
         * bytes. If the length of the client query is less than <min-query-len>,
         * the padding length must be adjusted in order to satisfy this
         * requirement.
         *
         * <min-query-len> is a variable length, initially set to 256 bytes, and
         * must be a multiple of 64 bytes.
         */
    }

    public function testThatDnsResponseIsDecryptedWithResolverPublicKey()
    {
        $this->markTestIncomplete();
        /**
         * The client must verify and decrypt the response using the resolver's
         * public key, the shared secret and the received nonce. If the response
         * cannot be verified, the response must be discarded.
         */
    }

    public function testThatDnsResponseIsDiscardedWhenItCannotBeVerified()
    {
        $this->markTestIncomplete();
        /**
         *
         * The client must verify and decrypt the response using the resolver's
         * public key, the shared secret and the received nonce. If the response
         * cannot be verified, the response must be discarded.
         */
    }

    public function testThatQuerySendOverUdpIsSendAgainOverTcpWhenTcFlagIsProvided()
    {
        $this->markTestIncomplete();
        /**
         *
         * If the response has the TC flag set, the client must:
         * 1) send the query again using TCP
         * 2) set the new minimum query length as:
         *
         * <min-query-len> ::= min(<min-query-len> + 64, <max-query-len>)
         *
         * <min-query-len> must be capped so that the full length of a DNSCrypt
         * packet doesn't exceed the maximum size required by the transport layer.
         *
         * The client may decrease <min-query-len>, but the length must remain a multiple
         * of 64 bytes.
         */
    }

    public function testThatQueryIsPaddedWhenSentOverTcp()
    {
        $this->markTestIncomplete();
        /**
         * Prior to encryption, queries are padded using the ISO/IEC 7816-4
         * format. The padding starts with a byte valued 0x80 followed by a
         * variable number of NUL bytes.
         *
         * The length of <client-query-pad> is randomly chosen between 1 and 256
         * bytes (including the leading 0x80), but the total length of <client-query>
         * <client-query-pad> must be a multiple of 64 bytes.
         *
         * For example, an originally unpadded 56-bytes DNS query can be padded as:
         *
         * <56-bytes-query> 0x80 0x00 0x00 0x00 0x00 0x00 0x00 0x00
         * or
         * <56-bytes-query> 0x80 (0x00 * 71)
         * or
         * <56-bytes-query> 0x80 (0x00 * 135)
         * or
         * <56-bytes-query> 0x80 (0x00 * 199)
         */
    }

    public function testThatTcpConnectionIsClosedWhenResponseIsReceivedFromResolver()
    {
        $this->markTestIncomplete();
        /**
         *
         * After having received a response from the resolver, the client and the
         * resolver must close the TCP connection. Multiple transactions over the
         * same TCP connections are not allowed by this revision of the protocol.
         */
    }
}