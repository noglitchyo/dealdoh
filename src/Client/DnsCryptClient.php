<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Client;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\Dns\MessageInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Factory\Dns\MessageFactoryInterface;
use NoGlitchYo\Dealdoh\Factory\DnsCrypt\EncryptionSystemFactoryInterface;
use NoGlitchYo\Dealdoh\Service\DnsCrypt\CertificateFetcher;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;

class DnsCryptClient implements DnsClientInterface
{
    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var EncryptionSystemFactoryInterface
     */
    private $dnsCryptService;

    /**
     * @var CertificateFetcher
     */
    private $certificateFetcher;

    public function __construct(
        MessageFactoryInterface $messageFactory,
        EncryptionSystemFactoryInterface $dnsCryptService,
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
        $es = $this->dnsCryptService->createEncryptionSystem($certificate);

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
        $transport = (!$useTcp) ? new DnsOverUdpTransport() : new DnsOverTcpTransport();

        return  $transport->send($dnsUpstream->getUri(), (string)$dnsCryptQuery);
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'sdns') !== false;
    }
}
