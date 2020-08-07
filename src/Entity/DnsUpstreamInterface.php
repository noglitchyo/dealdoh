<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity;

use JsonSerializable;

/**
 * @codeCoverageIgnore
 */
interface DnsUpstreamInterface extends JsonSerializable
{
    public function getPort(): ?int;

    public function getScheme(): ?string;

    public function getUri(): string;

    public function getCode(): string;

    /**
     * Return the URI cleaned up from its protocol, often supported by the client but which
     * can not be used directly with transport (e.g. dns://)
     *
     * @return string
     */
    public function getHost(): string;

    public static function getType();
}
