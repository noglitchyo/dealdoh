<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstream;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\DnsCrypt\EncryptionSystemMapperInterface;
use NoGlitchYo\Dealdoh\Mapper\MessageMapperInterface;
use NoGlitchYo\Dealdoh\Repository\DnsCrypt\CertificateRepository;
use NoGlitchYo\Dealdoh\Repository\DnsCrypt\CertificateRepositoryInterface;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;

class DnsCryptClient implements DnsClientInterface
{
    public const SUPPORTED_AUTHENTICATED_ENCRYPTION = [
        CertificateInterface::ES_VERSION_XSALSA20POLY1305,
    ];

    /**
     * @var EncryptionSystemMapperInterface
     */
    private $dnsCryptService;

    /**
     * @var CertificateRepository
     */
    private $certificateRepository;

    /**
     * @var MessageMapperInterface
     */
    private $messageMapper;

    public function __construct(
        EncryptionSystemMapperInterface $dnsCryptService,
        CertificateRepositoryInterface $certificateFetcher,
        MessageMapperInterface $messageMapper
    )
    {
        $this->dnsCryptService = $dnsCryptService;
        $this->certificateRepository = $certificateFetcher;
        $this->messageMapper = $messageMapper;
    }

    public function query(DnsUpstream $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        if (!$dnsUpstream instanceof DnsCryptUpstream) {
            throw new InvalidArgumentException('Upstream must be an instance of ' . DnsCryptUpstream::class);
        }

        $certificate = $this->certificateRepository->getCertificate(
            $dnsUpstream,
            static::SUPPORTED_AUTHENTICATED_ENCRYPTION
        );

        $dnsWireMessage = $this->messageMapper->createDnsWireMessageFromMessage($dnsRequestMessage);

        // Retrieve a handler for the encryption system used by the certificate
        $es = $this->dnsCryptService->createAuthenticatedEncryptionFromCertificate($certificate);

        $dnsCryptQuery = $es->encrypt($dnsWireMessage);

        $dnsResponseMessage = $this->send($dnsUpstream, $dnsCryptQuery);
        // TODO: verify response integrity
        // Check if TC flag is true, if yes, fallback on TCP transport
        $dnsMessage = $this->messageMapper->createMessageFromDnsWireMessage($es->decrypt($dnsResponseMessage));
        if ($dnsMessage->getHeader()->isTc()) {
            $dnsResponseMessage = $this->send($dnsUpstream, $dnsCryptQuery, true);
            $dnsMessage         = $this->messageMapper->createMessageFromDnsWireMessage(
                $es->decrypt($dnsResponseMessage)
            );
        }
        die(var_dump(json_encode($dnsMessage)));
    }

    private function send(DnsUpstream $dnsUpstream, DnsCryptQuery $dnsCryptQuery, bool $useTcp = false): string
    {
        $transport = (!$useTcp) ? new DnsOverUdpTransport() : new DnsOverTcpTransport();

        return $transport->send($dnsUpstream->getUri(), (string)$dnsCryptQuery);
    }

    public function supports(DnsUpstream $dnsUpstream): bool
    {
        return strpos(strtolower($dnsUpstream->getScheme() ?? ''), 'sdns') !== false;
    }
}
