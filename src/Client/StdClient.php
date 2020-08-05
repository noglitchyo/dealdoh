<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use Exception;
use InvalidArgumentException;
use LogicException;
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

    public function __construct(MessageFactoryInterface $dnsMessageFactory)
    {
        $this->dnsMessageFactory = $dnsMessageFactory;
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
    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        $scheme = $dnsUpstream->getScheme();

        $dnsRequestMessage = $this->enableRecursionForDnsMessage($dnsRequestMessage);

        // Clean up the protocol from URI supported by the client but which can not be used with sockets (e.g. dns://).
        $address = str_replace($scheme . '://', '', $dnsUpstream->getUri());

        if (in_array($scheme, ['udp', 'dns']) || $dnsUpstream->getScheme() === null) {
            $dnsWireResponseMessage = $this->sendWithSocket('udp', $address, $dnsRequestMessage);
        } elseif ($dnsUpstream->getScheme() === 'tcp') {
            $dnsWireResponseMessage = $this->sendWithSocket('tcp', $address, $dnsRequestMessage);
        } else {
            throw new LogicException(sprintf('Scheme `%s` is not supported', $scheme));
        }

        return $dnsWireResponseMessage;
    }


    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return in_array($dnsUpstream->getScheme(), ['udp', 'tcp', 'dns']) || $dnsUpstream->getScheme() === null;
    }

    /**
     * Send DNS message using socket with the given protocol: UDP or TCP
     * @param string           $protocol
     * @param string           $address
     * @param MessageInterface $dnsRequestMessage
     *
     * @return MessageInterface
     * @throws Exception
     */
    private function sendWithSocket(
        string $protocol,
        string $address,
        MessageInterface $dnsRequestMessage
    ): MessageInterface {
        $url = parse_url($address);

        $socket = stream_socket_client($protocol . '://' . $url['host'] . ':' . $url['port'], $errno, $errstr, 4);

        if ($socket === false) {
            throw new Exception('Unable to connect:' . $errno . ' - ' . $errstr);
        } else {
            $dnsMessage = $this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);

            switch ($protocol) {
                case 'udp':
                    if (isset($dnsMessage[static::EDNS_SIZE])) { // Must use TCP if message is bigger
                        return $this->sendWithSocket('tcp', $address, $dnsRequestMessage);
                    }

                    \fputs($socket, $dnsMessage);

                    $dnsWireResponseMessage = \fread($socket, static::EDNS_SIZE);
                    if ($dnsWireResponseMessage === false) {
                        throw new Exception('something happened');
                    }

                    break;
                case 'tcp':
                    \fputs($socket, $dnsMessage);
                    $dnsWireResponseMessage = '';
                    while (!feof($socket)) {
                        $dnsWireResponseMessage .= fgets($socket, 512);
                    }
                    break;
                default:
                    throw new InvalidArgumentException(
                        "Only `tcp`, `udp` are supported protocol to be used with socket."
                    );
            }
        }

        \fclose($socket);

        $message = $this->dnsMessageFactory->createMessageFromDnsWireMessage($dnsWireResponseMessage);

        // Message was truncated, retry with TCP
        if ($message->getHeader()->isTc()) {
            return $this->sendWithSocket('tcp', $address, $dnsRequestMessage);
        }

        return $message;
    }

    /**
     * Enable recursion for the given DNS message
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
