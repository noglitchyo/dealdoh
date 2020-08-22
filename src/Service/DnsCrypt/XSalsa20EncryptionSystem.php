<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;

class XSalsa20EncryptionSystem implements EncryptionSystemInterface
{
    public const PADDING_START = 0x80;

    /**
     * @var CertificateInterface
     */
    private $certificate;
    /**
     * @var string
     */
    private $clientKeyPair;
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
    private $sharedKey;

    public function __construct(CertificateInterface $certificate)
    {
        $this->certificate = $certificate;
        $this->clientKeyPair = sodium_crypto_box_keypair();
        $this->clientPublicKey = sodium_crypto_box_publickey($this->clientKeyPair);
        $this->clientSecretKey = sodium_crypto_box_secretkey($this->clientKeyPair);
        /**
         * When using X25519-XSalsa20Poly1305, this construction requires a 24 bytes
         * nonce, that must not be reused for a given shared secret.
         */
        $this->sharedKey = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $this->clientSecretKey,
            $this->certificate->getResolverPublicKey()
        );
    }

    public function supports(CertificateInterface $certificate): bool
    {
        return $certificate->getEsVersion() === CertificateInterface::ES_VERSION_XSALSA20POLY1305;
    }

    public function encrypt(string $clientDnsWireQuery): DnsCryptQuery
    {
        /**
         * With a 24 bytes nonce, a question sent by a DNSCrypt client must be
         * encrypted using the shared secret, and a nonce constructed as follows:
         * 12 bytes chosen by the client followed by 12 NUL (0) bytes.
         */
        [$clientNonce, $clientNonceWithPad] = $this->createClientNonce(SODIUM_CRYPTO_BOX_NONCEBYTES);

        $encryptedQuery = $this->encryptWithXsalsa20(
            $this->getClientQueryWithPadding($clientDnsWireQuery),
            // <client-query> <client-query-pad> must be at least <min-query-len>
            $clientNonceWithPad,
            $this->sharedKey
        );
        $dnsCryptQuery = new DnsCryptQuery(
            $this->certificate->getClientMagic(),
            $this->clientPublicKey,
            $clientNonce,
            $encryptedQuery
        );


        return $dnsCryptQuery;
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
        $decryptedMessage = $this->decryptWithXsalsa20($message, $this->sharedKey);

        return $this->removePaddingFromMessage($decryptedMessage);
    }

    private function removePaddingFromMessage(string $message): string
    {
        return substr($message, 0, strrpos($message, 0x80));
    }

    private function decryptWithXsalsa20(string $response, string $keypair): string
    {
        $nonceLength = 24;
        $resolverMagicLength = 8;
        $nonce = substr($response, $resolverMagicLength, $nonceLength);
        $encryptedQuery = substr($response, $resolverMagicLength + $nonceLength);

        return sodium_crypto_box_open($encryptedQuery, $nonce, $keypair);
    }

    private function encryptWithXsalsa20(string $message, string $nonce, string $keypair): string
    {
        return sodium_crypto_box($message, $nonce, $keypair);
    }

    private function encryptWithXchacha20(string $message, string $nonce, string $key): string
    {
        return sodium_crypto_aead_chacha20poly1305_ietf_encrypt($message, $nonce, $nonce, $key);
    }

    /**
     * <client-nonce> length is half the nonce length
     * required by the encryption algorithm. In client queries, the other half,
     * <client-nonce-pad> is filled with NUL bytes.
     * @return array
     * @throws Exception
     */
    private function createClientNonce(int $nonceLength): array
    {
        $clientNonce = random_bytes($nonceLength / 2);
        $clientNonceWithPad = $clientNonce . str_repeat(
                "\0",
                $nonceLength / 2
            ); // half the required nonce length  + 12 null bytes

        return [$clientNonce, $clientNonceWithPad];
    }

    /**
     * Prior to encryption, queries are padded using the ISO/IEC 7816-4
     * format.
     *
     * The padding starts with a byte valued 0x80 followed by a
     * variable number of NUL bytes.
     *
     * <client-query> <client-query-pad> must be at least <min-query-len>
     * <min-query-len> is a variable length, initially set to 256 bytes, and
     * must be a multiple of 64 bytes.
     *
     * @param string $clientQuery
     *
     * @return string
     */
    private function getClientQueryWithPadding(string $clientQuery)
    {
        // Check if query greater than min query length
        $queryLength = strlen($clientQuery);
        $paddingLength = 256;

        if ($queryLength > $paddingLength) {
            $paddingLength = $queryLength + (64 - ($queryLength % 64));
        }

        return sodium_pad($clientQuery . static::PADDING_START, $paddingLength);
    }
}
