<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\Dns;

use NoGlitchYo\Dealdoh\Entity\Dns\Message\HeaderInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface MessageFactoryInterface
{
    /**
     * Create a new message.
     *
     * @param int  $id
     * @param bool $qr
     * @param int  $opcode
     * @param bool $isAa
     * @param bool $isTc
     * @param bool $isRd
     * @param bool $isRa
     * @param int  $z
     * @param int  $rcode
     *
     * @return MessageInterface
     */
    public function create(
        int $id = null,
        bool $qr = false,
        int $opcode = HeaderInterface::RCODE_OK,
        bool $isAa = false,
        bool $isTc = false,
        bool $isRd = false,
        bool $isRa = false,
        int $z = 0,
        int $rcode = HeaderInterface::RCODE_OK
    ): MessageInterface;

    /**
     * Create an instance of MessageInterface from a DNS wire message.
     * A DNS wire message is a message using DNS wireformat as specified in RFC1035
     *
     * @param string $dnsWireMessage
     *
     * @return MessageInterface
     */
    public function createMessageFromDnsWireMessage(string $dnsWireMessage): MessageInterface;

    /**
     * Create a DNS wire message from an instance of MessageInterface.
     * A DNS wire message is a message using DNS wireformat as specified in RFC1035
     *
     * @param MessageInterface $dnsMessage
     *
     * @return MessageInterface
     */
    public function createDnsWireMessageFromMessage(MessageInterface $dnsMessage): string;
}
