<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface CertificateInterface
{
    public const CERT_LENGTH_WITHOUT_EXT = 116;
    public const CERT_LENGTH_WITH_EXT = 124;

    public const SIGNATURE_LENGTH = 64;

    public const ES_VERSION_XSALSA20POLY1305 = 0x00 . 0x01;
    public const ES_VERSION_XCHACHA20POLY1305 = 0x00 . 0x02;

    /**
     * === DNSC
     */
    public const CERT_MAGIC = 0x44 . 0x4e . 0x53 . 0x43;

    public const PROTOCOL_MINOR_VERSION = 0x00 . 0x00;

    /**
     * The cryptographic construction to use with this certificate.
     *
     * For X25519-XSalsa20Poly1305, <es-version> must be 0x00 0x01.
     * For X25519-XChacha20Poly1305, <es-version> must be 0x00 0x02.
     *
     * @return int
     */
    public function getEsVersion(): int;

    public function getAuthenticatedEncryptionAlgorithm(): string;

    public function getAuthenticatedEncryptionParameters(): array;

    /**
     * a 64-byte signature of (<resolver-pk> <client-magic>
     * <serial> <ts-start> <ts-end> <extensions>) using the Ed25519 algorithm and the
     * provider secret key. Ed25519 must be used in this version of the
     * protocol.
     *
     * @return string
     */
    public function getSignature(): string;

    /**
     * The resolver short-term public key, which is 32 bytes when
     * using X25519.
     * @return string
     */
    public function getResolverPublicKey(): string;

    /**
     * The first 8 bytes of a client query that was built
     * using the information from this certificate. It may be a truncated
     * public key. Two valid certificates cannot share the same <client-magic>.
     *
     * @return string
     */
    public function getClientMagic(): string;

    /**
     * a 4 byte serial number in big-endian format. If more than
     * one certificates are valid, the client must prefer the certificate
     * with a higher serial number.
     * @return int
     */
    public function getSerial(): int;

    /**
     * the date the certificate is valid from, as a big-endian
     * 4-byte unsigned Unix timestamp.
     * @return int
     */
    public function getTsStart(): int;

    /**
     * The date the certificate is valid until (inclusive), as a
     * big-endian 4-byte unsigned Unix timestamp.
     */
    public function getTsEnd(): int;

    /**
     * empty in the current protocol version, but may
     * contain additional data in future revisions, including minor versions.
     * The computation and the verification of the signature must include the
     * extensions. An implementation not supporting these extensions must
     * ignore them.
     */
    public function getExtensions(): void;
}
