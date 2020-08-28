<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\MessageMapperInterface;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsTransportInterface;
use Throwable;

/**
 * Standard DNS client making request over UDP (& TCP as a fallback)
 * Use message in DNS wire format as described in RFC-1035
 */
class PlainDnsClient implements DnsClientInterface
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
        DnsTransportInterface $tcpTransport = null,
        DnsTransportInterface $udpTransport = null
    ) {
        $this->messageMapper = $messageMapper;
        $this->tcpTransport = new DnsOverTcpTransport() ?? $tcpTransport;
        $this->udpTransport = new DnsOverUdpTransport() ?? $udpTransport;
    }

    /**
     * Resolve message using regular UDP/TCP queries towards DNS upstream
     *
     * @param DnsUpstreamInterface $dnsUpstream
     * @param MessageInterface     $dnsRequestMessage
     *
     * @return MessageInterface
     * @throws Exception
     */
    public function query(DnsUpstreamInterface $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        return $this->send(
            $dnsUpstream,
            $dnsRequestMessage->withRecursionEnabled(),
            !$this->isUdp($dnsUpstream)
        );
    }

    public function supports(DnsUpstreamInterface $dnsUpstream): bool
    {
        return $dnsUpstream::getType() === DnsUpstream::TYPE &&
            ($this->isUdp($dnsUpstream) || $this->isTcp($dnsUpstream));
    }

    private function isUdp(DnsUpstreamInterface $dnsUpstream): bool
    {
        return in_array($dnsUpstream->getScheme(), ['udp', 'dns']) || $dnsUpstream->getScheme() === null;
    }

    private function isTcp(DnsUpstreamInterface $dnsUpstream): bool
    {
        return $dnsUpstream->getScheme() === 'tcp';
    }

    /**
     * Send DNS message using socket with the chosen protocol: `udp` or `tcp`
     * Allow a sender to force usage of a specific protocol (e.g. protocol blocked by network/firewall)
     *
     * @param DnsUpstreamInterface $dnsUpstream
     * @param MessageInterface     $dnsRequestMessage
     * @param bool                 $isTcp Should use TCP to send message instead of UDP
     *
     * @return MessageInterface
     * @throws Exception
     */
    private function send(
        DnsUpstreamInterface $dnsUpstream,
        MessageInterface $dnsRequestMessage,
        bool $isTcp = false
    ): MessageInterface {
        $dnsWireMessage = $this->messageMapper->createDnsWireMessageFromMessage($dnsRequestMessage);

        if (!$isTcp) {
            if (strlen($dnsWireMessage) <= static::EDNS_SIZE) { // Must use TCP if message is bigger
                try {
                    $dnsWireResponseMessage = $this->udpTransport->send(
                        $dnsUpstream->getHost(),
                        $dnsUpstream->getPort(),
                        $dnsWireMessage
                    );

                    $message = $this->messageMapper->createMessageFromDnsWireMessage($dnsWireResponseMessage);

                    // Only if message is not truncated response is returned, otherwise retry with TCP
                    if (!$message->getHeader()->isTc()) {
                        return $message;
                    }
                } catch (Throwable $t) {
                    // Eat the exception, and retry with TCP
                    // TODO: this behavior might not always be desired
                    // TODO: Should filter this exception with a specific exception like UdpConnectionFailed
                }
            }
        }

        $dnsWireResponseMessage = $this->tcpTransport->send(
            $dnsUpstream->getHost(),
            $dnsUpstream->getPort(),
            $dnsWireMessage
        );

        return $this->messageMapper->createMessageFromDnsWireMessage($dnsWireResponseMessage);
    }
}
