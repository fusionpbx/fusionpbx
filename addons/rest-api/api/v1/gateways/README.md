# Gateways API Endpoints

## Overview
Complete REST API implementation for FusionPBX Gateway management with FreeSWITCH integration.

## Endpoints

### 1. List Gateways
**GET** `/api/v1/gateways/list.php`

Returns paginated list of gateways for the authenticated domain.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 50, max: 100)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "gateway_uuid": "uuid",
      "gateway": "gateway_name",
      "username": "user",
      "proxy": "sip.provider.com",
      "register": "true",
      "profile": "external",
      "enabled": "true",
      "description": "Description"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 100,
    "total_pages": 2
  }
}
```

### 2. Get Single Gateway
**GET** `/api/v1/gateways/get.php?uuid={gateway_uuid}`

Returns detailed information for a specific gateway including registration status.

**Response:**
```json
{
  "success": true,
  "data": {
    "gateway_uuid": "uuid",
    "gateway": "gateway_name",
    "username": "user",
    "proxy": "sip.provider.com",
    "register": "true",
    "profile": "external",
    "enabled": "true",
    "registration_status": "running",
    "registration_state": "REGED",
    "...": "all gateway fields"
  }
}
```

**Registration Status:**
- `running` - Gateway is active
- `stopped` - Gateway is not running
- `error` - Error retrieving status
- `null` - FreeSWITCH not connected

### 3. Create Gateway
**POST** `/api/v1/gateways/create.php`

Creates a new gateway with FreeSWITCH integration.

**Request Body:**
```json
{
  "gateway": "gateway_name",
  "proxy": "sip.provider.com",
  "username": "user",
  "password": "pass",
  "register": "true",
  "profile": "external",
  "enabled": "true",
  "description": "Optional description"
}
```

**Required Fields:**
- `gateway` - Gateway name
- `proxy` - SIP proxy address

**Optional Fields:**
- `username`, `password`, `auth_username`
- `realm`, `from_user`, `from_domain`
- `register_proxy`, `outbound_proxy`
- `expire_seconds` (default: 800)
- `register` (default: false)
- `register_transport` (udp/tcp/tls)
- `retry_seconds` (default: 30)
- `extension`, `ping`, `context`
- `profile` (default: external)
- `enabled` (default: true)
- `hostname`, `distinct_to`, `contact_params`
- `caller_id_in_from`, `supress_cng`, `sip_cid_type`
- `extension_in_contact`, `ping_min`, `ping_max`
- `contact_in_ping`, `codec_prefs`

**Response:**
```json
{
  "success": true,
  "data": {
    "gateway_uuid": "newly_created_uuid"
  },
  "message": "Gateway created successfully"
}
```

**Actions Performed:**
1. Validates required fields
2. Generates gateway UUID
3. Saves to database
4. Generates gateway XML file
5. Clears sofia configuration cache
6. Rescans FreeSWITCH profile

### 4. Update Gateway
**PUT** `/api/v1/gateways/update.php?uuid={gateway_uuid}`

Updates an existing gateway.

**Request Body:**
```json
{
  "gateway": "new_name",
  "proxy": "new.provider.com",
  "enabled": "false"
}
```

All fields are optional. Only provided fields will be updated.

**Response:**
```json
{
  "success": true,
  "data": {
    "gateway_uuid": "uuid"
  },
  "message": "Gateway updated successfully"
}
```

**Actions Performed:**
1. Validates gateway exists
2. Updates database record
3. Regenerates gateway XML
4. Clears sofia configuration cache
5. Rescans FreeSWITCH profiles (both old and new if profile changed)

### 5. Delete Gateway
**DELETE** `/api/v1/gateways/delete.php?uuid={gateway_uuid}`

Deletes a gateway and removes it from FreeSWITCH.

**Response:**
```json
{
  "success": true,
  "message": "Gateway deleted successfully"
}
```

**Actions Performed:**
1. Validates gateway exists
2. Kills gateway in FreeSWITCH (`sofia profile X killgw uuid`)
3. Deletes from database
4. Regenerates gateway XML (removes deleted gateway file)
5. Clears sofia configuration cache
6. Rescans FreeSWITCH profile

## Authentication

All endpoints require:
- **X-API-Key** header: API secret key
- **X-Domain** header: Domain UUID or domain name

## Error Responses

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "field": "field_name"
  }
}
```

**Common Error Codes:**
- `UNAUTHORIZED` (401) - Invalid API key
- `MISSING_DOMAIN` (400) - X-Domain header missing
- `INVALID_DOMAIN` (400) - Domain not found
- `VALIDATION_ERROR` (400) - Required field missing
- `NOT_FOUND` (404) - Gateway not found
- `MISSING_UUID` (400) - UUID required but not provided

## FreeSWITCH Integration

### XML Generation
The `save_gateway_xml()` function generates XML configuration files in the FreeSWITCH sip_profiles directory:
- Location: `/path/to/sip_profiles/{profile}/v_{gateway_uuid}.xml`
- Format: Standard FreeSWITCH gateway XML
- Only enabled gateways generate XML files

### Event Socket Commands
- `sofia profile {profile} rescan` - Reload gateway configuration
- `sofia profile {profile} killgw {uuid}` - Stop gateway
- `sofia profile {profile} startgw {uuid}` - Start gateway
- `sofia xmlstatus gateway {uuid}` - Get gateway status

### Cache Management
Clears `{hostname}:configuration:sofia.conf` cache after all modifications to ensure FreeSWITCH reloads configuration.

## Database Schema
Uses `v_gateways` table with app_uuid: `a2124650-6c38-c96a-0767-12ababf0a8d5`

## Testing

### Create Gateway
```bash
curl -X POST http://fusionpbx.local/api/v1/gateways/create.php \
  -H "X-API-Key: your_api_key" \
  -H "X-Domain: domain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "gateway": "test_gateway",
    "proxy": "sip.example.com",
    "username": "testuser",
    "password": "testpass",
    "register": "true"
  }'
```

### List Gateways
```bash
curl -X GET "http://fusionpbx.local/api/v1/gateways/list.php?page=1&per_page=10" \
  -H "X-API-Key: your_api_key" \
  -H "X-Domain: domain.com"
```

### Get Gateway
```bash
curl -X GET "http://fusionpbx.local/api/v1/gateways/get.php?uuid=gateway_uuid" \
  -H "X-API-Key: your_api_key" \
  -H "X-Domain: domain.com"
```

### Update Gateway
```bash
curl -X PUT "http://fusionpbx.local/api/v1/gateways/update.php?uuid=gateway_uuid" \
  -H "X-API-Key: your_api_key" \
  -H "X-Domain: domain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "enabled": "false"
  }'
```

### Delete Gateway
```bash
curl -X DELETE "http://fusionpbx.local/api/v1/gateways/delete.php?uuid=gateway_uuid" \
  -H "X-API-Key: your_api_key" \
  -H "X-Domain: domain.com"
```

## Files Created

- `/api/v1/gateways/list.php` - List gateways with pagination
- `/api/v1/gateways/get.php` - Get single gateway with status
- `/api/v1/gateways/create.php` - Create gateway with FreeSWITCH integration
- `/api/v1/gateways/update.php` - Update gateway
- `/api/v1/gateways/delete.php` - Delete gateway with FreeSWITCH cleanup

## Notes

- All create/update/delete operations automatically handle FreeSWITCH integration
- XML files are generated only for enabled gateways
- Profile changes trigger rescan on both old and new profiles
- Gateway UUIDs are used as FreeSWITCH gateway names in XML
- All boolean fields use string values ("true"/"false")
