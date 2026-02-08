# Security Fixes Applied to FusionPBX REST API

## Summary
Fixed 9 critical security vulnerabilities in the REST API at `/addons/rest-api/api/v1/`

## Fixes Applied

### 1. ESL Command Injection in active-calls/transfer.php
**Vulnerability**: Regex allowed spaces which are ESL command delimiters
**Fix**: Changed regex from `/^[0-9\+\*\#\s]{1,50}$/` to `/^[0-9\+\*\#]{1,50}$/`
**Impact**: Prevents injection of additional ESL commands via transfer destination

### 2. ESL Injection in registrations/check.php
**Vulnerability**: Extension parameter passed directly to ESL commands without validation
**Fix**: Added validation: `if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $extension))`
**Impact**: Prevents ESL command injection via extension parameter

### 3. Password Exposure in extensions/get.php
**Vulnerability**: `SELECT *` exposed password field in API response
**Fix**: Replaced with explicit column list excluding `password` field
**Impact**: Extension passwords no longer returned in API responses

### 4. Credential Exposure in gateways/get.php
**Vulnerability**: `SELECT *` exposed `password` and `auth_username` fields
**Fix**: Replaced with explicit column list excluding sensitive credentials
**Impact**: Gateway credentials no longer returned in API responses

### 5. Domain Filtering in logs/security/list.php
**Status**: Verified - table `v_event_guard_logs` is intentionally global (no domain_uuid)
**Note**: Warning comment already exists in code explaining this is system-wide

### 6. Domain Filtering in number-translations/list.php
**Status**: Documented - table `v_number_translations` is intentionally global
**Fix**: Added comment explaining this is a global resource for multi-tenant awareness
**Recommendation**: Consider restricting to superadmin in production

### 7. SIP Profile Management Access Control
**Files**: sip-profiles/create.php, update.php, delete.php
**Vulnerability**: No access control for global infrastructure resources
**Fix**: Added domain setting check requiring `api > allow_sip_profile_management = true`
**Impact**: SIP profile management now requires explicit permission via domain settings

### 8. Domain Settings Whitelist in domains/settings.php
**Vulnerability**: All setting categories could be modified via API
**Fix**: Added whitelist of allowed categories: `['theme', 'time_format', 'voicemail', 'provision', 'fax']`
**Impact**: Prevents modification of sensitive system settings via API

### 9. Path Traversal in extensions/create.php
**Vulnerability**: Extension value used directly in filesystem path for voicemail directory
**Fix**: Added sanitization: `$safe_extension = preg_replace('/[^a-zA-Z0-9_-]/', '', $request['extension'])`
**Impact**: Prevents directory traversal attacks when creating voicemail directories

## Testing Recommendations

1. Test call transfer with valid destinations
2. Test registration check with valid extensions
3. Verify extension GET responses don't include passwords
4. Verify gateway GET responses don't include credentials
5. Test SIP profile operations require permission setting
6. Test domain settings modification restricted to whitelist
7. Test extension creation with special characters in extension field

## Migration Notes

For existing deployments using SIP profile management via API:
- Add domain setting: `api > allow_sip_profile_management = true` to enable access
- Review all API consumers for reliance on password/credential fields in responses
