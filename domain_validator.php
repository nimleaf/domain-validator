<?php

final class DomainValidator
{
    /** @var bool */
    private $validateSpecialChars;

    /** 
     * @var array List of second-level domains (SLDs) that are registrable.
     */
    private $registrableSlds = [
        'AC' => true,
        'CO' => true,
        'COM' => true,
        'EDU' => true,
        'GOV' => true,
        'ID' => true,
        'ME' => true,
        'NET' => true,
        'ORG' => true,
    ];

    /** @var array */
    private $validTlds;

    public function __construct(bool $validateSpecialChars)
    {
        $this->validateSpecialChars = $validateSpecialChars;
        $this->validTlds = include_once 'valid_tl_domains.php';
    }

    /**
     * Extract TLD, domain, and optional subdomain from a hostname string.
     *
     * @param string $hostname
     * @return array|null Returns an array with 'tld', 'domain', and 'subdomain' if valid, or null if invalid.
     */
    private function extractDomainParts(string $hostname): ?array
    {
        // If special characters validation is enabled, check if the hostname contains only valid characters
        if ($this->validateSpecialChars && !preg_match('/^[a-zA-Z0-9\.\-\_]+$/', $hostname)) {
            return null;
        }

        // Split the hostname into parts, and then get the last three elements of the array
        $parts = array_slice(explode(".", $hostname), -3);

        // If there are at least two parts (domain and TLD), extract them
        if (count($parts) >= 2) {
            return [
                'tld' => array_pop($parts),
                'domain' => array_pop($parts),
                'subdomain' => array_pop($parts) ?? '', // Optional Subdomain (empty if not present)
            ];
        }

        return null;
    }

    /**
     * Check if the given string is a subdomain of a Country Code Top-Level Domain (ccTLD).
     * @param string $string The string (potential subdomain) to check.
     * @return bool Returns true if the string is a registrable second-level domain (SLD)
     */
    private function isRegistrableSld(string $string): bool
    {
        return isset($this->registrableSlds[strtoupper($string)]);
    }

    /**
     * Check if the given string is a Top-Level Domain (TLD).
     * @param string $string The string to check.
     * @return bool True if the string is a TLD, otherwise false.
     */
    private function isTld(string $string): bool
    {
        return isset($this->validTlds[strtoupper($string)]);
    }

    /**
     * Validates a single hostname and returns its validation result.
     *
     * @param string $hostname The hostname to be processed and validated.
     * @return array An associative array containing:
     *  - 'name' (string): The constructed domain name.
     *  - 'valid' (bool): True if the domain is valid, false otherwise.
     */
    public function execute($hostname): array
    {
        $hostname = trim($hostname);
        // Extract domain parts (subdomain, domain, and TLD)
        $parts = $this->extractDomainParts($hostname);

        if ($parts === null) {
            // No domain found (invalid format)
            return [
                'name' => $hostname,
                'valid' => false,
            ];
        }

        // Check if the extracted TLD is a valid TLD
        if (!$this->isTld($parts['tld'])) {
            // Invalid TLD
            return [
                'name' => $parts['domain'] . '.' . $parts['tld'],
                'valid' => false,
            ];
        }

        if ($this->isRegistrableSld($parts['domain'])) {
            // Domain is a ccTLD with a subdomain requirement
            $subdomain = $parts['subdomain'] === '' ? '' : $parts['subdomain'] . '.';
            return [
                'name' => $subdomain . $parts['domain'] . '.' . $parts['tld'],
                'valid' => $parts['subdomain'] !== '',
            ];
        }

        // Valid TLD
        return [
            'name' => $parts['domain'] . '.' . $parts['tld'],
            'valid' => true,
        ];
    }
}
