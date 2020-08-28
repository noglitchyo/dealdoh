<?php

declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Helper\DnsCryptHelper;
use ParagonIE_Sodium_Compat;
use ParagonIE_Sodium_Core_HChaCha20;
use SodiumException;

class XChacha20AuthenticatedEncryption implements AuthenticatedEncryptionInterface
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
    private $clientMagic;

    /**
     * @var string
     */
    private $resolverPublicKey;

    /**
     * @var string
     */
    private $sharedKey;

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
        [$clientNonce, $paddedClientNonce] = DnsCryptHelper::createClientNonce(
            ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_NONCEBYTES
        );

        $paddedQuery = DnsCryptHelper::addPadding($clientDnsWireQuery);

        $encryptedQuery = ParagonIE_Sodium_Compat::crypto_secretbox_xchacha20poly1305(
            $paddedQuery,
            $paddedClientNonce,
            $this->sharedKey
        );

        /**
         * TODO:
         * <min-query-len> is a variable length, initially set to 256 bytes, and
         * must be a multiple of 64 bytes.
         * <min-query-len> must be capped so that the full length of a DNSCrypt
         * packet doesn't exceed the maximum size required by the transport layer.
         * The client may decrease <min-query-len>, but the length must remain a multiple
         * of 64 bytes.
         */

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
        $resolverNonceLength = ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_NONCEBYTES;
        $resolverMagicLength = 8;
        $resolverNonce = substr($message, $resolverMagicLength, $resolverNonceLength);
        $encryptedQuery = substr($message, $resolverMagicLength + $resolverNonceLength);

        $decryptedMessage = ParagonIE_Sodium_Compat::crypto_secretbox_xchacha20poly1305_open(
            $encryptedQuery,
            $resolverNonce,
            $this->sharedKey
        );

        return DnsCryptHelper::removePadding($decryptedMessage);
    }



    private function initKeys(): void
    {
        $keypair = ParagonIE_Sodium_Compat::crypto_box_keypair();
        $this->clientPublicKey = ParagonIE_Sodium_Compat::crypto_box_publickey($keypair);
        $this->clientSecretKey = ParagonIE_Sodium_Compat::crypto_box_secretkey($keypair);
        $this->sharedKey = $this->createSharedKey($this->clientSecretKey, $this->resolverPublicKey);
    }

    /**
     * Create a shared key from the provided $secretKey and $publicKey
     * @param string $secretKey
     * @param string $publicKey
     *
     * @return string
     * @throws SodiumException
     */
    private function createSharedKey(string $secretKey, string $publicKey): string
    {
        $sharedPoint = ParagonIE_Sodium_Compat::crypto_scalarmult($secretKey, $publicKey);

        return ParagonIE_Sodium_Core_HChaCha20::hChaCha20(str_repeat("\0", 16), $sharedPoint);
    }
}
