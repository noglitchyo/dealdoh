<?php declare(strict_types=1);

namespace NoGlitchYo\Dealdoh\Entity\GoogleDns;

use JsonSerializable;

/**
 * Portions of this page are reproduced from work created and shared by Google and used according to terms described in
 * the Creative Commons 3.0 Attribution License.
 *
 * @see https://developers.google.com/speed/public-dns/docs/dns-over-https
 */
interface RequestMessageInterface extends JsonSerializable
{
    /**
     * The only required parameter. Its length must be between 1 and 253 (ignoring an optional trailing dot if present).
     * All labels (parts of the name separated by dots) must be 1 to 63 bytes long.
     * The API does not support names with escaped or non-ASCII characters, but they are not explicitly rejected.
     * Internationalized domain names must use punycode format (e.g. "xn--qxam" rather than "ελ").
     *
     * @return string
     */
    public function getName(): string;

    /**
     * RR type can be represented as a number in [1, 65535] or a canonical string (case-insensitive, such as A or aaaa).
     * You can use 255 for 'ANY' queries but be aware that this is not a replacement for sending queries for
     * both A and AAAA or MX records. Authoritative name servers need not return all records for such queries;
     * some do not respond, and others (such as cloudflare.com) return only HINFO.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * The CD (checking disabled) bit. Use cd, cd=1, or cd=true to disable DNSSEC validation; use cd=0, cd=false,
     * or no cd parameter to enable DNSSEC validation.
     *
     * @return bool
     */
    public function getCd(): bool;

    /**
     * The edns0-client-subnet option. Format is an IP address with a subnet mask.
     * Examples: 1.2.3.4/24, 2001:700:300::/48.
     *
     * If you are using DNS-over-HTTPS because of privacy concerns, and do not want any part of your IP address
     * to be sent to authoritative name servers for geographic location accuracy, use edns_client_subnet=0.0.0.0/0.
     * Google Public DNS normally sends approximate network information
     * (usually zeroing out the last part of your IPv4 address).
     *
     * @return string
     */
    public function getEdnsClientSubnet(): string;

    /**
     * The value of this parameter is ignored. Example: XmkMw~o_mgP2pf.gpw-Oi5dK.
     *
     * API clients concerned about possible side-channel privacy attacks using the packet sizes of HTTPS GET requests
     * can use this to make all requests exactly the same size by padding requests with random data.
     * To prevent misinterpretation of the URL, restrict the padding characters to the unreserved URL characters: upper-
     * and lower-case letters, digits, hyphen, period, underscore and tilde.
     *
     * @return string
     */
    public function getRandomPadding(): string;
}
