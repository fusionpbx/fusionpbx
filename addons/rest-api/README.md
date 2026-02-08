# FusionPBX REST API Addon

Comprehensive REST API for FusionPBX telephony system.

## Structure

```
addons/rest-api/
├── api/v1/           # REST API endpoints (193 files)
│   ├── auth.php      # Authentication
│   ├── base.php      # Base controller helpers
│   ├── cache.php     # Caching strategy
│   ├── config.php    # API configuration
│   ├── monitoring.php # Metrics collection
│   ├── openapi.yaml  # OpenAPI 3.0 specification
│   ├── response.php  # Response helpers
│   └── [endpoints]/  # ring-groups, extensions, etc.
├── tests/
│   ├── api/          # Integration tests
│   └── performance/  # Benchmark tests
└── migrations/       # Database migration framework
```

## Installation

1. Create a symlink from FusionPBX root:
   ```bash
   ln -s addons/rest-api/api api
   ```

2. Or copy to FusionPBX root:
   ```bash
   cp -r addons/rest-api/api /var/www/fusionpbx/
   ```

## API Documentation

See `api/v1/openapi.yaml` for full API specification.

### Authentication

All requests require:
- `X-API-Key`: Your API key
- `X-Domain`: Domain name for multi-tenant scoping

### Endpoints

| Category | Endpoints |
|----------|-----------|
| Core | extensions, users, gateways, devices, voicemails |
| Routing | destinations, dialplans, inbound-routes, outbound-routes |
| Features | ring-groups, ivr-menus, call-flows, conferences |
| Advanced | call-centers, call-block, call-recordings |
| Real-time | active-calls, registrations |
| Logs | audit, security, emergency |

## Running Tests

```bash
cd addons/rest-api/tests/api
cp config.php.example config.php
# Edit config.php with your API credentials
phpunit
```

## No Database Changes

This addon uses existing FusionPBX database tables. No schema modifications required.

## Compatibility

- FusionPBX 5.5.x
- PHP 7.4+
- PostgreSQL
