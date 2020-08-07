<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsStamp;

use JsonSerializable;
use NoGlitchYo\Dealdoh\Entity\DnsUpstreamAddress;
use NoGlitchYo\Dealdoh\Entity\ServerStampProps;

/**
 * @codeCoverageIgnore
 */
interface ServerStampInterface
{
    public const DNSCRYPT_STAMP = 0x01;
    public const DOH_STAMP = 0x02;
    public const DOT_STAMP = 0x03;
    public const PLAINDNS_STAMP = 0x00;
    public const ANONYMOUS_STAMP = 0x81;

    public function getServerStampProps(): ServerStampProps;

    public function getProtocolIdentifier(): int;

    public function getAddress(): DnsUpstreamAddress;
}
