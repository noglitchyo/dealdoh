<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\DnsCrypt;

interface EncryptedQueryInterface
{
    /**
     * the shared key derived from <resolver-pk> and
     * <client-sk>, using the key exchange algorithm defined in the chosen
     * certificate.
     * @return string
     */
    public function getSharedKey(): string;

    /**
     *  a unique query identifier for a given
     * (<client-sk>, <resolver-pk>) tuple. The same query sent twice for the same
     * (<client-sk>, <resolver-pk>) tuple must use two distinct <client-nonce>
     * values. The length of <client-nonce> depends on the chosen encryption
     * algorithm.
     * @return string
     */
    public function getClientNonce(): string;

    /**
     * <client-nonce> length is half the nonce length
     * required by the encryption algorithm. In client queries, the other half,
     * <client-nonce-pad> is filled with NUL bytes.
     * @return PadInterface
     */
    public function getClientNoncePad(): string;

    /**
     * the unencrypted client query. The query is not
     * modified; in particular, the query flags are not altered and the query
     * length must be kept in queries prepared to be sent over TCP.
     * @return mixed
     */
    public function getClientQuery(): string;


    /**
     * Must return the query
     * @return string
     */
    public function __toString();
}
