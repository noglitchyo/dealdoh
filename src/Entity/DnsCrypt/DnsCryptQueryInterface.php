<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface DnsCryptQueryInterface
{
    /**
     * a 8 byte identifier for the resolver certificate
     * chosen by the client.
     * @return string
     */
    public function getClientMagic(): string;

    /**
     * the client's public key, whose length depends on the
     * encryption algorithm defined in the chosen certificate.
     * @return string
     */
    public function getClientPublicKey(): string;

    /**
     * a unique query identifier for a given
     * (<client-sk>, <resolver-pk>) tuple. The same query sent twice for the same
     * (<client-sk>, <resolver-pk>) tuple must use two distinct <client-nonce>
     * values. The length of <client-nonce> depends on the chosen encryption
     * algorithm.
     * @return string
     */
    public function getClientNonce(): string;

    /**
     * @return string
     */
    public function getEncryptedQuery(): string;

    /**
     * @return string
     */
    public function __toString();
}
