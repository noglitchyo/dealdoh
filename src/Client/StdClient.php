<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use Socket\Raw\Factory;
use const MSG_EOR;
use const MSG_WAITALL;
use const SO_RCVTIMEO;
use const SOL_SOCKET;

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
        $socket->sendTo(
            $this->dnsMessageFactory->createDnsWireMessageFromMessage($dnsRequestMessage),
            MSG_EOR,
            $remote
        );
        $socket->setOption(SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        // TODO: Need to be improved: usage of tcp, handle truncated query, retry, etc...
        $dnsWireResponseMessage = $socket->recvFrom(static::EDNS_SIZE, MSG_WAITALL, $remote);

        return $this->dnsMessageFactory->createMessageFromDnsWireMessage($dnsWireResponseMessage);
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return $dnsUpstream->getScheme() === null || $dnsUpstream->getScheme() === 'udp';
    }

    private function getClientSocket(DnsUpstream $dnsUpstream)
    {
        return $this->factory->createClient('udp://' . $dnsUpstream->getUri());
    }
}
