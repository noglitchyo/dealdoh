<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Client\Transport\DnsOverUdpTransport;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Service\DnsCryptServiceInterface;
use Service\DnsCrypt\CertificateFetcher;

class DnsCryptClient implements DnsClientInterface
{
    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var DnsCryptServiceInterface
     */
    private $dnsCryptService;

    /**
     * @var CertificateFetcher
     */
    private $certificateFetcher;

    public function __construct(
        MessageFactoryInterface $messageFactory,
        DnsCryptServiceInterface $dnsCryptService,
        CertificateFetcher $certificateFetcher
    )
    {
        $this->messageFactory     = $messageFactory;
        $this->dnsCryptService    = $dnsCryptService;
        $this->certificateFetcher = $certificateFetcher;
    }

    public function resolve(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        if (!$dnsUpstream instanceof DnsCryptUpstream) {
            throw new InvalidArgumentException('Upstream must be an instance of ' . DnsCryptUpstream::class);
        }

        $certificate    = $this->certificateFetcher->getCertificateForUpstream($dnsUpstream);
        $dnsWireMessage = $this->messageFactory->createDnsWireMessageFromMessage($dnsRequestMessage);

        // Retrieve a handler for the encryption system used by the certificate
        $es = $this->dnsCryptService->getEncryptionSystem($certificate);

        $dnsCryptQuery = $es->encrypt($dnsWireMessage);

        $dnsResponseMessage = $this->send($dnsUpstream, $dnsCryptQuery);
        // TODO: verify response integrity
        // Check if TC flag is true, if yes, fallback on TCP transport
        $dnsMessage = $this->messageFactory->createMessageFromDnsWireMessage($es->decrypt($dnsResponseMessage));
        if ($dnsMessage->getHeader()->isTc()) {
            $dnsResponseMessage = $this->send($dnsUpstream, $dnsCryptQuery, true);
            $dnsMessage         = $this->messageFactory->createMessageFromDnsWireMessage(
                $es->decrypt($dnsResponseMessage)
            );
        }
        die(var_dump(json_encode($dnsMessage)));
    }

    private function send(DnsUpstream $dnsUpstream, DnsCryptQuery $dnsCryptQuery, bool $useTcp = false): string
    {
        if (!$useTcp) {
            $udpTransport = new DnsOverUdpTransport();
            $response     = $udpTransport->send($dnsUpstream->getUri(), (string)$dnsCryptQuery);
        } else {
            $tcpTransport = new DnsOverTcpTransport();
            $response     = $tcpTransport->send($dnsUpstream->getUri(), (string)$dnsCryptQuery);
        }

        return $response;
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'sdns') !== false;
    }
}
