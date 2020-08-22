<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Repository\DnsCrypt;

use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCryptUpstream;

interface CertificateRepositoryInterface
{
    public function getCertificateForUpstream(DnsCryptUpstream $dnsUpstream): CertificateInterface;
}
