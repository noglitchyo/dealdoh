<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Helper\DnsCryptHelper;
use ParagonIE_Sodium_Compat;

class XSalsa20AuthenticatedEncryption implements AuthenticatedEncryptionInterface
{
    /**
     * @var string
     */
    private $clientPublicKey;

    /**
     * @var string
     */
    private $clientSecretKey;

    /**
     * @var string
     */
    private $keypair;
    /**
     * @var string
     */
    private $clientMagic;
    /**
     * @var string
     */
    private $resolverPublicKey;

    public function __construct(string $clientMagic, string $resolverPublicKey)
    {
        $this->clientMagic = $clientMagic;
        $this->resolverPublicKey = $resolverPublicKey;
        $this->initKeys();
    }

    public function encrypt(string $clientDnsWireQuery): DnsCryptQuery
    {
        /**
         * With a 24 bytes nonce, a question sent by a DNSCrypt client must be
         * encrypted using the shared secret, and a nonce constructed as follows:
         * 12 bytes chosen by the client followed by 12 NUL (0) bytes.
         */
        [$clientNonce, $paddedClientNonce] = DnsCryptHelper::createClientNonce(SODIUM_CRYPTO_BOX_NONCEBYTES);

        $encryptedQuery = ParagonIE_Sodium_Compat::crypto_box(
            DnsCryptHelper::addPadding($clientDnsWireQuery),
            $paddedClientNonce,
            $this->keypair
        );

        return new DnsCryptQuery(
            $this->clientMagic,
            $this->clientPublicKey,
            $clientNonce,
            $encryptedQuery
        );
    }

    /**
     * Decrypt a DNScrypt response
     *
     * DNScrypt response is formatted as follow: <dnscrypt-response> ::= <resolver-magic> <nonce> <encrypted-response>
     * <resolver-magic> ::= 0x72 0x36 0x66 0x6e 0x76 0x57 0x6a 0x38
     *
     * <nonce> ::= <client-nonce> <resolver-nonce>
     * <client-nonce> ::= the nonce sent by the client in the related query.
     *
     * <client-pk> ::= the client's public key.
     *
     * <resolver-sk> ::= the resolver's public key.
     *
     * <resolver-nonce> ::= a unique response identifier for a given
     * (<client-pk>, <resolver-sk>) tuple. The length of <resolver-nonce>
     * depends on the chosen encryption algorithm.
     *
     * @param string $message
     *
     * @return string
     * @throws Exception
     */
    public function decrypt(string $message): string
    {
        $nonceLength = 24;
        $resolverMagicLength = 8;
        $nonce = substr($message, $resolverMagicLength, $nonceLength);
        $encryptedQuery = substr($message, $resolverMagicLength + $nonceLength);

        $decryptedMessage = sodium_crypto_box_open($encryptedQuery, $nonce, $this->keypair);

        return DnsCryptHelper::removePadding($decryptedMessage);
    }

    private function initKeys(): void
    {
        $clientKeyPair = ParagonIE_Sodium_Compat::crypto_box_keypair();
        $this->clientPublicKey = ParagonIE_Sodium_Compat::crypto_box_publickey($clientKeyPair);
        $this->clientSecretKey = ParagonIE_Sodium_Compat::crypto_box_secretkey($clientKeyPair);
        $this->keypair = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey(
            $this->clientSecretKey,
            $this->resolverPublicKey
        );
    }
}
