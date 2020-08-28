<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Repository\DnsCrypt;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;

interface CertificateRepositoryInterface
{
    /**
     * Retrieve a certificate from the upstream which will be used to send queries to this resolver.
     * Certificates can be filtered by providing a list of supported encryptions.
     *
     * @param DnsCryptUpstream $dnsUpstream
     * @param array            $supportedEsVersions
     *
     * @return CertificateInterface
     */
    public function getCertificate(
        DnsCryptUpstream $dnsUpstream,
        array $supportedEsVersions = []
    ): CertificateInterface;
}
