# Cloudflare Integration

HestiaCP provides seamless integration with Cloudflare, allowing you to manage your domains' DNS, SSL/TLS, and caching features directly from the HestiaCP interface.

## Features

- DNS Management
  - Automatic DNS synchronization
  - Proxy status control
  - DNS record management

- SSL/TLS Management
  - SSL mode configuration (Off, Flexible, Full, Strict)
  - Certificate provisioning
  - Edge certificate management

- Cache Controls
  - Cache purge functionality
  - Cache rule management
  - Development mode toggle

## Prerequisites

Before using the Cloudflare integration, ensure you have:

1. A Cloudflare account
2. Domains added to Cloudflare
3. API token with the following permissions:
   - Zone:Read
   - DNS:Edit
   - SSL/TLS:Edit
   - Cache:Purge

## Installation

1. The Cloudflare integration is included in HestiaCP by default
2. Navigate to DNS > Cloudflare Settings in your HestiaCP panel
3. Enter your Cloudflare API token
4. Click Save to enable the integration

## Configuration

### API Token Setup

1. Log in to your Cloudflare dashboard
2. Navigate to User Profile > API Tokens
3. Click "Create Token"
4. Use the "Edit Zone DNS" template or create a custom token with required permissions
5. Add the token to HestiaCP's Cloudflare Settings

### Domain Setup

1. Add your domain to Cloudflare if not already added
2. Update your domain's nameservers to Cloudflare's nameservers
3. In HestiaCP, click "Add to Cloudflare" for your domain
4. Configure SSL and proxy settings as needed

## Usage

### DNS Management

```bash
# List Cloudflare zones
v-list-cloudflare-zones user [format]

# Add domain to Cloudflare
v-add-cloudflare-zone user domain

# Remove domain from Cloudflare
v-delete-cloudflare-zone user domain

# Sync DNS records
v-sync-dns-cloudflare user domain

# Toggle proxy status
v-change-dns-cloudflare-proxy user domain record [yes|no]
```

### SSL/TLS Management

```bash
# Change SSL mode
v-change-cloudflare-ssl user domain [mode]
# Modes: off, flexible, full, strict
```

### Cache Management

```bash
# Purge cache
v-purge-cloudflare-cache user domain [files]

# Toggle development mode
v-toggle-cloudflare-devmode user domain [on|off]
```

## Web Interface

### Cloudflare Settings Page

Access Cloudflare settings through DNS > Cloudflare Settings to:
- Configure API token
- View connected domains
- Manage SSL/TLS settings
- Control cache settings
- Toggle development mode

### DNS Records Page

The DNS records page includes Cloudflare-specific features:
- Proxy status toggle for A, AAAA, and CNAME records
- Cloudflare status indicators
- Quick access to Cloudflare controls

## Troubleshooting

### Common Issues

1. API Token Invalid
   - Verify token has required permissions
   - Check token hasn't expired
   - Ensure token is entered correctly

2. DNS Sync Failed
   - Check domain exists in Cloudflare
   - Verify nameservers are set correctly
   - Ensure API token has DNS:Edit permission

3. SSL Configuration Failed
   - Verify domain is active in Cloudflare
   - Check SSL/TLS permission in API token
   - Ensure domain has valid DNS configuration

### Error Messages

- "Invalid API token": Token is incorrect or lacks required permissions
- "Zone not found": Domain isn't added to Cloudflare
- "SSL configuration failed": Unable to change SSL mode
- "Cache purge failed": Issue with cache purge request

## API Documentation

### CloudflareAPI Class

The `CloudflareAPI` class provides methods for interacting with Cloudflare's API:

```php
// Initialize API
$api = new \Hestia\System\CloudflareAPI($token);

// Zone Management
$zones = $api->listZones();
$zone = $api->createZone($domain);
$success = $api->deleteZone($zoneId);

// DNS Management
$records = $api->listDnsRecords($zoneId);
$record = $api->createDnsRecord($zoneId, $recordData);
$success = $api->deleteDnsRecord($zoneId, $recordId);

// SSL/TLS Management
$config = $api->getSSLConfig($zoneId);
$result = $api->updateSSLConfig($zoneId, $mode);

// Cache Management
$success = $api->purgeCache($zoneId, $files);
$result = $api->updateDevMode($zoneId, $enabled);
```

## Security Considerations

1. API Token Security
   - Store tokens securely
   - Use minimum required permissions
   - Rotate tokens periodically

2. DNS Security
   - Monitor DNS changes
   - Review proxy status changes
   - Maintain DNSSEC if enabled

3. SSL/TLS Security
   - Use Full or Strict SSL mode when possible
   - Monitor certificate expiration
   - Review SSL/TLS settings regularly