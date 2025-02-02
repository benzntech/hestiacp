# Cloudflare API Configuration
# This configuration file manages Cloudflare integration settings

# API Authentication
API_TOKEN='%token%'

# DNS Management
PROXY_ENABLED='yes'
DEFAULT_DNS_TTL='1'
AUTO_SYNC_DNS='yes'

# SSL/TLS Settings
SSL_MODE='full'  # Options: off, flexible, full, strict
ALWAYS_USE_HTTPS='yes'
MIN_TLS_VERSION='1.2'

# Cache Settings
AUTO_PURGE_CACHE='yes'
DEVELOPMENT_MODE='no'

# Logging
DEBUG_MODE='no'
LOG_LEVEL='error'  # Options: debug, info, warning, error

# Feature Flags
ENABLE_DNS_MANAGEMENT='yes'
ENABLE_SSL_MANAGEMENT='yes'
ENABLE_CACHE_MANAGEMENT='yes'