# FusionPBX REST API v1 Documentation

## Overview

The FusionPBX REST API provides programmatic access to manage extensions, users, gateways, devices, voicemails, dialplans, call records, and more. The API follows RESTful principles using standard HTTP methods (GET, POST, PUT, DELETE) and returns JSON responses.

**Base URL:** `/api/v1/`

**Version:** 1.0
**Last Updated:** 2026-02-04

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Pagination](#pagination)
5. [Error Handling](#error-handling)
6. [Endpoints](#endpoints)
7. [Testing Examples](#testing-examples)
8. [Implementation Notes](#implementation-notes)

---

## Getting Started

### Prerequisites

- Access to a FusionPBX installation
- API key configured in FusionPBX system settings
- Domain UUID or domain name
- HTTP client (curl, Postman, etc.)

### Quick Start Example

```bash
curl -X GET https://your-pbx.example.com/api/v1/extensions/list.php \
  -H "X-API-Key: your-api-secret-key" \
  -H "X-Domain: your-domain.com"
```

---

## Authentication

All API requests require two HTTP headers for authentication:

### Required Headers

| Header | Description | Example |
|--------|-------------|---------|
| `X-API-Key` | API secret key configured in system settings | `X-API-Key: abc123def456...` |
| `X-Domain` | Domain UUID or domain name | `X-Domain: your-domain.com` or `X-Domain: 550e8400-e29b-41d4-a716-446655440000` |

### Authentication Flow

1. FusionPBX validates the `X-API-Key` against the configured secret key
2. The provided `X-Domain` is resolved to a domain UUID
3. All database queries are filtered by the authenticated domain
4. Request fails if either header is missing or invalid

### Error Responses

**Missing API Key:**
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Invalid API key"
  }
}
```

**Missing Domain Header:**
```json
{
  "success": false,
  "error": {
    "code": "MISSING_DOMAIN",
    "message": "X-Domain header is required"
  }
}
```

**Invalid Domain:**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_DOMAIN",
    "message": "Domain not found"
  }
}
```

---

## Response Format

### Success Response

All successful API responses follow this standard format:

```json
{
  "success": true,
  "data": {
    "extension_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "extension": "101",
    "enabled": "true"
  },
  "message": "Extension created successfully",
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 150,
    "total_pages": 3
  }
}
```

**Response Fields:**

| Field | Type | Always Present | Description |
|-------|------|---|---|
| `success` | boolean | Yes | Always `true` for successful requests |
| `data` | object/array | For GET/POST/PUT | Requested resource(s) |
| `message` | string | Optional | Human-readable success message |
| `pagination` | object | For list endpoints | Pagination metadata |

### Error Response

All error responses follow this standard format:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Extension number is required",
    "field": "extension"
  }
}
```

**Error Fields:**

| Field | Type | Description |
|-------|------|---|
| `success` | boolean | Always `false` for error responses |
| `error.code` | string | Machine-readable error code |
| `error.message` | string | Human-readable error description |
| `error.field` | string | Optional - field that caused the error |

---

## Pagination

All list endpoints (`/list.php`) support pagination using query parameters.

### Query Parameters

| Parameter | Type | Default | Maximum | Description |
|-----------|------|---------|---------|---|
| `page` | integer | 1 | N/A | Page number (1-indexed) |
| `per_page` | integer | 50 | 100 | Records per page |

### Pagination Response

All list endpoints return pagination metadata in the response:

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 125,
    "total_pages": 3
  }
}
```

**Pagination Fields:**

| Field | Type | Description |
|-------|------|---|
| `page` | integer | Current page number |
| `per_page` | integer | Records returned per page |
| `total` | integer | Total records available |
| `total_pages` | integer | Total number of pages |

### Example: Get Page 2

```bash
curl -X GET "https://your-pbx.example.com/api/v1/extensions/list.php?page=2&per_page=25" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: your-domain.com"
```

---

## Error Handling

### HTTP Status Codes

| Code | Name | Situation |
|------|------|-----------|
| 200 | OK | Successful GET, PUT request |
| 201 | Created | Successful POST request |
| 400 | Bad Request | Validation error, missing required field |
| 401 | Unauthorized | Invalid or missing API key |
| 403 | Forbidden | Operation not allowed (e.g., deleting superadmin) |
| 404 | Not Found | Resource does not exist |
| 405 | Method Not Allowed | HTTP method not supported for endpoint |
| 409 | Conflict | Resource already exists (DUPLICATE_ERROR) |
| 500 | Internal Server Error | Server configuration error |
| 501 | Not Implemented | Endpoint handler not yet implemented |

### Error Codes Reference

| Error Code | HTTP Code | Description |
|---|---|---|
| `UNAUTHORIZED` | 401 | Invalid API key provided |
| `MISSING_DOMAIN` | 400 | X-Domain header is required |
| `INVALID_DOMAIN` | 400 | Domain UUID or name not found |
| `VALIDATION_ERROR` | 400 | Required field missing or invalid format |
| `DUPLICATE_ERROR` | 409 | Resource already exists (extension, user, gateway, device, etc.) |
| `NOT_FOUND` | 404 | Resource UUID does not exist |
| `FORBIDDEN` | 403 | Operation not allowed (e.g., superadmin deletion) |
| `METHOD_NOT_ALLOWED` | 405 | HTTP method not supported for this endpoint |
| `NOT_IMPLEMENTED` | 501 | Endpoint handler file does not exist |
| `CONFIG_ERROR` | 500 | API key not configured in system settings |
| `MISSING_UUID` | 400 | UUID required in URL path but not provided |

### Example: Handling Errors

```bash
# Response with validation error
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Extension number is required",
    "field": "extension"
  }
}

# Response with duplicate error
{
  "success": false,
  "error": {
    "code": "DUPLICATE_ERROR",
    "message": "Extension already exists",
    "field": "extension"
  }
}
```

---

## Endpoints

### 1. Extensions (`/extensions/`)

Extensions are SIP endpoints that can make and receive calls. Each extension has a unique number, password, and optional voicemail configuration.

#### List Extensions

**Endpoint:** `GET /extensions/list.php`

**Description:** Retrieve a paginated list of extensions in the domain.

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|---|
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 50 | Records per page (max 100) |

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `extension_uuid` | string | Unique extension identifier (UUID) |
| `extension` | string | Extension number (e.g., "101") |
| `number_alias` | string | Alternative extension number |
| `effective_caller_id_name` | string | Display name for outgoing calls |
| `effective_caller_id_number` | string | Number shown as caller ID |
| `outbound_caller_id_name` | string | Outbound call display name |
| `outbound_caller_id_number` | string | Outbound call number |
| `user_context` | string | Domain context (usually domain name) |
| `enabled` | string | "true" or "false" |
| `description` | string | Extension description/notes |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/extensions/list.php?page=1&per_page=20" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "extension_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "extension": "101",
      "number_alias": "1001",
      "effective_caller_id_name": "John Doe",
      "effective_caller_id_number": "101",
      "outbound_caller_id_name": "Main Office",
      "outbound_caller_id_number": "5551234567",
      "user_context": "example.com",
      "enabled": "true",
      "description": "Reception desk"
    },
    {
      "extension_uuid": "660e8400-e29b-41d4-a716-446655440001",
      "extension": "102",
      "number_alias": "1002",
      "effective_caller_id_name": "Jane Smith",
      "effective_caller_id_number": "102",
      "outbound_caller_id_name": "Main Office",
      "outbound_caller_id_number": "5551234567",
      "user_context": "example.com",
      "enabled": "true",
      "description": "Sales department"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 42,
    "total_pages": 3
  }
}
```

#### Get Extension

**Endpoint:** `GET /extensions/get.php?uuid={extension_uuid}`

**Description:** Retrieve a single extension with all details including voicemail and linked user information.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | Extension UUID (path parameter) |

**Response Fields:**

All fields from list endpoint, plus:

| Field | Type | Description |
|-------|------|---|
| `password` | string | Extension SIP password |
| `max_registrations` | string | Maximum simultaneous registrations |
| `limit_max` | string | Maximum call limit |
| `limit_destination` | string | Destination for limit reached |
| `directory_first_name` | string | First name in directory |
| `directory_last_name` | string | Last name in directory |
| `directory_visible` | string | "true" if visible in directory |
| `directory_exten_visible` | string | Show extension in directory |
| `voicemail` | object | Voicemail configuration (if exists) |
| `user` | object | Linked user information (if exists) |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/extensions/get.php?uuid=550e8400-e29b-41d4-a716-446655440000" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "extension_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "extension": "101",
    "password": "a1b2c3d4e5f6g7h8",
    "effective_caller_id_name": "John Doe",
    "effective_caller_id_number": "101",
    "max_registrations": "1",
    "limit_max": "5",
    "enabled": "true",
    "voicemail": {
      "voicemail_uuid": "770e8400-e29b-41d4-a716-446655440002",
      "voicemail_id": "101",
      "voicemail_password": "123456",
      "voicemail_enabled": "true"
    },
    "user": {
      "user_uuid": "880e8400-e29b-41d4-a716-446655440003",
      "username": "jdoe",
      "full_name": "John Doe"
    }
  }
}
```

#### Create Extension

**Endpoint:** `POST /extensions/create.php`

**Description:** Create a new extension with optional voicemail and user link.

**Required Fields:**

| Field | Type | Description |
|-------|------|---|
| `extension` | string | Extension number (e.g., "101") |

**Optional Fields:**

| Field | Type | Default | Description |
|-------|------|---------|---|
| `password` | string | auto-generated | SIP password (16 hex chars if not provided) |
| `effective_caller_id_name` | string | extension number | Display name for calls |
| `effective_caller_id_number` | string | extension number | Caller ID number |
| `outbound_caller_id_name` | string | empty | Outbound call display name |
| `outbound_caller_id_number` | string | empty | Outbound call number |
| `emergency_caller_id_name` | string | empty | Emergency call display name |
| `emergency_caller_id_number` | string | empty | Emergency call number |
| `directory_first_name` | string | empty | First name in directory |
| `directory_last_name` | string | empty | Last name in directory |
| `directory_visible` | string | "true" | Visible in directory |
| `max_registrations` | string | "1" | Simultaneous device registrations |
| `limit_max` | string | "5" | Maximum concurrent calls |
| `limit_destination` | string | "error/user_busy" | Action when limit reached |
| `enabled` | string | "true" | Enable/disable extension |
| `description` | string | empty | Extension description |
| `user_uuid` | string | empty | Link to user UUID |
| `voicemail_enabled` | string | "false" | Enable voicemail |
| `voicemail_password` | string | auto-generated | Voicemail PIN (6 digits if not provided) |
| `voicemail_mail_to` | string | empty | Email for voicemail notifications |

**Actions Performed:**

- Creates extension in database
- Generates FreeSWITCH XML configuration
- Creates voicemail if `voicemail_enabled` is "true"
- Creates voicemail storage directory
- Clears SIP directory cache
- Links extension to user if `user_uuid` provided

**Example Request:**

```bash
curl -X POST "https://pbx.example.com/api/v1/extensions/create.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "extension": "103",
    "effective_caller_id_name": "Sales Team",
    "effective_caller_id_number": "103",
    "outbound_caller_id_name": "Main Office",
    "outbound_caller_id_number": "5551234567",
    "directory_first_name": "Sales",
    "directory_last_name": "Team",
    "voicemail_enabled": "true",
    "voicemail_password": "1234",
    "voicemail_mail_to": "sales@example.com",
    "description": "Sales department extension"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "extension_uuid": "990e8400-e29b-41d4-a716-446655440004"
  },
  "message": "Extension created successfully"
}
```

#### Update Extension

**Endpoint:** `PUT /extensions/update.php?uuid={extension_uuid}`

**Description:** Update an existing extension. All fields are optional.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | Extension UUID (path parameter) |

**Request Body:**

Send JSON with any fields to update (same fields as create endpoint).

**Example Request:**

```bash
curl -X PUT "https://pbx.example.com/api/v1/extensions/update.php?uuid=550e8400-e29b-41d4-a716-446655440000" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "effective_caller_id_name": "John Doe Updated",
    "enabled": "true"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "message": "Extension updated successfully"
}
```

#### Delete Extension

**Endpoint:** `DELETE /extensions/delete.php?uuid={extension_uuid}`

**Description:** Delete an extension and its associated voicemail.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | Extension UUID (path parameter) |

**Actions Performed:**

- Deletes extension from database
- Deletes associated voicemail
- Regenerates FreeSWITCH XML
- Clears SIP directory cache

**Example Request:**

```bash
curl -X DELETE "https://pbx.example.com/api/v1/extensions/delete.php?uuid=550e8400-e29b-41d4-a716-446655440000" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "message": "Extension deleted successfully"
}
```

---

### 2. Users (`/users/`)

Users are administrative accounts that can log into FusionPBX and manage system settings. Users can be assigned to groups and linked to extensions.

#### List Users

**Endpoint:** `GET /users/list.php`

**Description:** Retrieve a paginated list of users. Passwords are never returned.

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|---|
| `page` | integer | No | 1 | Page number |
| `per_page` | integer | No | 50 | Records per page (max 100) |

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `user_uuid` | string | Unique user identifier (UUID) |
| `username` | string | Login username |
| `user_email` | string | User email address |
| `user_enabled` | string | "true" or "false" |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/users/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "user_uuid": "880e8400-e29b-41d4-a716-446655440003",
      "username": "jdoe",
      "user_email": "john.doe@example.com",
      "user_enabled": "true"
    },
    {
      "user_uuid": "990e8400-e29b-41d4-a716-446655440005",
      "username": "jsmith",
      "user_email": "jane.smith@example.com",
      "user_enabled": "true"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 15,
    "total_pages": 1
  }
}
```

#### Get User

**Endpoint:** `GET /users/get.php?uuid={user_uuid}`

**Description:** Retrieve a single user with groups and contact information.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | User UUID (path parameter) |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/users/get.php?uuid=880e8400-e29b-41d4-a716-446655440003" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "user_uuid": "880e8400-e29b-41d4-a716-446655440003",
    "username": "jdoe",
    "user_email": "john.doe@example.com",
    "user_enabled": "true",
    "user_language": "en",
    "user_time_zone": "America/New_York",
    "groups": [
      {
        "group_name": "managers",
        "group_description": "Manager group"
      }
    ]
  }
}
```

#### Create User

**Endpoint:** `POST /users/create.php`

**Description:** Create a new user account with optional groups and contact information.

**Required Fields:**

| Field | Type | Description |
|-------|------|---|
| `username` | string | Login username (must be unique) |
| `password` | string | Login password |

**Optional Fields:**

| Field | Type | Description |
|-------|------|---|
| `user_email` | string | Email address |
| `user_enabled` | string | "true" or "false" (default: "true") |
| `user_status` | string | Status (e.g., "active") |
| `user_language` | string | Language code (e.g., "en") |
| `user_time_zone` | string | Timezone (e.g., "America/New_York") |
| `api_key` | string | API key for user |
| `groups` | array | List of group names to add user to |
| `contact_name_given` | string | First name |
| `contact_name_family` | string | Last name |
| `contact_organization` | string | Organization/company |
| `contact_email` | string | Contact email |
| `contact_url` | string | Website URL |
| `contact_nickname` | string | Nickname |

**Actions Performed:**

- Creates user account with hashed password
- Creates contact record if name fields provided
- Links user to groups if specified
- Links contact to user

**Example Request:**

```bash
curl -X POST "https://pbx.example.com/api/v1/users/create.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "msmith",
    "password": "SecurePassword123!",
    "user_email": "m.smith@example.com",
    "user_enabled": "true",
    "user_language": "en",
    "user_time_zone": "America/Chicago",
    "contact_name_given": "Mary",
    "contact_name_family": "Smith",
    "contact_organization": "Accounting",
    "contact_email": "m.smith@example.com",
    "groups": ["managers", "accountants"]
  }'
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "user_uuid": "aa0e8400-e29b-41d4-a716-446655440006"
  },
  "message": "User created successfully"
}
```

#### Update User

**Endpoint:** `PUT /users/update.php?uuid={user_uuid}`

**Description:** Update an existing user. All fields are optional.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | User UUID (path parameter) |

**Example Request:**

```bash
curl -X PUT "https://pbx.example.com/api/v1/users/update.php?uuid=880e8400-e29b-41d4-a716-446655440003" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "user_email": "john.new@example.com",
    "user_enabled": "true"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "message": "User updated successfully"
}
```

#### Delete User

**Endpoint:** `DELETE /users/delete.php?uuid={user_uuid}`

**Description:** Delete a user account.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | User UUID (path parameter) |

**Restrictions:**

- Superadmin users cannot be deleted via API

**Example Request:**

```bash
curl -X DELETE "https://pbx.example.com/api/v1/users/delete.php?uuid=880e8400-e29b-41d4-a716-446655440003" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

### 3. Gateways (`/gateways/`)

Gateways are SIP trunks for connecting to external VoIP providers or other PBX systems.

#### List Gateways

**Endpoint:** `GET /gateways/list.php`

**Description:** Retrieve a paginated list of gateways.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `gateway_uuid` | string | Unique gateway identifier (UUID) |
| `gateway` | string | Gateway name |
| `username` | string | SIP username for registration |
| `proxy` | string | SIP proxy address |
| `register` | string | "true" or "false" - whether to register |
| `profile` | string | FreeSWITCH profile (usually "external") |
| `enabled` | string | "true" or "false" |
| `description` | string | Gateway description |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/gateways/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "gateway_uuid": "bb0e8400-e29b-41d4-a716-446655440007",
      "gateway": "Vonage",
      "username": "myaccount",
      "proxy": "sip.vonage.com",
      "register": "true",
      "profile": "external",
      "enabled": "true",
      "description": "Vonage SIP trunk"
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

#### Get Gateway

**Endpoint:** `GET /gateways/get.php?uuid={gateway_uuid}`

**Description:** Retrieve a single gateway with FreeSWITCH registration status.

**Response Fields:**

All fields from list endpoint, plus:

| Field | Type | Description |
|-------|------|---|
| `registration_status` | string | "running", "stopped", "error", or null |
| `registration_state` | string | "REGED", "UNREGED", etc. |
| `password` | string | SIP password |
| `auth_username` | string | Authentication username |
| `realm` | string | SIP realm |
| `from_user` | string | From user |
| `from_domain` | string | From domain |
| `expire_seconds` | string | Registration expiration (default 800) |
| `retry_seconds` | string | Retry interval (default 30) |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/gateways/get.php?uuid=bb0e8400-e29b-41d4-a716-446655440007" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "gateway_uuid": "bb0e8400-e29b-41d4-a716-446655440007",
    "gateway": "Vonage",
    "username": "myaccount",
    "password": "secret123",
    "proxy": "sip.vonage.com",
    "register": "true",
    "profile": "external",
    "enabled": "true",
    "registration_status": "running",
    "registration_state": "REGED"
  }
}
```

#### Create Gateway

**Endpoint:** `POST /gateways/create.php`

**Description:** Create a new SIP gateway with FreeSWITCH integration.

**Required Fields:**

| Field | Type | Description |
|-------|------|---|
| `gateway` | string | Gateway name |
| `proxy` | string | SIP proxy address |

**Optional Fields:**

| Field | Type | Default | Description |
|-------|------|---------|---|
| `username` | string | empty | SIP username |
| `password` | string | empty | SIP password |
| `auth_username` | string | empty | Authentication username |
| `realm` | string | empty | SIP realm |
| `from_user` | string | empty | From user |
| `from_domain` | string | empty | From domain |
| `register_proxy` | string | empty | Proxy for registration |
| `outbound_proxy` | string | empty | Outbound proxy |
| `expire_seconds` | string | "800" | Registration expiration |
| `register` | string | "false" | Enable registration |
| `register_transport` | string | empty | Transport (udp, tcp, tls) |
| `retry_seconds` | string | "30" | Retry interval |
| `extension` | string | empty | Extension context |
| `ping` | string | empty | Ping interval |
| `context` | string | empty | Dialplan context |
| `profile` | string | "external" | FreeSWITCH profile |
| `enabled` | string | "true" | Enable gateway |
| `description` | string | empty | Gateway description |
| `hostname` | string | empty | Hostname |
| `distinct_to` | string | empty | Distinct to header |
| `contact_params` | string | empty | Contact parameters |
| `caller_id_in_from` | string | empty | Caller ID in from header |
| `supress_cng` | string | empty | Suppress CNG |
| `sip_cid_type` | string | empty | SIP CID type |
| `extension_in_contact` | string | empty | Extension in contact |
| `codec_prefs` | string | empty | Codec preferences |

**Actions Performed:**

- Creates gateway in database
- Generates FreeSWITCH XML configuration
- Clears sofia config cache
- Rescans FreeSWITCH profile
- Triggers registration if enabled

**Example Request:**

```bash
curl -X POST "https://pbx.example.com/api/v1/gateways/create.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "gateway": "Twilio",
    "proxy": "sip.twilio.com",
    "username": "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "password": "auth_token_here",
    "register": "true",
    "expire_seconds": "3600",
    "profile": "external",
    "enabled": "true",
    "description": "Twilio SIP trunk"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "gateway_uuid": "cc0e8400-e29b-41d4-a716-446655440008"
  },
  "message": "Gateway created successfully"
}
```

#### Update Gateway

**Endpoint:** `PUT /gateways/update.php?uuid={gateway_uuid}`

**Description:** Update an existing gateway.

**Actions Performed:**

- Updates gateway configuration
- Regenerates FreeSWITCH XML
- Clears cache
- Rescans profile

**Example Request:**

```bash
curl -X PUT "https://pbx.example.com/api/v1/gateways/update.php?uuid=bb0e8400-e29b-41d4-a716-446655440007" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "enabled": "true"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "message": "Gateway updated successfully"
}
```

#### Delete Gateway

**Endpoint:** `DELETE /gateways/delete.php?uuid={gateway_uuid}`

**Description:** Delete a gateway.

**Actions Performed:**

- Kills gateway in FreeSWITCH
- Removes from database
- Clears cache

**Example Request:**

```bash
curl -X DELETE "https://pbx.example.com/api/v1/gateways/delete.php?uuid=bb0e8400-e29b-41d4-a716-446655440007" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "message": "Gateway deleted successfully"
}
```

---

### 4. Devices (`/devices/`)

Devices are IP phones that can register to extensions. Each device has a MAC address, model information, and device lines.

#### List Devices

**Endpoint:** `GET /devices/list.php`

**Description:** Retrieve a paginated list of devices.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `device_uuid` | string | Unique device identifier (UUID) |
| `device_address` | string | MAC address (normalized) |
| `device_vendor` | string | Device vendor (e.g., "Yealink", "Cisco") |
| `device_model` | string | Device model |
| `device_enabled` | string | "true" or "false" |
| `device_label` | string | Device label/name |
| `device_description` | string | Device description |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/devices/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "device_uuid": "dd0e8400-e29b-41d4-a716-446655440009",
      "device_address": "a0b1c2d3e4f5",
      "device_vendor": "Yealink",
      "device_model": "SIP-T46U",
      "device_enabled": "true",
      "device_label": "Conference Room Phone",
      "device_description": "Yealink T46U in conference room"
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

#### Get Device

**Endpoint:** `GET /devices/get.php?uuid={device_uuid}`

**Description:** Retrieve a single device with device lines and keys.

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/devices/get.php?uuid=dd0e8400-e29b-41d4-a716-446655440009" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "device_uuid": "dd0e8400-e29b-41d4-a716-446655440009",
    "device_address": "a0b1c2d3e4f5",
    "device_vendor": "Yealink",
    "device_model": "SIP-T46U",
    "device_enabled": "true",
    "device_label": "Conference Room Phone",
    "device_lines": [
      {
        "device_line_uuid": "ee0e8400-e29b-41d4-a716-446655440010",
        "line_number": "1",
        "server_address": "example.com",
        "display_name": "Conference Room",
        "user_id": "101",
        "auth_id": "101",
        "label": "Main Line",
        "enabled": "true"
      }
    ]
  }
}
```

#### Create Device

**Endpoint:** `POST /devices/create.php`

**Description:** Create a new IP phone device with optional lines.

**Required Fields:**

| Field | Type | Description |
|-------|------|---|
| `device_address` | string | MAC address (with or without separators) |

**Optional Fields:**

| Field | Type | Description |
|-------|------|---|
| `device_vendor` | string | Vendor name |
| `device_model` | string | Model number |
| `device_label` | string | Device label |
| `device_template` | string | Provisioning template |
| `device_enabled` | string | "true" or "false" (default: "true") |
| `device_description` | string | Description |
| `device_username` | string | Device admin username |
| `device_password` | string | Device admin password |
| `device_location` | string | Physical location |
| `device_serial_number` | string | Serial number |
| `device_firmware_version` | string | Firmware version |
| `device_profile_uuid` | string | Device profile UUID |
| `lines` | array | Array of line objects |

**Line Object Fields:**

| Field | Type | Default | Description |
|-------|------|---------|---|
| `line_number` | string | 1 | Line number on device |
| `server_address` | string | domain_name | SIP server address |
| `display_name` | string | empty | Display name |
| `user_id` | string | empty | Extension number |
| `auth_id` | string | user_id | Authentication ID |
| `password` | string | empty | Line password |
| `label` | string | empty | Line label |
| `enabled` | string | "true" | Enable line |
| `outbound_proxy_primary` | string | empty | Primary proxy |
| `outbound_proxy_secondary` | string | empty | Secondary proxy |

**MAC Address Normalization:**

- Separators (`:`, `-`, `.`) are automatically removed
- Must be 12 hexadecimal characters
- Examples: `a0:b1:c2:d3:e4:f5`, `a0-b1-c2-d3-e4-f5`, `a0b1c2d3e4f5`

**Actions Performed:**

- Creates device with normalized MAC address
- Creates device lines if provided
- Generates provisioning files
- Validates MAC address format

**Example Request:**

```bash
curl -X POST "https://pbx.example.com/api/v1/devices/create.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "device_address": "a0:b1:c2:d3:e4:f5",
    "device_vendor": "Yealink",
    "device_model": "SIP-T46U",
    "device_label": "Front Desk Phone",
    "device_enabled": "true",
    "device_description": "Yealink phone for front desk",
    "lines": [
      {
        "line_number": "1",
        "display_name": "Front Desk",
        "user_id": "101",
        "password": "linepassword123"
      }
    ]
  }'
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "device_uuid": "ff0e8400-e29b-41d4-a716-446655440011"
  },
  "message": "Device created successfully"
}
```

#### Update Device

**Endpoint:** `PUT /devices/update.php?uuid={device_uuid}`

**Description:** Update an existing device.

**Example Request:**

```bash
curl -X PUT "https://pbx.example.com/api/v1/devices/update.php?uuid=dd0e8400-e29b-41d4-a716-446655440009" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "device_label": "Updated Label",
    "device_enabled": "true"
  }'
```

**Example Response:**

```json
{
  "success": true,
  "message": "Device updated successfully"
}
```

#### Delete Device

**Endpoint:** `DELETE /devices/delete.php?uuid={device_uuid}`

**Description:** Delete a device.

**Example Request:**

```bash
curl -X DELETE "https://pbx.example.com/api/v1/devices/delete.php?uuid=dd0e8400-e29b-41d4-a716-446655440009" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "message": "Device deleted successfully"
}
```

---

### 5. Voicemails (`/voicemails/`)

Voicemail boxes for extensions with message storage and optional email delivery.

#### List Voicemails

**Endpoint:** `GET /voicemails/list.php`

**Description:** Retrieve a paginated list of voicemails with message counts.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `voicemail_uuid` | string | Unique voicemail identifier |
| `voicemail_id` | string | Voicemail ID (usually extension number) |
| `voicemail_enabled` | string | "true" or "false" |
| `new_message_count` | integer | Count of unread messages |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/voicemails/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Get Voicemail

**Endpoint:** `GET /voicemails/get.php?uuid={voicemail_uuid}`

**Description:** Retrieve a single voicemail with options and message counts.

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/voicemails/get.php?uuid=770e8400-e29b-41d4-a716-446655440002" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Get Voicemail Messages

**Endpoint:** `GET /voicemails/messages.php?uuid={voicemail_uuid}`

**Description:** Retrieve voicemail messages.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|---|
| `uuid` | string | Voicemail UUID (path parameter) |
| `status` | string | Filter by status: "read", "unread" |
| `page` | integer | Page number |
| `per_page` | integer | Records per page |

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `voicemail_message_uuid` | string | Message UUID |
| `created_epoch` | integer | Unix timestamp |
| `caller_id_name` | string | Caller name |
| `caller_id_number` | string | Caller number |
| `message_length` | integer | Duration in seconds |
| `message_status` | string | "new", "read", "saved" |
| `message_priority` | string | "normal", "urgent" |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/voicemails/messages.php?uuid=770e8400-e29b-41d4-a716-446655440002&status=unread" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Create Voicemail

**Endpoint:** `POST /voicemails/create.php`

**Description:** Create a new voicemail.

#### Update Voicemail

**Endpoint:** `PUT /voicemails/update.php?uuid={voicemail_uuid}`

**Description:** Update a voicemail configuration.

#### Delete Voicemail

**Endpoint:** `DELETE /voicemails/delete.php?uuid={voicemail_uuid}`

**Description:** Delete a voicemail and its messages.

---

### 6. Destinations (`/destinations/`)

Destinations define how incoming calls are routed.

#### List Destinations

**Endpoint:** `GET /destinations/list.php`

**Description:** Retrieve a paginated list of destinations.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `destination_uuid` | string | Unique destination identifier |
| `destination_type` | string | "inbound", "outbound", etc. |
| `destination_number` | string | Destination number/pattern |
| `destination_prefix` | string | Prefix added to number |
| `destination_context` | string | Domain context |
| `destination_enabled` | string | "true" or "false" |
| `destination_description` | string | Description |
| `destination_order` | integer | Order priority |
| `dialplan_uuid` | string | Associated dialplan UUID |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/destinations/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Create Destination

**Endpoint:** `POST /destinations/create.php`

**Description:** Create a new destination with associated dialplan.

**Required Fields:**

| Field | Type | Description |
|-------|------|---|
| `destination_number` | string | Destination number |
| `destination_context` | string | Domain context |

**Optional Fields:**

| Field | Type | Default | Description |
|-------|------|---------|---|
| `destination_type` | string | "inbound" | Type of destination |
| `destination_prefix` | string | empty | Prefix for number |
| `destination_enabled` | string | "true" | Enable/disable |
| `destination_description` | string | empty | Description |
| `destination_order` | string | "100" | Priority order |
| `dialplan_name` | string | formatted number | Dialplan name |
| `destination_actions` | array | empty | Actions to perform |

**Destination Actions:**

Each action object contains:

| Field | Type | Description |
|-------|------|---|
| `destination_app` | string | Application (e.g., "transfer", "voicemail", "playback") |
| `destination_data` | string | Application data |

**Actions Performed:**

- Creates destination record
- Creates associated dialplan
- Generates FreeSWITCH XML configuration
- Clears dialplan cache
- Validates destination actions

**Example Request:**

```bash
curl -X POST "https://pbx.example.com/api/v1/destinations/create.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com" \
  -H "Content-Type: application/json" \
  -d '{
    "destination_number": "100",
    "destination_context": "example.com",
    "destination_type": "inbound",
    "destination_enabled": "true",
    "destination_description": "Main reception",
    "dialplan_name": "Main Reception",
    "destination_actions": [
      {
        "destination_app": "transfer",
        "destination_data": "101 XML example.com"
      }
    ]
  }'
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "destination_uuid": "gg0e8400-e29b-41d4-a716-446655440012"
  },
  "message": "Destination created successfully"
}
```

---

### 7. Dialplans (`/dialplans/`)

Dialplans define call routing logic.

#### List Dialplans

**Endpoint:** `GET /dialplans/list.php`

**Description:** Retrieve a paginated list of dialplans.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|---|
| `app_uuid` | string | Filter by app UUID |
| `page` | integer | Page number |
| `per_page` | integer | Records per page |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/dialplans/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Get Dialplan

**Endpoint:** `GET /dialplans/get.php?uuid={dialplan_uuid}`

**Description:** Retrieve a single dialplan with details (conditions/actions).

#### Create Dialplan

**Endpoint:** `POST /dialplans/create.php`

**Description:** Create a new dialplan.

**Required Fields:**

| Field | Type | Description |
|-------|------|---|
| `dialplan_name` | string | Dialplan name |

**Optional Fields:**

| Field | Type | Description |
|-------|------|---|
| `app_uuid` | string | Associated application UUID |
| `dialplan_number` | string | Dialplan number |
| `dialplan_context` | string | Domain context |
| `dialplan_continue` | string | "true" or "false" |
| `dialplan_order` | string | Order priority |
| `dialplan_enabled` | string | "true" or "false" |
| `dialplan_description` | string | Description |
| `details` | array | Array of condition/action details |

#### Update Dialplan

**Endpoint:** `PUT /dialplans/update.php?uuid={dialplan_uuid}`

**Description:** Update a dialplan.

#### Delete Dialplan

**Endpoint:** `DELETE /dialplans/delete.php?uuid={dialplan_uuid}`

**Description:** Delete a dialplan.

---

### 8. Inbound Routes (`/inbound-routes/`)

Inbound routes are dialplans filtered by app UUID `c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4`.

#### List Inbound Routes

**Endpoint:** `GET /inbound-routes/list.php`

**Description:** Retrieve a paginated list of inbound routes.

#### Get Inbound Route

**Endpoint:** `GET /inbound-routes/get.php?uuid={dialplan_uuid}`

**Description:** Retrieve a single inbound route.

#### Create Inbound Route

**Endpoint:** `POST /inbound-routes/create.php`

**Description:** Create a new inbound route.

#### Update Inbound Route

**Endpoint:** `PUT /inbound-routes/update.php?uuid={dialplan_uuid}`

**Description:** Update an inbound route.

#### Delete Inbound Route

**Endpoint:** `DELETE /inbound-routes/delete.php?uuid={dialplan_uuid}`

**Description:** Delete an inbound route.

---

### 9. Outbound Routes (`/outbound-routes/`)

Outbound routes are dialplans filtered by app UUID `8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3`.

#### List Outbound Routes

**Endpoint:** `GET /outbound-routes/list.php`

**Description:** Retrieve a paginated list of outbound routes.

#### Get Outbound Route

**Endpoint:** `GET /outbound-routes/get.php?uuid={dialplan_uuid}`

**Description:** Retrieve a single outbound route.

#### Create Outbound Route

**Endpoint:** `POST /outbound-routes/create.php`

**Description:** Create a new outbound route.

#### Update Outbound Route

**Endpoint:** `PUT /outbound-routes/update.php?uuid={dialplan_uuid}`

**Description:** Update an outbound route.

#### Delete Outbound Route

**Endpoint:** `DELETE /outbound-routes/delete.php?uuid={dialplan_uuid}`

**Description:** Delete an outbound route.

---

### 10. Time Conditions (`/time-conditions/`)

Time-based call routing conditions (filtered by app UUID `4b821450-926b-175a-af93-a03c441f8c30`).

#### List Time Conditions

**Endpoint:** `GET /time-conditions/list.php`

#### Get Time Condition

**Endpoint:** `GET /time-conditions/get.php?uuid={dialplan_uuid}`

#### Create Time Condition

**Endpoint:** `POST /time-conditions/create.php`

#### Update Time Condition

**Endpoint:** `PUT /time-conditions/update.php?uuid={dialplan_uuid}`

#### Delete Time Condition

**Endpoint:** `DELETE /time-conditions/delete.php?uuid={dialplan_uuid}`

---

### 11. CDR - Call Detail Records (`/cdr/`)

Historical call records with filtering and recording information.

#### List CDR Records

**Endpoint:** `GET /cdr/list.php`

**Description:** Retrieve a paginated list of call detail records.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|---|
| `page` | integer | Page number |
| `per_page` | integer | Records per page (default 50, max 100) |
| `start_date` | string | Start date (YYYY-MM-DD) |
| `end_date` | string | End date (YYYY-MM-DD) |
| `direction` | string | Filter: "inbound", "outbound", "local" |
| `extension` | string | Extension number filter |
| `caller_id` | string | Caller ID filter (partial match) |

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `xml_cdr_uuid` | string | CDR record UUID |
| `direction` | string | "inbound", "outbound", "local" |
| `caller_id_name` | string | Caller name |
| `caller_id_number` | string | Caller number |
| `caller_destination` | string | Caller destination |
| `destination_number` | string | Destination number |
| `start_stamp` | string | Call start time |
| `answer_stamp` | string | Call answer time |
| `end_stamp` | string | Call end time |
| `duration` | integer | Total duration (seconds) |
| `billsec` | integer | Billable seconds |
| `hangup_cause` | string | Disconnect reason |
| `recording_file` | string | Path to call recording |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/cdr/list.php?start_date=2026-02-01&end_date=2026-02-04&direction=inbound" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "xml_cdr_uuid": "hh0e8400-e29b-41d4-a716-446655440013",
      "direction": "inbound",
      "caller_id_name": "John Smith",
      "caller_id_number": "5551234567",
      "destination_number": "101",
      "start_stamp": "2026-02-04 10:30:00",
      "answer_stamp": "2026-02-04 10:30:05",
      "end_stamp": "2026-02-04 10:35:15",
      "duration": 315,
      "billsec": 310,
      "hangup_cause": "NORMAL_CLEARING",
      "recording_file": "/var/lib/freeswitch/recordings/2026/02/04/call_12345.wav"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 256,
    "total_pages": 6
  }
}
```

#### Get CDR Record

**Endpoint:** `GET /cdr/get.php?uuid={xml_cdr_uuid}`

**Description:** Retrieve a single CDR record with all details.

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/cdr/get.php?uuid=hh0e8400-e29b-41d4-a716-446655440013" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

---

### 12. Dashboard (`/dashboard/`)

Real-time statistics and status information.

#### Get Statistics

**Endpoint:** `GET /dashboard/stats.php`

**Description:** Get domain statistics including counts and new voicemail messages.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `extensions.total` | integer | Total extensions |
| `users.total` | integer | Enabled users |
| `gateways.total` | integer | Enabled gateways |
| `voicemails.new_messages` | integer | Unread voicemail messages |
| `calls.today_total` | integer | Total calls today |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/dashboard/stats.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "extensions": {
      "total": 42
    },
    "users": {
      "total": 15
    },
    "gateways": {
      "total": 3
    },
    "voicemails": {
      "new_messages": 7
    },
    "calls": {
      "today_total": 234
    }
  }
}
```

#### Get SIP Registrations

**Endpoint:** `GET /dashboard/registrations.php`

**Description:** Get live SIP registrations from FreeSWITCH.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `user` | string | Extension/user ID |
| `domain` | string | Domain name |
| `contact` | string | SIP contact URI |
| `agent` | string | User agent string |
| `status` | string | Registration status |
| `profile` | string | SIP profile |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/dashboard/registrations.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "user": "101",
      "domain": "example.com",
      "contact": "sip:101@192.168.1.100:5060",
      "agent": "Yealink SIP-T46U",
      "status": "Registered",
      "profile": "internal"
    },
    {
      "user": "102",
      "domain": "example.com",
      "contact": "sip:102@192.168.1.101:5060",
      "agent": "Cisco UCCX",
      "status": "Registered",
      "profile": "internal"
    }
  ]
}
```

#### Get Active Calls

**Endpoint:** `GET /dashboard/active-calls.php`

**Description:** Get live active calls from FreeSWITCH.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `uuid` | string | Call UUID |
| `direction` | string | "inbound" or "outbound" |
| `caller_id_name` | string | Caller name |
| `caller_id_number` | string | Caller number |
| `destination` | string | Destination number |
| `state` | string | Call state |
| `created` | string | Call start time |
| `created_epoch` | integer | Unix timestamp |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/dashboard/active-calls.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "uuid": "ii0e8400-e29b-41d4-a716-446655440014",
      "direction": "inbound",
      "caller_id_name": "Customer Support",
      "caller_id_number": "8005551234",
      "destination": "101",
      "state": "CS_EXECUTE",
      "created": "2026-02-04 14:23:45",
      "created_epoch": 1707057825
    }
  ]
}
```

---

### 13. Domains (`/domains/`)

Domain management and configuration.

#### List Domains

**Endpoint:** `GET /domains/list.php`

**Description:** Retrieve a list of all domains in the system.

**Response Fields:**

| Field | Type | Description |
|-------|------|---|
| `domain_uuid` | string | Domain UUID |
| `domain_name` | string | Domain name |
| `domain_enabled` | string | "true" or "false" |
| `domain_description` | string | Description |

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/domains/list.php" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Get Domain

**Endpoint:** `GET /domains/get.php?uuid={domain_uuid}`

**Description:** Retrieve domain details with resource counts.

**Example Request:**

```bash
curl -X GET "https://pbx.example.com/api/v1/domains/get.php?uuid=550e8400-e29b-41d4-a716-446655440000" \
  -H "X-API-Key: your-api-key" \
  -H "X-Domain: example.com"
```

#### Get Domain Settings

**Endpoint:** `GET /domains/settings.php?uuid={domain_uuid}`

**Description:** Retrieve domain settings.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|---|
| `uuid` | string | Yes | Domain UUID |

#### Update Domain Settings

**Endpoint:** `PUT /domains/settings.php?uuid={domain_uuid}`

**Description:** Update domain settings.

**Request Body:**

```json
{
  "settings": [
    {
      "category": "voicemail",
      "subcategory": "enabled",
      "name": "voicemail_enabled",
      "value": "true",
      "enabled": "true"
    }
  ]
}
```

---

## Testing Examples

### Example 1: Create an Extension with Voicemail

```bash
#!/bin/bash

API_KEY="your-api-secret-key"
DOMAIN="example.com"
BASE_URL="https://pbx.example.com/api/v1"

# Create extension with voicemail
curl -X POST "${BASE_URL}/extensions/create.php" \
  -H "X-API-Key: ${API_KEY}" \
  -H "X-Domain: ${DOMAIN}" \
  -H "Content-Type: application/json" \
  -d '{
    "extension": "104",
    "effective_caller_id_name": "Sales Line",
    "effective_caller_id_number": "104",
    "outbound_caller_id_name": "Main Office",
    "outbound_caller_id_number": "5551234567",
    "directory_first_name": "Sales",
    "directory_last_name": "Line",
    "voicemail_enabled": "true",
    "voicemail_password": "1234",
    "voicemail_mail_to": "sales@example.com",
    "description": "Sales department line"
  }' | jq .
```

### Example 2: List Recent Call Records

```bash
#!/bin/bash

API_KEY="your-api-secret-key"
DOMAIN="example.com"
BASE_URL="https://pbx.example.com/api/v1"

# Get calls from today
TODAY=$(date +%Y-%m-%d)
curl -X GET "${BASE_URL}/cdr/list.php?start_date=${TODAY}&direction=inbound" \
  -H "X-API-Key: ${API_KEY}" \
  -H "X-Domain: ${DOMAIN}" | jq .
```

### Example 3: Create a SIP Gateway

```bash
#!/bin/bash

API_KEY="your-api-secret-key"
DOMAIN="example.com"
BASE_URL="https://pbx.example.com/api/v1"

curl -X POST "${BASE_URL}/gateways/create.php" \
  -H "X-API-Key: ${API_KEY}" \
  -H "X-Domain: ${DOMAIN}" \
  -H "Content-Type: application/json" \
  -d '{
    "gateway": "Vonage",
    "proxy": "sip.vonage.com",
    "username": "vonage-account-id",
    "password": "vonage-password",
    "register": "true",
    "expire_seconds": "3600",
    "retry_seconds": "30",
    "profile": "external",
    "enabled": "true",
    "description": "Vonage SIP trunk for outbound calls"
  }' | jq .
```

### Example 4: Error Handling Example

```bash
#!/bin/bash

API_KEY="invalid-key"
DOMAIN="example.com"
BASE_URL="https://pbx.example.com/api/v1"

# This will fail with UNAUTHORIZED error
curl -X GET "${BASE_URL}/extensions/list.php" \
  -H "X-API-Key: ${API_KEY}" \
  -H "X-Domain: ${DOMAIN}"

# Response:
# {
#   "success": false,
#   "error": {
#     "code": "UNAUTHORIZED",
#     "message": "Invalid API key"
#   }
# }
```

### Example 5: Pagination Example

```bash
#!/bin/bash

API_KEY="your-api-secret-key"
DOMAIN="example.com"
BASE_URL="https://pbx.example.com/api/v1"

# Get second page with 25 items per page
curl -X GET "${BASE_URL}/users/list.php?page=2&per_page=25" \
  -H "X-API-Key: ${API_KEY}" \
  -H "X-Domain: ${DOMAIN}" | jq .
```

---

## Implementation Notes

### FreeSWITCH Integration

All create/update/delete operations that affect call routing automatically:

1. **Generate FreeSWITCH XML** - Extensions and gateways generate XML configs
2. **Clear Caches** - SIP directory, dialplan, and configuration caches are cleared
3. **Reload Profiles** - Gateway profiles are rescanned
4. **Update Registrations** - Gateway registrations are refreshed

### Field Value Standards

#### Boolean Fields

Boolean fields in API requests use string values, not JSON booleans:

```json
{
  "enabled": "true",
  "voicemail_enabled": "false",
  "register": "true"
}
```

#### Password Generation

When passwords are not provided:

- **Extensions**: 16 hexadecimal characters
- **Voicemail**: 6 numeric digits
- **Users**: Must be provided explicitly

### UUID Generation

All resource UUIDs are automatically generated if not provided:

- Format: RFC 4122 UUID (e.g., `550e8400-e29b-41d4-a716-446655440000`)
- Generated server-side
- Unique per resource

### MAC Address Normalization

Device MAC addresses are automatically normalized:

- Input formats: `a0:b1:c2:d3:e4:f5`, `a0-b1-c2-d3-e4-f5`, `a0b1c2d3e4f5`
- Stored format: 12 lowercase hexadecimal characters: `a0b1c2d3e4f5`
- Must be 12 characters after normalization

### Domain Context

- All requests must include `X-Domain` header
- Domain can be specified by UUID or domain name
- All database queries are automatically filtered by domain
- Resources are domain-isolated (no cross-domain access)

### Rate Limiting

Currently no rate limiting is implemented. Use reasonable request rates to avoid server overload.

### Error Recovery

When a request fails:

1. Check HTTP status code
2. Read the error code from `error.code`
3. Check the `field` parameter if field validation failed
4. Retry if appropriate for the error type

### Security Considerations

- API key is validated using constant-time comparison (`hash_equals`)
- Passwords are never returned in API responses (user list excludes passwords)
- All database queries use parameterized statements to prevent SQL injection
- Request data is validated before processing

---

## Troubleshooting

### API Key Not Configured

**Error:** `{"success": false, "error": {"code": "CONFIG_ERROR", "message": "API key not configured"}}`

**Solution:** Configure API key in FusionPBX system settings under `Settings > System > API > Secret Key`

### Invalid Domain

**Error:** `{"success": false, "error": {"code": "INVALID_DOMAIN", "message": "Domain not found"}}`

**Solution:**
- Verify domain exists in FusionPBX
- Use correct domain UUID or domain name in `X-Domain` header
- Check domain is enabled

### Duplicate Resource

**Error:** `{"success": false, "error": {"code": "DUPLICATE_ERROR", "message": "Extension already exists"}}`

**Solution:**
- Use unique extension numbers
- Check if resource already exists before creating
- Use PUT to update instead of POST to create

### MAC Address Format

**Error:** `{"success": false, "error": {"code": "VALIDATION_ERROR", "message": "Invalid MAC address format"}}`

**Solution:**
- Ensure MAC address is 12 hexadecimal characters
- Valid formats: `aa:bb:cc:dd:ee:ff`, `aa-bb-cc-dd-ee-ff`, `aabbccddeeff`
- Separators are automatically removed

---

## Support & Feedback

For issues or feature requests:

1. Check the FusionPBX documentation
2. Review error codes and messages carefully
3. Verify all required headers are present
4. Test with curl before integrating into applications

---

**API Version:** 1.0
**Last Updated:** 2026-02-04
**FusionPBX Version:** 5.5.7+
