<?php

namespace Hestia\System;

/**
 * CloudflareAPI class handles interactions with the Cloudflare API
 * for managing DNS records, SSL certificates, and cache controls.
 */
class CloudflareAPI {
    private string $apiToken;
    private string $baseUrl = 'https://api.cloudflare.com/client/v4';
    private ?string $lastError = null;

    /**
     * Initialize CloudflareAPI with authentication token
     * 
     * @param string $apiToken Cloudflare API token with required permissions
     */
    public function __construct(string $apiToken) {
        $this->apiToken = $apiToken;
    }

    /**
     * Get the last error message
     * 
     * @return string|null Last error message or null if no error
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    /**
     * Make an authenticated request to Cloudflare API
     * 
     * @param string $endpoint API endpoint (e.g., /zones)
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array $data Request data for POST/PUT methods
     * @return array|false Response data or false on failure
     */
    private function request(string $endpoint, string $method = 'GET', array $data = []): array|false {
        $ch = curl_init($this->baseUrl . $endpoint);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        $result = json_decode($response, true);

        if ($statusCode >= 400 || !isset($result['success']) || !$result['success']) {
            $this->lastError = $result['errors'][0]['message'] ?? 'Unknown API error';
            return false;
        }

        return $result;
    }

    /**
     * Verify API token has required permissions
     * 
     * @return bool True if token is valid and has required permissions
     */
    public function verifyToken(): bool {
        $result = $this->request('/user/tokens/verify');
        return $result !== false;
    }

    /**
     * List zones (domains) associated with the account
     * 
     * @return array|false Array of zones or false on failure
     */
    public function listZones(): array|false {
        $result = $this->request('/zones');
        return $result ? $result['result'] : false;
    }

    /**
     * Get zone ID for a domain
     * 
     * @param string $domain Domain name
     * @return string|false Zone ID or false if not found
     */
    public function getZoneId(string $domain): string|false {
        $result = $this->request('/zones?name=' . urlencode($domain));
        if (!$result || empty($result['result'])) {
            $this->lastError = 'Zone not found for domain: ' . $domain;
            return false;
        }
        return $result['result'][0]['id'];
    }

    /**
     * List DNS records for a zone
     * 
     * @param string $zoneId Zone ID
     * @return array|false Array of DNS records or false on failure
     */
    public function listDnsRecords(string $zoneId): array|false {
        $result = $this->request("/zones/{$zoneId}/dns_records");
        return $result ? $result['result'] : false;
    }

    /**
     * Create DNS record
     * 
     * @param string $zoneId Zone ID
     * @param array $record DNS record data (type, name, content, proxied)
     * @return array|false Created record data or false on failure
     */
    public function createDnsRecord(string $zoneId, array $record): array|false {
        return $this->request("/zones/{$zoneId}/dns_records", 'POST', $record);
    }

    /**
     * Update DNS record
     * 
     * @param string $zoneId Zone ID
     * @param string $recordId DNS record ID
     * @param array $record Updated DNS record data
     * @return array|false Updated record data or false on failure
     */
    public function updateDnsRecord(string $zoneId, string $recordId, array $record): array|false {
        return $this->request("/zones/{$zoneId}/dns_records/{$recordId}", 'PUT', $record);
    }

    /**
     * Delete DNS record
     * 
     * @param string $zoneId Zone ID
     * @param string $recordId DNS record ID
     * @return bool True on success, false on failure
     */
    public function deleteDnsRecord(string $zoneId, string $recordId): bool {
        $result = $this->request("/zones/{$zoneId}/dns_records/{$recordId}", 'DELETE');
        return $result !== false;
    }

    /**
     * Create a new zone (domain) in Cloudflare
     *
     * @param string $domain Domain name
     * @param array $settings Optional zone settings
     * @return array|false Zone data or false on failure
     */
    public function createZone(string $domain, array $settings = []): array|false {
        $data = array_merge([
            'name' => $domain,
            'jump_start' => true,
            'type' => 'full'
        ], $settings);

        $result = $this->request('/zones', 'POST', $data);
        return $result ? $result['result'] : false;
    }

    /**
     * Delete a zone from Cloudflare
     *
     * @param string $zoneId Zone ID
     * @return bool True on success, false on failure
     */
    public function deleteZone(string $zoneId): bool {
        $result = $this->request("/zones/{$zoneId}", 'DELETE');
        return $result !== false;
    }

    /**
     * Update zone settings
     *
     * @param string $zoneId Zone ID
     * @param array $settings Zone settings to update
     * @return array|false Updated zone data or false on failure
     */
    public function updateZoneSettings(string $zoneId, array $settings): array|false {
        $result = $this->request("/zones/{$zoneId}/settings", 'PATCH', $settings);
        return $result ? $result['result'] : false;
    }

    /**
     * Get SSL/TLS configuration for a zone
     *
     * @param string $zoneId Zone ID
     * @return array|false SSL configuration or false on failure
     */
    public function getSSLConfig(string $zoneId): array|false {
        $result = $this->request("/zones/{$zoneId}/settings/ssl");
        return $result ? $result['result'] : false;
    }

    /**
     * Update SSL/TLS configuration for a zone
     *
     * @param string $zoneId Zone ID
     * @param string $mode SSL mode (off, flexible, full, strict)
     * @return array|false Updated configuration or false on failure
     */
    public function updateSSLConfig(string $zoneId, string $mode): array|false {
        return $this->request("/zones/{$zoneId}/settings/ssl", 'PATCH', ['value' => $mode]);
    }

    /**
     * Get SSL certificate details
     *
     * @param string $zoneId Zone ID
     * @return array|false Certificate details or false on failure
     */
    public function getSSLCertificates(string $zoneId): array|false {
        $result = $this->request("/zones/{$zoneId}/ssl/certificate_packs");
        return $result ? $result['result'] : false;
    }

    /**
     * Order new SSL certificate
     *
     * @param string $zoneId Zone ID
     * @param string $type Certificate type (advanced, custom)
     * @return array|false Order details or false on failure
     */
    public function orderSSLCertificate(string $zoneId, string $type = 'advanced'): array|false {
        return $this->request("/zones/{$zoneId}/ssl/certificate_packs", 'POST', ['type' => $type]);
    }

    /**
     * Purge cache for a zone
     *
     * @param string $zoneId Zone ID
     * @param array $files Optional specific files to purge
     * @return bool True on success, false on failure
     */
    public function purgeCache(string $zoneId, array $files = []): bool {
        $data = empty($files) ? ['purge_everything' => true] : ['files' => $files];
        $result = $this->request("/zones/{$zoneId}/purge_cache", 'POST', $data);
        return $result !== false;
    }

    /**
     * Get cache rules for a zone
     *
     * @param string $zoneId Zone ID
     * @return array|false Cache rules or false on failure
     */
    public function getCacheRules(string $zoneId): array|false {
        $result = $this->request("/zones/{$zoneId}/cache/rules");
        return $result ? $result['result'] : false;
    }

    /**
     * Create cache rule
     *
     * @param string $zoneId Zone ID
     * @param array $rule Rule configuration
     * @return array|false Created rule or false on failure
     */
    public function createCacheRule(string $zoneId, array $rule): array|false {
        return $this->request("/zones/{$zoneId}/cache/rules", 'POST', $rule);
    }

    /**
     * Update development mode
     *
     * @param string $zoneId Zone ID
     * @param bool $enabled Whether to enable development mode
     * @return array|false Updated configuration or false on failure
     */
    public function updateDevMode(string $zoneId, bool $enabled): array|false {
        return $this->request(
            "/zones/{$zoneId}/settings/development_mode",
            'PATCH',
            ['value' => $enabled ? 'on' : 'off']
        );
    }
}