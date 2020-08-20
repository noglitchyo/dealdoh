<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Factory\DnsCrypt;

use Exception;
use NoGlitchYo\Dealdoh\Entity\Dns\Message\Section\ResourceRecordInterface;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\Certificate;
use NoGlitchYo\Dealdoh\Entity\DnsCrypt\CertificateInterface;

//use function Sodium\crypto_sign_ed25519_pk_to_curve25519;

class DnsCryptCertificateFactory
{
    public function createFromResourceRecord(
        ResourceRecordInterface $resourceRecord,
        string $providerPublicKey
    ): CertificateInterface {
        // check cert size 124
        $certificate = $resourceRecord->getData()[0];

        // Retrieve cert-magic
        $values = array_values(unpack('n*', $certificate));
        foreach (array_values(unpack('C*', $certificate)) as $char) {
            $t[] = chr($char);
        }

        // Construct cert-magic
        $certMagic = [ // we need to check the cert magic is valid
            $values[0] >> 8 & bindec('1111 1111'),
            $values[0] & bindec('1111 1111'),
            $values[1] >> 8 & bindec('1111 1111'),
            $values[1] & bindec('1111 1111'),
        ];
        //CertificateInterface::CERT_MAGIC;

        /**
         * the cryptographic construction to use with this
         * certificate.
         * For X25519-XSalsa20Poly1305, <es-version> must be 0x00 0x01.
         * For X25519-XChacha20Poly1305, <es-version> must be 0x00 0x02.
         */
        $esVersion = (int)$values[2];
        // TODO: validate this

        // Retrieve protocol-minor-version
        $protocolMinorVersion = $values[3];
        // TODO: validate this


        $key = [];
        foreach (array_slice($values, 4, CertificateInterface::SIGNATURE_LENGTH / 2) as $n) {
            $key[] = chr($n >> 8 & bindec('1111 1111'));
            $key[] = chr($n & bindec('1111 1111'));
        }

        // Retrieve 64-bytes signature using the Ed25519 algorithm and the
        // provider secret key. Ed25519 must be used in this version of the
        // protocol.
        $signature = substr($certificate, 8, 64); //implode('', $key);


        // the resolver short-term public key, which is 32 bytes when using X25519
        $key = [];
        foreach (array_slice($values, 36, 32 * 8 / 16) as $n) {
            $key[] = chr($n >> 8 & bindec('1111 1111'));
            $key[] = chr($n & bindec('1111 1111'));
        }
        $resolverPublicKey = implode('', $key);

        // Construct client-magic (8-bytes)
        // TODO: assert that two valid certificates cannot share the same client-magic
        $key = [];
        foreach (unpack('n*', substr($certificate, 104, 8)) as $n) {
            $key[] = chr($n >> 8 & bindec('1111 1111'));
            $key[] = chr($n & bindec('1111 1111'));
        }
        $clientMagic = implode('', $key);

        /**
         * a 4 byte serial number in big-endian format. If more than
         * one certificates are valid, the client must prefer the certificate
         * with a higher serial number.
         */
        $serial = (int)unpack('N', substr($certificate, 112, 4))[1];

        /**
         * the date the certificate is valid from, as a big-endian
         * 4-byte unsigned Unix timestamp.
         */
        $tsStart = (int)unpack('N', substr($certificate, 116, 4))[1];

        /**
         * the date the certificate is valid until (inclusive), as a
         * big-endian 4-byte unsigned Unix timestamp.
         */
        $tsEnd = (int)unpack('N', substr($certificate, 120, 4))[1];

        /**
         * The resolver responds with a public set of signed certificates, that
         * must be verified by the client using a previously distributed public
         * key, known as the provider public key .
         */
        $providerPublicKey = sodium_hex2bin($providerPublicKey);

        $isVerified = sodium_crypto_sign_verify_detached($signature, substr($certificate, 72), $providerPublicKey);
        if ($isVerified === false) {
            throw new Exception('Could not verify signature with provider public key');
        }

        return new Certificate($esVersion, $signature, $serial, $tsStart, $tsEnd, $clientMagic, $resolverPublicKey);
    }
}
