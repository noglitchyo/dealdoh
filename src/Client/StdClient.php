<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use Socket\Raw\Factory;
use const MSG_WAITALL;
use const SO_RCVTIMEO;
use const SOL_SOCKET;

/**
 * Standard DNS client making request over UDP (& TCP as a fallback)
 * Use message in DNS wire format as described in RFC-1035
 */
class StdClient implements DnsClientInterface
{
    public const EDNS_SIZE = 4096;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var MessageFactoryInterface
     */
    private $dnsMessageFactory;

    public function __construct(Factory $factory, MessageFactoryInterface $dnsMessageFactory)
    {
        $this->factory = $factory;
        $this->dnsMessageFactory = $dnsMessageFactory;
    }

    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        $socket = $this->getClientSocket($dnsUpstream);
        $remote = $dnsUpstream->getUri();
        $socket->sendTo($this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsRequestMessage), 0, $remote);
        $socket->setOption(SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        // TODO: Need to be improved: usage of tcp, handle truncated query, retry, etc...
        $dnsWireResponseMessage = $socket->recvFrom(static::EDNS_SIZE, MSG_WAITALL, $remote);

        return $this->dnsMessageFactory->createMessageFromDnsWireMessage($dnsWireResponseMessage);
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return in_array($dnsUpstream->getScheme(), ['udp', 'tcp', 'dns']) || $dnsUpstream->getScheme() === null;
    }

    private function getClientSocket(DnsUpstream $dnsUpstream)
    {
        return $this->factory->createClient('udp://' . $dnsUpstream->getUri());
    }
}
