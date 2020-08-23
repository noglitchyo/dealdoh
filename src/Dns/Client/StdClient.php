<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\MessageMapperInterface;
use NoGlitchYo\Dealdoh\Service\Transport\DnsTransportInterface;

/**
 * Standard DNS client making request over UDP (& TCP as a fallback)
 * Use message in DNS wire format as described in RFC-1035
 */
class StdClient implements DnsClientInterface
{
    public const EDNS_SIZE = 4096;

    /**
     * @var MessageMapperInterface
     */
    private $messageMapper;

    /**
     * @var DnsTransportInterface
     */
    private $tcpTransport;

    /**
     * @var DnsTransportInterface
     */
    private $udpTransport;

    public function __construct(
        MessageMapperInterface $messageMapper,
        DnsTransportInterface $tcpTransport,
        DnsTransportInterface $udpTransport
    )
    {
        $this->messageMapper = $messageMapper;
        $this->tcpTransport  = $tcpTransport;
        $this->udpTransport  = $udpTransport;
    }

    /**
     * Resolve message using regular UDP/TCP queries towards DNS upstream
     *
     * @param DnsUpstream $dnsUpstream
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     * @throws Exception
     */
    public function query(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        return $this->send(
            $dnsUpstream->getAddr(),
            $dnsRequestMessage->withRecursionEnabled(),
            !$this->isUdp($dnsUpstream)
        );
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return $this->isUdp($dnsUpstream) || $this->isTcp($dnsUpstream);
    }

    private function isUdp(DnsUpstream $dnsUpstream): bool
    {
        return in_array($dnsUpstream->getScheme(), ['udp', 'dns']) || $dnsUpstream->getScheme() === null;
    }

    private function isTcp(DnsUpstream $dnsUpstream): bool
    {
        return $dnsUpstream->getScheme() === 'tcp';
    }

    /**
     * Send DNS message using socket with the chosen protocol: `udp` or `tcp`
     * Allow a sender to force usage of a specific protocol (e.g. protocol blocked by network/firewall)
     *
     * @param string $address
     * @param MessageInterface $dnsRequestMessage
     * @param bool $isTcp Should use TCP to send message instead of UDP
     *
     * @return MessageInterface
     * @throws Exception
     */
    private function send(
        string $address,
        MessageInterface $dnsRequestMessage,
        bool $isTcp = false
    ): MessageInterface
    {
        $dnsWireMessage = $this->messageMapper->createDnsWireMessageFromMessage($dnsRequestMessage);

        if (!$isTcp) {
            if (strlen($dnsWireMessage) <= static::EDNS_SIZE) { // Must use TCP if message is bigger
                $dnsWireResponseMessage = $this->udpTransport->send($address, $dnsWireMessage);

                $message = $this->messageMapper->createMessageFromDnsWireMessage($dnsWireResponseMessage);
                // Only if message is not truncated response is returned, otherwise retry with TCP
                if (!$message->getHeader()->isTc()) {
                    return $message;
                }
            }
        }

        $dnsWireResponseMessage = $this->tcpTransport->send($address, $dnsWireMessage);

        return $this->messageMapper->createMessageFromDnsWireMessage($dnsWireResponseMessage);
    }
}
