<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Mapper;

use NoGlitchYo\Dealdoh\Entity\MessageInterface;

interface MessageMapperInterface
{
    /**
     * Create an instance of MessageInterface from a DNS wire message.
     * A DNS wire message is a message using DNS wireformat as specified in RFC1035
     *
     * @param string $dnsWireMessage
     *
     * @return \NoGlitchYo\Dealdoh\Entity\MessageInterface
     */
    public function createMessageFromDnsWireMessage(string $dnsWireMessage): MessageInterface;

    /**
     * Create a DNS wire message from an instance of MessageInterface.
     * A DNS wire message is a message using DNS wireformat as specified in RFC1035
     *
     * @param MessageInterface $dnsMessage
     *
     * @return string
     */
    public function createDnsWireMessageFromMessage(MessageInterface $dnsMessage): string;
}
