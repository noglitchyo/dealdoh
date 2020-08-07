<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Dns\Client;

use Exception;
use InvalidArgumentException;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamInterface;
use NoGlitchYo\Dealdoh\Entity\MessageInterface;
use NoGlitchYo\Dealdoh\Mapper\DnsCrypt\AuthenticatedEncryptionMapperInterface;
use NoGlitchYo\Dealdoh\Mapper\MessageMapperInterface;
use NoGlitchYo\Dealdoh\Repository\DnsCrypt\CertificateRepository;
use NoGlitchYo\Dealdoh\Repository\DnsCrypt\CertificateRepositoryInterface;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverTcpTransport;
use NoGlitchYo\Dealdoh\Service\Transport\DnsOverUdpTransport;
use Throwable;

class DnsCryptClient implements DnsClientInterface
{
    public const SUPPORTED_ES_VERSION = [
        CertificateInterface::ES_VERSION_1,
        CertificateInterface::ES_VERSION_2,
    ];

    /**
     * @var AuthenticatedEncryptionMapperInterface
     */
    private $authenticatedEncryptionMapper;

    /**
     * @var CertificateRepository
     */
    private $certificateRepository;

    /**
     * @var MessageMapperInterface
     */
    private $messageMapper;

    public function __construct(
        AuthenticatedEncryptionMapperInterface $authenticatedEncryptionMapper,
        CertificateRepositoryInterface $certificateRepository,
        MessageMapperInterface $messageMapper
    ) {
        $this->authenticatedEncryptionMapper = $authenticatedEncryptionMapper;
        $this->certificateRepository = $certificateRepository;
        $this->messageMapper = $messageMapper;
    }

    public function query(DnsUpstreamInterface $dnsUpstream, MessageInterface $dnsRequestMessage): MessageInterface
    {
        if (!$dnsUpstream instanceof DnsCryptUpstream) {
            throw new InvalidArgumentException('Upstream must be an instance of ' . DnsCryptUpstream::class);
        }

        try {
            $certificate = $this->certificateRepository->getCertificate(
                $dnsUpstream,
                static::SUPPORTED_ES_VERSION
            );
        } catch (Throwable $t) {
            throw new Exception('Not able to query DNScrypt upstream: ' . $t->getMessage());
        }

        // Retrieve an authenticated encryption system to be used with the given certificate
        $ae = $this->authenticatedEncryptionMapper->createFromCertificate($certificate);

        $dnsCryptQuery = (string)$ae->encrypt(
            $this->messageMapper->createDnsWireMessageFromMessage($dnsRequestMessage)
        );

        $response = $this->send($dnsUpstream, $dnsCryptQuery);

        $dnsMessage = $this->messageMapper->createMessageFromDnsWireMessage($ae->decrypt($response));

        // Check if response is truncated, if yes, fallback on TCP transport and retry
        if ($dnsMessage->getHeader()->isTc()) {
            $dnsMessage = $this->messageMapper->createMessageFromDnsWireMessage(
                $ae->decrypt($this->send($dnsUpstream, $dnsCryptQuery, true))
            );
        }

        return $dnsMessage;
    }

    private function send(DnsUpstreamInterface $dnsUpstream, string $dnsQuery, bool $useTcp = false): string
    {
        $transport = !$useTcp ? new DnsOverUdpTransport() : new DnsOverTcpTransport();

        return $transport->send($dnsUpstream->getHost(), $dnsUpstream->getPort(), $dnsQuery);
    }

    /**
     * Supports only DNSCrypt upstream
     *
     * @param DnsUpstreamInterface $dnsUpstream
     *
     * @return bool
     */
    public function supports(DnsUpstreamInterface $dnsUpstream): bool
    {
        return $dnsUpstream::getType() === DnsCryptUpstream::TYPE;
    }
}
