<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Service\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\DnsCryptQuery;
use NoGlitchYo\Dealdoh\Helper\DnsCryptHelper;

class XSalsa20AuthenticatedEncryption implements AuthenticatedEncryptionInterface
{
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
        $this->certificate     = $certificate;
        $this->clientKeyPair   = sodium_crypto_box_keypair();
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

        $encryptedQuery = sodium_crypto_box(
            DnsCryptHelper::addUdpPadding($clientDnsWireQuery),
            // <client-query> <client-query-pad> must be at least <min-query-len>
            $clientNonceWithPad,
            $this->sharedKey
        );

        return new DnsCryptQuery(
            $this->certificate->getClientMagic(),
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
        $nonceLength         = 24;
        $resolverMagicLength = 8;
        $nonce               = substr($message, $resolverMagicLength, $nonceLength);
        $encryptedQuery      = substr($message, $resolverMagicLength + $nonceLength);

        $decryptedMessage = sodium_crypto_box_open($encryptedQuery, $nonce, $this->sharedKey);

        return DnsCryptHelper::removeUdpPadding($decryptedMessage);
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
        $clientNonce        = random_bytes($nonceLength / 2);
        $clientNonceWithPad = $clientNonce . str_repeat(
                "\0",
                $nonceLength / 2
            ); // half the required nonce length  + 12 null bytes

        return [$clientNonce, $clientNonceWithPad];
    }
}
