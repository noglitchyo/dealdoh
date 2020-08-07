<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface ClientNonceRepositoryInterface
{
    public function getClientNonce(string $clientSecretKey, string $resolverPublicKey): string;
}
