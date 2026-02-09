# FusionPBX REST API Documentation

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Base URL](#base-url)
- [Common Headers](#common-headers)
- [Response Format](#response-format)
- [Error Handling](#error-handling)
- [Pagination](#pagination)
- [Rate Limiting](#rate-limiting)
- [API Endpoints](#api-endpoints)
  - [Extensions](#extensions)
  - [Users](#users)
  - [Gateways](#gateways)
  - [Devices](#devices)
  - [Voicemails](#voicemails)
  - [CDR (Call Detail Records)](#cdr-call-detail-records)
  - [Active Calls](#active-calls)
  - [Registrations](#registrations)
  - [Ring Groups](#ring-groups)
  - [IVR Menus](#ivr-menus)
  - [Call Flows](#call-flows)
  - [Conferences](#conferences)
  - [Inbound Routes](#inbound-routes)
  - [Outbound Routes](#outbound-routes)
  - [Dialplans](#dialplans)
  - [Call Centers](#call-centers)
  - [Fax](#fax)
  - [Domains](#domains)
  - [Dashboard](#dashboard)

---

## Overview

The FusionPBX REST API provides programmatic access to FusionPBX telephony features. This API follows RESTful principles and uses JSON for request and response bodies.

**Version:** 1.0

**API Stability:** Production-ready

---

## Authentication

The API uses header-based authentication with two required headers:

- **X-API-Key**: Your API secret key
- **X-Domain**: Domain name or UUID to scope operations

### Authentication Modes

The API supports three authentication modes (configured in `config.php`):

1. **global_key**: Single API key shared across all domains (from FusionPBX settings)
2. **per_key**: Per-domain API keys stored in `v_api_keys` table
3. **both**: Try per-key first, fall back to global key

### Example Authentication Headers

```http
X-API-Key: your-secret-api-key-here
X-Domain: example.com
```

Or using domain UUID:

```http
X-API-Key: your-secret-api-key-here
X-Domain: 550e8400-e29b-41d4-a716-446655440000
```

### Security Notes

- Always use HTTPS in production (configurable via `security.require_https`)
- API keys are sensitive credentials - store securely
- IP whitelisting can be enabled via `security.allowed_ips` config

---

## Base URL

```
https://your-fusionpbx-server.com/api/v1/
```

Example from the provided configuration:
```
https://voip.davincitechsolutions.us/api/v1/
```

---

## Common Headers

### Required Headers

| Header | Description | Example |
|--------|-------------|---------|
| `X-API-Key` | API authentication key | `abc123def456` |
| `X-Domain` | Domain name or UUID | `example.com` |
| `Content-Type` | Request body format (for POST/PUT) | `application/json` |

### Response Headers

| Header | Description |
|--------|-------------|
| `Content-Type` | Always `application/json` |
| `X-RateLimit-Limit` | Rate limit maximum (requests per minute) |
| `X-RateLimit-Remaining` | Remaining requests in current window |
| `X-RateLimit-Reset` | Unix timestamp when limit resets |

---

## Response Format

All API responses follow a consistent JSON structure.

### Success Response

```json
{
  "success": true,
  "data": { /* resource data */ },
  "message": "Optional success message",
  "pagination": { /* pagination info (for list endpoints) */ }
}
```

### Success Response (201 Created)

```json
{
  "success": true,
  "data": {
    "resource_uuid": "550e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Resource created successfully"
}
```

### Success Response (204 No Content)

For DELETE operations, the API returns HTTP 204 with no body.

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "field": "field_name (optional)"
  }
}
```

### Validation Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "errors": [
      {
        "field": "extension",
        "message": "extension is required"
      }
    ]
  }
}
```

### HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| 200 | OK - Request succeeded |
| 201 | Created - Resource created successfully |
| 204 | No Content - Resource deleted successfully |
| 400 | Bad Request - Invalid request data |
| 401 | Unauthorized - Invalid or missing API key |
| 403 | Forbidden - Access denied or IP not whitelisted |
| 404 | Not Found - Resource not found |
| 405 | Method Not Allowed - HTTP method not supported |
| 409 | Conflict - Duplicate resource |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |
| 501 | Not Implemented - Endpoint not implemented |
| 503 | Service Unavailable - FreeSWITCH connection failed |

### Common Error Codes

| Error Code | Description |
|------------|-------------|
| `UNAUTHORIZED` | Invalid or missing API key |
| `MISSING_DOMAIN` | X-Domain header required |
| `INVALID_DOMAIN` | Domain not found or disabled |
| `DOMAIN_MISMATCH` | API key doesn't have access to domain |
| `VALIDATION_ERROR` | Request validation failed |
| `DUPLICATE_ERROR` | Resource already exists |
| `NOT_FOUND` | Resource not found |
| `FORBIDDEN` | Access denied |
| `METHOD_NOT_ALLOWED` | HTTP method not supported |
| `NOT_IMPLEMENTED` | Endpoint not implemented |
| `HTTPS_REQUIRED` | HTTPS required but HTTP used |
| `IP_DENIED` | IP address not whitelisted |
| `ESL_NOT_AVAILABLE` | Event Socket Library not available |
| `ESL_CONNECTION_FAILED` | Cannot connect to FreeSWITCH |

---

## Pagination

List endpoints support pagination via query parameters.

### Pagination Query Parameters

| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| `page` | integer | 1 | - | Page number (1-indexed) |
| `per_page` | integer | 50 | 100 | Items per page |

### Pagination Response

```json
{
  "success": true,
  "data": [ /* array of items */ ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 150,
    "total_pages": 3
  }
}
```

### Example Request

```http
GET /api/v1/extensions?page=2&per_page=25
X-API-Key: your-api-key
X-Domain: example.com
```

---

## Rate Limiting

The API enforces rate limits to prevent abuse.

### Default Limits

- **60 requests per minute** (per API key)
- **1000 requests per hour** (per API key)

Configurable in `config.php`:
```php
'rate_limit' => [
    'enabled' => true,
    'requests_per_minute' => 60,
    'requests_per_hour' => 1000,
]
```

### Rate Limit Headers

Every response includes rate limit headers:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1678901234
```

### Rate Limit Exceeded Response

**HTTP 429 Too Many Requests**

```json
{
  "error": "Rate limit exceeded",
  "message": "Too many requests. Please try again later.",
  "limit_type": "minute",
  "retry_after": 30,
  "reset": 1678901234
}
```

Response includes `Retry-After` header with seconds to wait.

---

## API Endpoints

### Extensions

Manage phone extensions (SIP endpoints).

#### List Extensions

```http
GET /api/v1/extensions
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "extension_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "extension": "1001",
      "number_alias": null,
      "effective_caller_id_name": "John Doe",
      "effective_caller_id_number": "1001",
      "outbound_caller_id_name": "",
      "outbound_caller_id_number": "",
      "user_context": "example.com",
      "enabled": "true",
      "description": "John's Extension"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### Get Extension

```http
GET /api/v1/extensions/{extension_uuid}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "extension_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "extension": "1001",
    "number_alias": null,
    "effective_caller_id_name": "John Doe",
    "effective_caller_id_number": "1001",
    "outbound_caller_id_name": "",
    "outbound_caller_id_number": "",
    "emergency_caller_id_name": "",
    "emergency_caller_id_number": "",
    "directory_first_name": "John",
    "directory_last_name": "Doe",
    "directory_visible": "true",
    "directory_exten_visible": "true",
    "max_registrations": "1",
    "limit_max": "5",
    "limit_destination": "error/user_busy",
    "user_context": "example.com",
    "enabled": "true",
    "description": "John's Extension",
    "forward_all_destination": "",
    "forward_all_enabled": "false",
    "forward_busy_destination": "",
    "forward_busy_enabled": "false",
    "forward_no_answer_destination": "",
    "forward_no_answer_enabled": "false",
    "forward_user_not_registered_destination": "",
    "forward_user_not_registered_enabled": "false",
    "follow_me_uuid": null,
    "do_not_disturb": "false",
    "accountcode": "",
    "voicemail": {
      "voicemail_uuid": "660e8400-e29b-41d4-a716-446655440000",
      "voicemail_id": "1001",
      "voicemail_password": "123456",
      "voicemail_mail_to": "john@example.com",
      "voicemail_enabled": "true",
      "voicemail_description": ""
    },
    "user": {
      "user_uuid": "770e8400-e29b-41d4-a716-446655440000",
      "username": "john",
      "full_name": "John Doe"
    }
  }
}
```

#### Create Extension

```http
POST /api/v1/extensions
Content-Type: application/json
```

**Request Body:**
```json
{
  "extension": "1001",
  "password": "secure-password",
  "accountcode": "",
  "effective_caller_id_name": "John Doe",
  "effective_caller_id_number": "1001",
  "outbound_caller_id_name": "",
  "outbound_caller_id_number": "",
  "emergency_caller_id_name": "",
  "emergency_caller_id_number": "",
  "directory_first_name": "John",
  "directory_last_name": "Doe",
  "directory_visible": "true",
  "directory_exten_visible": "true",
  "max_registrations": "1",
  "limit_max": "5",
  "limit_destination": "error/user_busy",
  "enabled": "true",
  "description": "John's Extension",
  "voicemail_enabled": "true",
  "voicemail_password": "123456",
  "voicemail_mail_to": "john@example.com",
  "user_uuid": "770e8400-e29b-41d4-a716-446655440000"
}
```

**Required Fields:**
- `extension` (string): Extension number

**Optional Fields:**
- `password` (string): SIP password (auto-generated if not provided)
- `accountcode` (string): Account code for billing
- `effective_caller_id_name` (string): Caller ID name
- `effective_caller_id_number` (string): Caller ID number
- `outbound_caller_id_name` (string): Outbound caller ID name
- `outbound_caller_id_number` (string): Outbound caller ID number
- `emergency_caller_id_name` (string): Emergency caller ID name
- `emergency_caller_id_number` (string): Emergency caller ID number
- `directory_first_name` (string): Directory first name
- `directory_last_name` (string): Directory last name
- `directory_visible` (string): Show in directory ("true"/"false")
- `directory_exten_visible` (string): Show extension in directory ("true"/"false")
- `max_registrations` (string): Maximum simultaneous registrations
- `limit_max` (string): Maximum concurrent calls
- `limit_destination` (string): Destination when limit reached
- `enabled` (string): Extension enabled ("true"/"false")
- `description` (string): Extension description
- `voicemail_enabled` (string): Enable voicemail ("true"/"false")
- `voicemail_password` (string): Voicemail PIN (auto-generated if not provided)
- `voicemail_mail_to` (string): Email for voicemail notifications
- `user_uuid` (string): Link extension to user

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "extension_uuid": "550e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Extension created successfully"
}
```

#### Update Extension

```http
PUT /api/v1/extensions/{extension_uuid}
Content-Type: application/json
```

**Request Body:** (All fields optional - only include fields to update)
```json
{
  "extension": "1002",
  "password": "new-password",
  "effective_caller_id_name": "John Smith",
  "enabled": "true",
  "voicemail_password": "654321",
  "voicemail_mail_to": "johnsmith@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "extension_uuid": "550e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Extension updated successfully"
}
```

#### Delete Extension

```http
DELETE /api/v1/extensions/{extension_uuid}
```

**Response:** HTTP 204 No Content

**Notes:**
- Deletes associated voicemail, call forwarding, and removes from ring groups
- Regenerates FreeSWITCH XML configuration
- Clears cache automatically

---

### Users

Manage user accounts for FusionPBX web interface access.

#### List Users

```http
GET /api/v1/users
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "user_uuid": "770e8400-e29b-41d4-a716-446655440000",
      "username": "john",
      "user_email": "john@example.com",
      "user_enabled": "true"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### Create User

```http
POST /api/v1/users
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "john",
  "password": "SecurePassword123!",
  "user_email": "john@example.com",
  "user_enabled": "true",
  "user_status": "Available",
  "user_language": "en-us",
  "user_time_zone": "America/New_York",
  "groups": ["user", "admin"],
  "contact_name_given": "John",
  "contact_name_family": "Doe",
  "contact_organization": "Acme Corp",
  "contact_email": "john@example.com",
  "contact_url": "https://example.com",
  "contact_nickname": "Johnny"
}
```

**Required Fields:**
- `username` (string): Username for login
- `password` (string): Password (will be hashed with bcrypt)

**Optional Fields:**
- `user_email` (string): User email address
- `user_enabled` (string): Account enabled ("true"/"false")
- `user_status` (string): User status
- `user_language` (string): UI language code
- `user_time_zone` (string): User timezone
- `api_key` (string): API key for this user
- `groups` (array): Group names to add user to
- `contact_name_given` (string): First name
- `contact_name_family` (string): Last name
- `contact_organization` (string): Organization name
- `contact_email` (string): Contact email
- `contact_url` (string): Contact URL
- `contact_nickname` (string): Nickname

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "user_uuid": "770e8400-e29b-41d4-a716-446655440000"
  },
  "message": "User created successfully"
}
```

---

### Gateways

Manage SIP trunks/gateways for external connectivity.

#### List Gateways

```http
GET /api/v1/gateways
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "gateway_uuid": "880e8400-e29b-41d4-a716-446655440000",
      "gateway": "carrier1",
      "username": "account123",
      "proxy": "sip.carrier.com",
      "register": "true",
      "profile": "external",
      "enabled": "true",
      "description": "Primary Carrier"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### Create Gateway

```http
POST /api/v1/gateways
Content-Type: application/json
```

**Request Body:**
```json
{
  "gateway": "carrier1",
  "username": "account123",
  "password": "secret",
  "auth_username": "account123",
  "realm": "sip.carrier.com",
  "from_user": "account123",
  "from_domain": "sip.carrier.com",
  "proxy": "sip.carrier.com",
  "register_proxy": "sip.carrier.com",
  "outbound_proxy": "",
  "expire_seconds": "800",
  "register": "true",
  "register_transport": "udp",
  "retry_seconds": "30",
  "extension": "",
  "ping": "25",
  "context": "public",
  "profile": "external",
  "enabled": "true",
  "description": "Primary Carrier",
  "hostname": "",
  "distinct_to": "false",
  "contact_params": "",
  "caller_id_in_from": "false",
  "supress_cng": "false",
  "sip_cid_type": "none",
  "codec_prefs": "PCMU,PCMA"
}
```

**Required Fields:**
- `gateway` (string): Gateway name
- `proxy` (string): SIP proxy hostname/IP

**Optional Fields:** (All gateway configuration options)

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "gateway_uuid": "880e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Gateway created successfully"
}
```

**Notes:**
- Automatically regenerates gateway XML configuration
- Clears sofia config cache
- Reloads gateway in FreeSWITCH via Event Socket

---

### Devices

Manage provisioned phones and devices.

#### List Devices

```http
GET /api/v1/devices
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "device_uuid": "990e8400-e29b-41d4-a716-446655440000",
      "device_address": "001122334455",
      "device_vendor": "Yealink",
      "device_model": "T46S",
      "device_enabled": "true",
      "device_label": "Reception Phone",
      "device_description": "Front desk phone"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Voicemails

Manage voicemail boxes.

#### List Voicemails

```http
GET /api/v1/voicemails
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "voicemail_uuid": "aa0e8400-e29b-41d4-a716-446655440000",
      "voicemail_id": "1001",
      "voicemail_mail_to": "john@example.com",
      "voicemail_enabled": "true",
      "voicemail_description": "John's Voicemail",
      "new_message_count": 3
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### CDR (Call Detail Records)

Query call history and call detail records.

#### List CDR Records

```http
GET /api/v1/cdr
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `start_date` (string): Filter by start date (YYYY-MM-DD)
- `end_date` (string): Filter by end date (YYYY-MM-DD)
- `direction` (string): Filter by direction ("inbound", "outbound", "local")
- `extension` (string): Filter by extension number
- `caller_id` (string): Filter by caller ID (partial match)

**Example:**
```http
GET /api/v1/cdr?start_date=2024-01-01&end_date=2024-01-31&direction=inbound&page=1
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "xml_cdr_uuid": "bb0e8400-e29b-41d4-a716-446655440000",
      "direction": "inbound",
      "caller_id_name": "John Doe",
      "caller_id_number": "5551234567",
      "caller_destination": "1001",
      "destination_number": "1001",
      "start_stamp": "2024-01-15 10:30:45",
      "answer_stamp": "2024-01-15 10:30:50",
      "end_stamp": "2024-01-15 10:35:30",
      "duration": 285,
      "billsec": 280,
      "hangup_cause": "NORMAL_CLEARING",
      "recording_file": "recording_1234.wav"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 150,
    "total_pages": 3
  }
}
```

---

### Active Calls

Monitor and control active calls in real-time.

#### List Active Calls

```http
GET /api/v1/active-calls
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "call_uuid": "cc0e8400-e29b-41d4-a716-446655440000",
      "caller_id_name": "John Doe",
      "caller_id_number": "1001",
      "destination_number": "5551234567",
      "call_direction": "outbound",
      "call_state": "CS_EXECUTE",
      "created_time": "2024-01-15 10:30:45",
      "created_epoch": 1705318245,
      "duration": 125
    }
  ]
}
```

**Notes:**
- Retrieves real-time call data from FreeSWITCH via Event Socket
- Filters calls by authenticated domain
- No caching (always live data)

#### Hangup Call

```http
POST /api/v1/active-calls/hangup
Content-Type: application/json
```

**Request Body:**
```json
{
  "call_uuid": "cc0e8400-e29b-41d4-a716-446655440000"
}
```

**Required Fields:**
- `call_uuid` (string): UUID of call to terminate

**Response:**
```json
{
  "success": true,
  "data": {
    "call_uuid": "cc0e8400-e29b-41d4-a716-446655440000",
    "result": "+OK"
  },
  "message": "Call terminated successfully"
}
```

**Notes:**
- Verifies call belongs to authenticated domain before hangup
- Uses FreeSWITCH `uuid_kill` command

#### Transfer Call

```http
POST /api/v1/active-calls/transfer
Content-Type: application/json
```

**Request Body:**
```json
{
  "call_uuid": "cc0e8400-e29b-41d4-a716-446655440000",
  "destination": "1002"
}
```

**Required Fields:**
- `call_uuid` (string): UUID of call to transfer
- `destination` (string): Transfer destination (digits, +, *, # only)

**Response:**
```json
{
  "success": true,
  "data": {
    "call_uuid": "cc0e8400-e29b-41d4-a716-446655440000",
    "destination": "1002",
    "result": "+OK"
  },
  "message": "Call transferred successfully"
}
```

**Notes:**
- Verifies call belongs to authenticated domain
- Destination is sanitized (max 50 chars, digits/+/*/#only)
- Uses FreeSWITCH `uuid_transfer` command

---

### Registrations

View active SIP registrations.

#### List Registrations

```http
GET /api/v1/registrations
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "user": "1001",
      "sip_realm": "example.com",
      "contact": "sofia/internal/1001@192.168.1.100:5060",
      "agent": "Yealink SIP-T46S 66.85.0.5",
      "status": "Registered",
      "ping_time": "523",
      "network_ip": "192.168.1.100",
      "network_port": "5060",
      "profile": "internal"
    }
  ]
}
```

**Notes:**
- Queries FreeSWITCH sofia status via Event Socket
- Filters registrations by authenticated domain
- Parses registration data from all enabled SIP profiles

---

### Ring Groups

Manage ring groups for simultaneous/sequential call distribution.

#### List Ring Groups

```http
GET /api/v1/ring-groups
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "ring_group_uuid": "dd0e8400-e29b-41d4-a716-446655440000",
      "ring_group_name": "Sales Team",
      "ring_group_extension": "2000",
      "ring_group_strategy": "simultaneous",
      "ring_group_timeout_app": "transfer",
      "ring_group_timeout_data": "1001",
      "ring_group_cid_name_prefix": "",
      "ring_group_cid_number_prefix": "",
      "ring_group_enabled": "true",
      "ring_group_description": "Sales department"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### Create Ring Group

```http
POST /api/v1/ring-groups
Content-Type: application/json
```

**Request Body:**
```json
{
  "ring_group_name": "Sales Team",
  "ring_group_extension": "2000",
  "ring_group_strategy": "simultaneous",
  "ring_group_timeout_app": "transfer",
  "ring_group_timeout_data": "1001",
  "ring_group_cid_name_prefix": "",
  "ring_group_cid_number_prefix": "",
  "ring_group_enabled": "true",
  "ring_group_description": "Sales department",
  "destinations": [
    {
      "destination_number": "1001",
      "destination_delay": "0",
      "destination_timeout": "30",
      "destination_prompt": "",
      "destination_enabled": "true"
    },
    {
      "destination_number": "1002",
      "destination_delay": "0",
      "destination_timeout": "30",
      "destination_prompt": "",
      "destination_enabled": "true"
    }
  ]
}
```

**Required Fields:**
- `ring_group_name` (string): Ring group name
- `ring_group_extension` (string): Ring group extension number

**Optional Fields:**
- `ring_group_strategy` (string): Ring strategy ("simultaneous", "sequence", "rollover", "random")
- `ring_group_timeout_app` (string): Timeout application
- `ring_group_timeout_data` (string): Timeout data
- `ring_group_cid_name_prefix` (string): Caller ID name prefix
- `ring_group_cid_number_prefix` (string): Caller ID number prefix
- `ring_group_enabled` (string): Enabled ("true"/"false")
- `ring_group_description` (string): Description
- `destinations` (array): Array of destination objects

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "ring_group_uuid": "dd0e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Ring group created successfully"
}
```

---

### IVR Menus

Manage Interactive Voice Response (IVR) menus.

#### List IVR Menus

```http
GET /api/v1/ivr-menus
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "ivr_menu_uuid": "ee0e8400-e29b-41d4-a716-446655440000",
      "ivr_menu_name": "Main Menu",
      "ivr_menu_extension": "5000",
      "ivr_menu_language": "en",
      "ivr_menu_timeout": "3000",
      "ivr_menu_exit_app": "hangup",
      "ivr_menu_enabled": "true",
      "ivr_menu_description": "Main IVR menu"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### Create IVR Menu

```http
POST /api/v1/ivr-menus
Content-Type: application/json
```

**Request Body:**
```json
{
  "ivr_menu_name": "Main Menu",
  "ivr_menu_extension": "5000",
  "ivr_menu_language": "en",
  "ivr_menu_greet_long": "welcome.wav",
  "ivr_menu_greet_short": "welcome_short.wav",
  "ivr_menu_invalid_sound": "invalid.wav",
  "ivr_menu_exit_sound": "goodbye.wav",
  "ivr_menu_timeout": "3000",
  "ivr_menu_inter_digit_timeout": "2000",
  "ivr_menu_max_failures": "3",
  "ivr_menu_max_timeouts": "3",
  "ivr_menu_digit_len": "5",
  "ivr_menu_direct_dial": "false",
  "ivr_menu_exit_app": "hangup",
  "ivr_menu_exit_data": "",
  "ivr_menu_enabled": "true",
  "ivr_menu_description": "Main IVR menu",
  "options": [
    {
      "ivr_menu_option_digits": "1",
      "ivr_menu_option_action": "menu-exec-app",
      "ivr_menu_option_param": "transfer 1001 XML example.com",
      "ivr_menu_option_order": "10",
      "ivr_menu_option_enabled": "true",
      "ivr_menu_option_description": "Sales"
    },
    {
      "ivr_menu_option_digits": "2",
      "ivr_menu_option_action": "menu-exec-app",
      "ivr_menu_option_param": "transfer 1002 XML example.com",
      "ivr_menu_option_order": "20",
      "ivr_menu_option_enabled": "true",
      "ivr_menu_option_description": "Support"
    }
  ]
}
```

**Required Fields:**
- `ivr_menu_name` (string): IVR menu name
- `ivr_menu_extension` (string): IVR menu extension

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "ivr_menu_uuid": "ee0e8400-e29b-41d4-a716-446655440000"
  },
  "message": "IVR menu created successfully"
}
```

---

### Call Flows

Manage call flow toggle features.

#### List Call Flows

```http
GET /api/v1/call-flows
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "call_flow_uuid": "ff0e8400-e29b-41d4-a716-446655440000",
      "call_flow_name": "After Hours",
      "call_flow_extension": "3000",
      "call_flow_feature_code": "*00",
      "call_flow_status": "true",
      "call_flow_enabled": "true",
      "call_flow_description": "Toggle after hours routing"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Conferences

Manage conference bridges.

#### List Conferences

```http
GET /api/v1/conferences
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "conference_uuid": "110e8400-e29b-41d4-a716-446655440000",
      "conference_name": "Weekly Meeting",
      "conference_extension": "4000",
      "conference_pin": "123456",
      "moderator_pin": "654321",
      "wait_mod": "false",
      "announce_name": "true",
      "announce_count": "true",
      "announce_recording": "false",
      "mute": "false",
      "sounds": "default",
      "member_type": "moderator",
      "profile": "default",
      "max_members": "50",
      "record": "false",
      "enabled": "true",
      "description": "Weekly team meeting"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Inbound Routes

Manage inbound call routing rules.

#### List Inbound Routes

```http
GET /api/v1/inbound-routes
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "dialplan_uuid": "220e8400-e29b-41d4-a716-446655440000",
      "dialplan_name": "Main Number",
      "dialplan_number": "5551234567",
      "dialplan_context": "public",
      "dialplan_order": "100",
      "dialplan_enabled": "true",
      "dialplan_description": "Main company number"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Outbound Routes

Manage outbound call routing rules.

#### List Outbound Routes

```http
GET /api/v1/outbound-routes
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "dialplan_uuid": "330e8400-e29b-41d4-a716-446655440000",
      "dialplan_name": "Local Calls",
      "dialplan_number": "^(\\d{10})$",
      "dialplan_context": "example.com",
      "dialplan_order": "100",
      "dialplan_enabled": "true",
      "dialplan_description": "10-digit local calls"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Dialplans

Manage custom dialplan entries (both endpoints use the same structure).

#### List Dialplans

```http
GET /api/v1/dialplans
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "dialplan_uuid": "440e8400-e29b-41d4-a716-446655440000",
      "dialplan_name": "Custom Route",
      "dialplan_number": "^123$",
      "dialplan_context": "example.com",
      "dialplan_order": "100",
      "dialplan_enabled": "true",
      "dialplan_description": "Custom dialplan entry"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

**Notes:**
- Dialplans are FusionPBX's routing rules
- Inbound routes use app_uuid: `c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4`
- Outbound routes use app_uuid: `8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3`

---

### Call Centers

Manage call center queues and agents.

#### List Call Center Queues

```http
GET /api/v1/call-centers/queues
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `queue_enabled` (string): Filter by enabled status
- `queue_strategy` (string): Filter by queue strategy

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "call_center_queue_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "queue_name": "support",
      "queue_extension": "7000",
      "queue_strategy": "longest-idle-agent",
      "queue_moh_sound": "local_stream://default",
      "queue_record_template": "",
      "queue_time_base_score": "queue",
      "queue_max_wait_time": "0",
      "queue_max_wait_time_with_no_agent": "90",
      "queue_tier_rules_apply": "false",
      "queue_tier_rule_wait_second": "30",
      "queue_tier_rule_wait_multiply_level": "true",
      "queue_tier_rule_no_agent_no_wait": "false",
      "queue_discard_abandoned_after": "900",
      "queue_abandoned_resume_allowed": "false",
      "queue_announce_sound": "",
      "queue_announce_frequency": "0",
      "queue_description": "Support queue",
      "queue_enabled": "true"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### List Call Center Agents

```http
GET /api/v1/call-centers/agents
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `agent_status` (string): Filter by agent status
- `agent_type` (string): Filter by agent type

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "call_center_agent_uuid": "660e8400-e29b-41d4-a716-446655440000",
      "user_uuid": "770e8400-e29b-41d4-a716-446655440000",
      "agent_name": "1001@example.com",
      "agent_type": "callback",
      "agent_call_timeout": "30",
      "agent_contact": "user/1001@example.com",
      "agent_status": "Available",
      "agent_logout": "false",
      "agent_max_no_answer": "3",
      "agent_wrap_up_time": "10",
      "agent_reject_delay_time": "10",
      "agent_busy_delay_time": "60",
      "agent_no_answer_delay_time": "30"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Fax

Manage fax accounts and fax files.

#### List Fax Accounts

```http
GET /api/v1/fax/accounts
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `fax_extension` (string): Filter by extension
- `fax_name` (string): Filter by name
- `fax_enabled` (string): Filter by enabled status

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "fax_uuid": "770e8400-e29b-41d4-a716-446655440000",
      "fax_extension": "6000",
      "fax_name": "Main Fax",
      "fax_email": "fax@example.com",
      "fax_pin_number": "1234",
      "fax_caller_id_name": "Company Fax",
      "fax_caller_id_number": "5551234567",
      "fax_forward_number": "",
      "fax_description": "Main fax line",
      "fax_send_channels": "1"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

#### List Fax Files

```http
GET /api/v1/fax/files
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `fax_uuid` (string): Filter by fax account UUID
- `fax_mode` (string): Filter by mode ("send"/"receive")
- `fax_status` (string): Filter by status
- `date_from` (string): Filter from date (YYYY-MM-DD)
- `date_to` (string): Filter to date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "fax_file_uuid": "880e8400-e29b-41d4-a716-446655440000",
      "fax_uuid": "770e8400-e29b-41d4-a716-446655440000",
      "fax_mode": "receive",
      "fax_file_type": "pdf",
      "fax_file_path": "/path/to/fax.pdf",
      "fax_caller_id_name": "John Doe",
      "fax_caller_id_number": "5559876543",
      "fax_destination": "6000",
      "fax_date": "2024-01-15",
      "fax_time": "14:30:45",
      "fax_epoch": 1705333845,
      "fax_status": "success",
      "fax_retry_count": 0
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1,
    "total_pages": 1
  }
}
```

---

### Domains

View authenticated domain information.

#### List Domains

```http
GET /api/v1/domains
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "domain_uuid": "990e8400-e29b-41d4-a716-446655440000",
      "domain_name": "example.com",
      "domain_enabled": "true",
      "domain_description": "Example domain"
    }
  ]
}
```

**Notes:**
- Returns only the authenticated domain (domain-scoped)
- No pagination (single record)

---

### Dashboard

Get statistics and overview data.

#### Get Dashboard Stats

```http
GET /api/v1/dashboard/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "extensions": {
      "total": 50
    },
    "users": {
      "total": 25
    },
    "gateways": {
      "total": 3
    },
    "voicemails": {
      "new_messages": 8
    },
    "calls": {
      "today_total": 142
    }
  }
}
```

**Notes:**
- Provides quick overview statistics for the domain
- No caching (always current data)

---

## Additional Endpoints

The following endpoints are available but follow similar patterns to those documented above:

### Destinations
- `GET /api/v1/destinations` - List dialplan destinations
- `GET /api/v1/destinations/{uuid}` - Get destination details
- `POST /api/v1/destinations` - Create destination
- `PUT /api/v1/destinations/{uuid}` - Update destination
- `DELETE /api/v1/destinations/{uuid}` - Delete destination

### Call Forward
- `GET /api/v1/call-forward` - List call forwarding rules
- `GET /api/v1/call-forward/{uuid}` - Get call forward details
- `POST /api/v1/call-forward` - Create call forward
- `PUT /api/v1/call-forward/{uuid}` - Update call forward
- `DELETE /api/v1/call-forward/{uuid}` - Delete call forward

### Follow Me
- `GET /api/v1/follow-me` - List follow me entries
- `GET /api/v1/follow-me/{uuid}` - Get follow me details
- `POST /api/v1/follow-me` - Create follow me
- `PUT /api/v1/follow-me/{uuid}` - Update follow me
- `DELETE /api/v1/follow-me/{uuid}` - Delete follow me

### Call Block
- `GET /api/v1/call-block` - List blocked numbers
- `GET /api/v1/call-block/{uuid}` - Get call block details
- `POST /api/v1/call-block` - Create call block
- `PUT /api/v1/call-block/{uuid}` - Update call block
- `DELETE /api/v1/call-block/{uuid}` - Delete call block

### Call Recordings
- `GET /api/v1/call-recordings` - List call recordings
- `GET /api/v1/call-recordings/{uuid}` - Get recording details
- `DELETE /api/v1/call-recordings/{uuid}` - Delete recording

### Access Controls
- `GET /api/v1/access-controls` - List access control lists
- `GET /api/v1/access-controls/{uuid}` - Get ACL details
- `POST /api/v1/access-controls` - Create ACL
- `PUT /api/v1/access-controls/{uuid}` - Update ACL
- `DELETE /api/v1/access-controls/{uuid}` - Delete ACL

### Access Control Nodes
- `GET /api/v1/access-controls/nodes` - List ACL nodes
- `POST /api/v1/access-controls/nodes` - Create ACL node
- `DELETE /api/v1/access-controls/nodes/{uuid}` - Delete ACL node

### SIP Profiles
- `GET /api/v1/sip-profiles` - List SIP profiles
- `GET /api/v1/sip-profiles/{uuid}` - Get SIP profile details
- `POST /api/v1/sip-profiles` - Create SIP profile
- `PUT /api/v1/sip-profiles/{uuid}` - Update SIP profile
- `DELETE /api/v1/sip-profiles/{uuid}` - Delete SIP profile

### SIP Profile Settings
- `GET /api/v1/sip-profiles/settings` - List SIP profile settings
- `POST /api/v1/sip-profiles/settings` - Create setting
- `PUT /api/v1/sip-profiles/settings/{uuid}` - Update setting
- `DELETE /api/v1/sip-profiles/settings/{uuid}` - Delete setting

### Number Translations
- `GET /api/v1/number-translations` - List number translations
- `GET /api/v1/number-translations/{uuid}` - Get translation details
- `POST /api/v1/number-translations` - Create translation
- `PUT /api/v1/number-translations/{uuid}` - Update translation
- `DELETE /api/v1/number-translations/{uuid}` - Delete translation

### Time Conditions
- `GET /api/v1/time-conditions` - List time conditions
- `GET /api/v1/time-conditions/{uuid}` - Get time condition details
- `POST /api/v1/time-conditions` - Create time condition
- `PUT /api/v1/time-conditions/{uuid}` - Update time condition
- `DELETE /api/v1/time-conditions/{uuid}` - Delete time condition

### Queues (Email/Fax)
- `GET /api/v1/queues/email` - List email queues
- `GET /api/v1/queues/fax` - List fax queues

### Logs
- `GET /api/v1/logs/security` - List security logs
- `GET /api/v1/logs/emergency` - List emergency logs
- `GET /api/v1/logs/audit` - List audit logs

### Conference Centers
- `GET /api/v1/conference-centers/centers` - List conference centers
- `GET /api/v1/conference-centers/rooms` - List conference rooms
- `GET /api/v1/conference-centers/profiles` - List conference profiles

### Monitoring
- `GET /api/v1/monitoring` - System monitoring endpoints

---

## Configuration Reference

### API Configuration File

Location: `/api/v1/config.php`

Key configuration options:

```php
return [
    'version' => '1.0',

    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000,
        'by_key' => true,
    ],

    'pagination' => [
        'default_per_page' => 50,
        'max_per_page' => 100,
        'min_per_page' => 1,
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 300,
        'exclude_endpoints' => [
            'active-calls',
            'registrations',
            'dashboard/stats',
        ],
    ],

    'security' => [
        'require_https' => false,  // Set to true in production
        'allowed_origins' => [],    // CORS origins
        'max_request_size' => 10485760,  // 10MB
        'auth_mode' => 'global_key',  // 'global_key', 'per_key', 'both'
        'allowed_ips' => [],  // IP whitelist (empty = allow all)
    ],

    'freeswitch' => [
        'auto_regenerate_xml' => true,
        'clear_cache_on_update' => true,
    ],

    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_errors' => true,
        'log_slow_requests' => true,
        'slow_request_threshold' => 1000,  // milliseconds
    ],
];
```

---

## Best Practices

### Security

1. **Always use HTTPS in production**
   - Set `security.require_https = true` in config
   - Use valid SSL certificates

2. **Protect API keys**
   - Store keys securely (environment variables, vault)
   - Rotate keys periodically
   - Use per-domain keys when possible

3. **Enable IP whitelisting**
   - Configure `security.allowed_ips` for production
   - Limit access to known IPs

4. **Enable CORS properly**
   - Configure `security.allowed_origins` with specific domains
   - Never use wildcard `*` with credentials in production

### Performance

1. **Use pagination**
   - Always paginate list endpoints
   - Keep `per_page` reasonable (default 50, max 100)

2. **Monitor rate limits**
   - Check `X-RateLimit-*` headers
   - Implement exponential backoff for 429 responses

3. **Cache responses client-side**
   - Cache relatively static data (extensions, users)
   - Respect API cache headers

### Error Handling

1. **Handle all error codes**
   - Check `success` field in all responses
   - Parse `error.code` for programmatic handling
   - Display `error.message` to users

2. **Implement retries**
   - Retry on 503 (service unavailable)
   - Use exponential backoff for 429 (rate limit)
   - Don't retry on 4xx client errors

3. **Validate before sending**
   - Validate UUIDs before sending
   - Check required fields client-side
   - Sanitize user input

### Integration

1. **Domain scoping**
   - All operations are scoped to authenticated domain
   - Users cannot access other domains' resources

2. **UUID handling**
   - UUIDs are the primary identifier for all resources
   - Store UUIDs for future operations (update/delete)
   - Validate UUID format before API calls

3. **FreeSWITCH integration**
   - API automatically regenerates XML when needed
   - Cache is cleared automatically
   - Event Socket operations (calls, registrations) require FreeSWITCH connection

---

## Examples

### Laravel HTTP Client Example

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FusionPBXClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $domain;

    public function __construct()
    {
        $this->baseUrl = config('fusionpbx.api_url');
        $this->apiKey = config('fusionpbx.api_key');
        $this->domain = config('fusionpbx.domain');
    }

    protected function request(string $method, string $endpoint, array $data = [])
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'X-Domain' => $this->domain,
        ])->$method("{$this->baseUrl}/{$endpoint}", $data);

        if ($response->failed()) {
            throw new \Exception(
                $response->json('error.message', 'API request failed'),
                $response->status()
            );
        }

        return $response->json();
    }

    // List extensions with pagination
    public function listExtensions(int $page = 1, int $perPage = 50): array
    {
        return $this->request('get', 'extensions', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    // Get single extension
    public function getExtension(string $uuid): array
    {
        return $this->request('get', "extensions/{$uuid}");
    }

    // Create extension
    public function createExtension(array $data): array
    {
        return $this->request('post', 'extensions', $data);
    }

    // Update extension
    public function updateExtension(string $uuid, array $data): array
    {
        return $this->request('put', "extensions/{$uuid}", $data);
    }

    // Delete extension
    public function deleteExtension(string $uuid): void
    {
        $this->request('delete', "extensions/{$uuid}");
    }

    // List active calls
    public function listActiveCalls(): array
    {
        return $this->request('get', 'active-calls');
    }

    // Hangup call
    public function hangupCall(string $callUuid): array
    {
        return $this->request('post', 'active-calls/hangup', [
            'call_uuid' => $callUuid,
        ]);
    }

    // Transfer call
    public function transferCall(string $callUuid, string $destination): array
    {
        return $this->request('post', 'active-calls/transfer', [
            'call_uuid' => $callUuid,
            'destination' => $destination,
        ]);
    }

    // Get dashboard stats
    public function getDashboardStats(): array
    {
        return $this->request('get', 'dashboard/stats');
    }

    // List CDR with filters
    public function listCDR(array $filters = []): array
    {
        return $this->request('get', 'cdr', $filters);
    }
}
```

### Usage Example

```php
<?php

use App\Services\FusionPBXClient;

// Initialize client
$fusionpbx = new FusionPBXClient();

// List extensions
$result = $fusionpbx->listExtensions(page: 1, perPage: 50);
$extensions = $result['data'];
$pagination = $result['pagination'];

// Create extension
$newExtension = $fusionpbx->createExtension([
    'extension' => '1001',
    'password' => 'SecurePass123!',
    'effective_caller_id_name' => 'John Doe',
    'effective_caller_id_number' => '1001',
    'enabled' => 'true',
    'voicemail_enabled' => 'true',
    'voicemail_password' => '123456',
]);

$extensionUuid = $newExtension['data']['extension_uuid'];

// Update extension
$fusionpbx->updateExtension($extensionUuid, [
    'effective_caller_id_name' => 'John Smith',
    'description' => 'Updated description',
]);

// Get active calls
$activeCalls = $fusionpbx->listActiveCalls();

// Hangup a call
if (!empty($activeCalls['data'])) {
    $callUuid = $activeCalls['data'][0]['call_uuid'];
    $fusionpbx->hangupCall($callUuid);
}

// Get CDR with filters
$cdrResult = $fusionpbx->listCDR([
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'direction' => 'inbound',
    'page' => 1,
    'per_page' => 100,
]);
```

---

## Troubleshooting

### Common Issues

#### 401 Unauthorized

**Cause:** Invalid or missing API key

**Solution:**
- Check `X-API-Key` header is present
- Verify API key matches FusionPBX configuration
- Check `security.auth_mode` in config.php

#### 403 Forbidden

**Causes:**
- IP address not whitelisted
- Domain mismatch
- HTTPS required but HTTP used

**Solution:**
- Add IP to `security.allowed_ips`
- Verify `X-Domain` header matches API key domain
- Enable HTTPS

#### 404 Not Found

**Causes:**
- Invalid endpoint URL
- Resource UUID not found

**Solution:**
- Check endpoint path matches documentation
- Verify UUID exists and belongs to domain

#### 429 Too Many Requests

**Cause:** Rate limit exceeded

**Solution:**
- Check `X-RateLimit-*` headers
- Implement request throttling
- Wait for `Retry-After` seconds
- Consider increasing limits in config

#### 503 Service Unavailable

**Cause:** Cannot connect to FreeSWITCH Event Socket

**Solution:**
- Check FreeSWITCH is running
- Verify Event Socket Library configuration
- Check network connectivity

### Debug Mode

Enable detailed logging in `config.php`:

```php
'logging' => [
    'enabled' => true,
    'log_requests' => true,
    'log_errors' => true,
    'log_slow_requests' => true,
    'slow_request_threshold' => 1000,
]
```

---

## Changelog

### Version 1.0

Initial release with support for:
- Extensions management
- Users management
- Gateways management
- Call control (hangup, transfer)
- Real-time monitoring (active calls, registrations)
- CDR queries
- Ring groups
- IVR menus
- Call flows
- Conferences
- Dialplan management
- Call centers
- Fax management
- And more...

---

## Support

For issues, feature requests, or questions:

1. Check this documentation thoroughly
2. Review FusionPBX logs
3. Check FreeSWITCH Event Socket connection
4. Verify API configuration in `config.php`
5. Test with curl/Postman before implementing in code

---

## License

This API is part of FusionPBX. Refer to FusionPBX licensing for details.
