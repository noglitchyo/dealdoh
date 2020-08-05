<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use Exception;
use LogicException;
use NoGlitchYo\Dealdoh\Client\Transport\DnsTransportInterface;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Header;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;

/**
 * Standard DNS client making request over UDP (& TCP as a fallback)
 * Use message in DNS wire format as described in RFC-1035
 */
class StdClient implements DnsClientInterface
{
    public const EDNS_SIZE = 4096;

    /**
     * @var MessageFactoryInterface
     */
    private $dnsMessageFactory;
    /**
     * @var DnsTransportInterface
     */
    private $tcpTransport;
    /**
     * @var DnsTransportInterface
     */
    private $udpTransport;

    public function __construct(
        MessageFactoryInterface $dnsMessageFactory,
        DnsTransportInterface $tcpTransport,
        DnsTransportInterface $udpTransport
    ) {
        $this->dnsMessageFactory = $dnsMessageFactory;
        $this->tcpTransport = $tcpTransport;
        $this->udpTransport = $udpTransport;
    }

    /**
     * Resolve message using regular UDP/TCP queries towards DNS upstream
     *
     * @param DnsUpstream      $dnsUpstream
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     * @throws Exception
     */
    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        $dnsRequestMessage = $this->enableRecursionForDnsMessage($dnsRequestMessage);
        $address = $this->getSanitizedUpstreamAddress($dnsUpstream);

        if ($this->isUdp($dnsUpstream)) {
            $dnsWireResponseMessage = $this->sendWith('udp', $address, $dnsRequestMessage);
        } elseif ($this->isTcp($dnsUpstream)) {
            $dnsWireResponseMessage = $this->sendWith('tcp', $address, $dnsRequestMessage);
        } else {
            throw new LogicException(sprintf('Scheme `%s` is not supported', $dnsUpstream->getScheme()));
        }

        return $dnsWireResponseMessage;
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return $this->isUdp($dnsUpstream) || $this->isTcp($dnsUpstream);
    }

    private function isUdp($dnsUpstream): bool
    {
        return in_array($dnsUpstream->getScheme(), ['udp', 'dns']) || $dnsUpstream->getScheme() === null;
    }

    private function isTcp($dnsUpstream): bool
    {
        return $dnsUpstream->getScheme() === 'tcp';
    }

    /**
     * Send DNS message using socket with the chosen protocol: `udp` or `tcp`
     * Allow a sender to force usage of a specific protocol (e.g. protocol blocked by network/firewall)
     *
     * @param string           $protocol Protocol to use to send the message
     * @param string           $address
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     * @throws Exception
     */
    private function sendWith(
        string $protocol,
        string $address,
        MessageInterface $dnsRequestMessage
    ): MessageInterface {
        $dnsWireMessage = $this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);

        if ($protocol === 'udp') {
            if (strlen($dnsWireMessage) <= static::EDNS_SIZE) { // Must use TCP if message is bigger
                $dnsWireResponseMessage = $this->udpTransport->send($address, $dnsWireMessage);

                $message = $this->dnsMessageFactory->createMessageFromDnsWireMessage($dnsWireResponseMessage);
                // Only if message is not truncated response is returned, otherwise retry with TCP
                if (!$message->getHeader()->isTc()) {
                    return $message;
                }
            }
        }

        $dnsWireResponseMessage = $this->tcpTransport->send($address, $dnsWireMessage);

        $message = $this->dnsMessageFactory->createMessageFromDnsWireMessage($dnsWireResponseMessage);

        return $message;
    }

    /**
     * Clean up the protocol from URI supported by the client but which can not be used with transport (e.g. dns://).
     *
     * @param DnsUpstream $dnsUpstream
     *
     * @return string
     */
    private function getSanitizedUpstreamAddress(DnsUpstream $dnsUpstream): string
    {
        return str_replace($dnsUpstream->getScheme() . '://', '', $dnsUpstream->getUri());
    }

    /**
     * Enable recursion for the given DNS message
     *
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     */
    private function enableRecursionForDnsMessage(MessageInterface $dnsRequestMessage): MessageInterface
    {
        return $dnsRequestMessage->withHeader(
            new Header(
                $dnsRequestMessage->getHeader()->getId(),
                $dnsRequestMessage->getHeader()->isQr(),
                $dnsRequestMessage->getHeader()->getOpcode(),
                $dnsRequestMessage->getHeader()->isAa(),
                $dnsRequestMessage->getHeader()->isTc(),
                true, // Enable recursion (RD = 1)
                $dnsRequestMessage->getHeader()->isRa(),
                $dnsRequestMessage->getHeader()->getZ(),
                $dnsRequestMessage->getHeader()->getRcode()
            )
        );
    }
}
