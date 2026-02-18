# FusionPBX Eloquent REST API Documentation

This document describes the REST API endpoints for FusionPBX Eloquent models.

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Endpoints](#endpoints)
   - [Users](#users-api)
   - [Extensions](#extensions-api)
   - [Groups](#groups-api)
   - [Dashboards](#dashboards-api)
   - [Permissions](#permissions-api)
5. [Error Handling](#error-handling)
6. [Examples](#examples)

## Overview

The FusionPBX Eloquent REST API provides programmatic access to FusionPBX resources using modern HTTP methods and JSON responses.

**Base URL**: `/app/models/api/index.php`

**Supported Methods**:
- `GET` - Retrieve resources
- `POST` - Create new resources
- `PUT` / `PATCH` - Update resources
- `DELETE` - Delete resources

## Authentication

All API requests require authentication via FusionPBX session. Ensure you're logged in before making API calls.

The API checks permissions using FusionPBX's permission system. Each endpoint requires specific permissions.

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Success message",
  "data": {
    "resource": { }
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": "Error message"
}
```

### Validation Error

```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "field_name": "Error message"
  }
}
```

## Endpoints

### Users API

#### List Users
**GET** `/app/models/api/index.php?resource=users`

Query Parameters:
- `enabled` - Filter by enabled status (`true`/`false`)
- `search` - Search username or status
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 30, max: 100)

Permission Required: `user_view`

Example:
```bash
curl "http://fusionpbx/app/models/api/index.php?resource=users&enabled=true&page=1&per_page=10"
```

Response:
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "user_uuid": "...",
        "username": "john",
        "user_enabled": "true",
        "groups": [...]
      }
    ],
    "pagination": {
      "total": 50,
      "per_page": 10,
      "current_page": 1,
      "total_pages": 5
    }
  }
}
```

#### Get Single User
**GET** `/app/models/api/index.php?resource=users&id={uuid}`

Permission Required: `user_view`

Response includes user details, groups, and permissions.

#### Create User
**POST** `/app/models/api/index.php?resource=users`

Permission Required: `user_add`

Request Body:
```json
{
  "username": "newuser",
  "password": "secret123",
  "user_enabled": "true",
  "user_status": "Available",
  "groups": ["group-uuid-1", "group-uuid-2"]
}
```

#### Update User
**PUT** `/app/models/api/index.php?resource=users&id={uuid}`

Permission Required: `user_edit`

Request Body (all fields optional):
```json
{
  "username": "updated_username",
  "user_enabled": "false",
  "password": "newpassword",
  "groups": ["group-uuid-1"]
}
```

#### Delete User
**DELETE** `/app/models/api/index.php?resource=users&id={uuid}`

Permission Required: `user_delete`

#### Get User Permissions
**GET** `/app/models/api/index.php?resource=users&id={uuid}&action=permissions`

Permission Required: `user_view`

Response:
```json
{
  "success": true,
  "data": {
    "permissions": [...],
    "permission_names": ["extension_view", "extension_add", ...],
    "is_admin": true,
    "is_superadmin": false
  }
}
```

#### Get User Settings
**GET** `/app/models/api/index.php?resource=users&id={uuid}&action=settings`

Permission Required: `user_view`

#### Update User Setting
**PUT** `/app/models/api/index.php?resource=users&id={uuid}&action=settings`

Permission Required: `user_edit`

Request Body:
```json
{
  "category": "dashboard",
  "subcategory": "theme",
  "name": "color",
  "value": "blue"
}
```

### Extensions API

#### List Extensions
**GET** `/app/models/api/index.php?resource=extensions`

Query Parameters:
- `enabled` - Filter by enabled status
- `search` - Search extension, name, description
- `page` - Page number
- `per_page` - Results per page

Permission Required: `extension_view`

#### Get Single Extension
**GET** `/app/models/api/index.php?resource=extensions&id={uuid}`

Permission Required: `extension_view`

#### Create Extension
**POST** `/app/models/api/index.php?resource=extensions`

Permission Required: `extension_add`

Request Body:
```json
{
  "extension": "1001",
  "password": "secret",
  "effective_caller_id_name": "John Doe",
  "effective_caller_id_number": "1001",
  "enabled": "true",
  "description": "John's Extension"
}
```

#### Update Extension
**PUT** `/app/models/api/index.php?resource=extensions&id={uuid}`

Permission Required: `extension_edit`

#### Delete Extension
**DELETE** `/app/models/api/index.php?resource=extensions&id={uuid}`

Permission Required: `extension_delete`

### Groups API

#### List Groups
**GET** `/app/models/api/index.php?resource=groups`

Permission Required: `group_view`

Returns groups with users and permissions.

#### Get Single Group
**GET** `/app/models/api/index.php?resource=groups&id={uuid}`

Permission Required: `group_view`

#### Create Group
**POST** `/app/models/api/index.php?resource=groups`

Permission Required: `group_add`

Request Body:
```json
{
  "group_name": "operators",
  "group_level": 50,
  "group_description": "Operators group",
  "permissions": ["perm-uuid-1", "perm-uuid-2"]
}
```

#### Update Group
**PUT** `/app/models/api/index.php?resource=groups&id={uuid}`

Permission Required: `group_edit`

#### Delete Group
**DELETE** `/app/models/api/index.php?resource=groups&id={uuid}`

Permission Required: `group_delete`

Note: Cannot delete protected groups.

#### Get Group Permissions
**GET** `/app/models/api/index.php?resource=groups&id={uuid}&action=permissions`

Permission Required: `group_view`

#### Grant Permission to Group
**POST** `/app/models/api/index.php?resource=groups&id={uuid}&action=permissions`

Permission Required: `group_edit`

Request Body:
```json
{
  "permission_uuid": "perm-uuid-123"
}
```

#### Revoke Permission from Group
**DELETE** `/app/models/api/index.php?resource=groups&id={uuid}&action=permissions&permission_id={perm_uuid}`

Permission Required: `group_edit`

### Dashboards API

#### List Dashboards
**GET** `/app/models/api/index.php?resource=dashboards`

Query Parameters:
- `enabled` - Filter by enabled status

Permission Required: `dashboard_view`

#### Get Single Dashboard
**GET** `/app/models/api/index.php?resource=dashboards&id={uuid}`

Permission Required: `dashboard_view`

#### Create Dashboard
**POST** `/app/models/api/index.php?resource=dashboards`

Permission Required: `dashboard_add`

Request Body:
```json
{
  "dashboard_name": "Main Dashboard",
  "dashboard_enabled": "true",
  "dashboard_description": "Primary dashboard"
}
```

#### Update Dashboard
**PUT** `/app/models/api/index.php?resource=dashboards&id={uuid}`

Permission Required: `dashboard_edit`

#### Delete Dashboard
**DELETE** `/app/models/api/index.php?resource=dashboards&id={uuid}`

Permission Required: `dashboard_delete`

### Permissions API

#### List Permissions
**GET** `/app/models/api/index.php?resource=permissions`

Query Parameters:
- `application` - Filter by application name
- `search` - Search permission name/description
- `group_by` - Group results (`application`)

Permission Required: `permission_view`

Example:
```bash
curl "http://fusionpbx/app/models/api/index.php?resource=permissions&application=extensions"
```

#### Get Single Permission
**GET** `/app/models/api/index.php?resource=permissions&id={uuid}`

Permission Required: `permission_view`

#### Get Permissions by Application
**GET** `/app/models/api/index.php?resource=permissions&id={application_name}&action=by-application`

Permission Required: `permission_view`

## Error Handling

### HTTP Status Codes

- `200 OK` - Successful GET/PUT request
- `201 Created` - Successful POST request
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Permission denied
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

### Common Error Responses

**Unauthorized** (401):
```json
{
  "success": false,
  "error": "Authentication required"
}
```

**Forbidden** (403):
```json
{
  "success": false,
  "error": "Permission required: user_add"
}
```

**Not Found** (404):
```json
{
  "success": false,
  "error": "User not found"
}
```

**Validation Error** (422):
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "username": "The username field is required"
  }
}
```

## Examples

### Using cURL

#### List Users
```bash
curl -X GET \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  "http://fusionpbx/app/models/api/index.php?resource=users&enabled=true"
```

#### Create User
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"username":"testuser","password":"secret123","user_enabled":"true"}' \
  "http://fusionpbx/app/models/api/index.php?resource=users"
```

#### Update User
```bash
curl -X PUT \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"user_enabled":"false"}' \
  "http://fusionpbx/app/models/api/index.php?resource=users&id=USER-UUID"
```

#### Delete User
```bash
curl -X DELETE \
  -b cookies.txt \
  "http://fusionpbx/app/models/api/index.php?resource=users&id=USER-UUID"
```

### Using JavaScript/Fetch

```javascript
// List extensions
fetch('/app/models/api/index.php?resource=extensions&enabled=true')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Extensions:', data.data.extensions);
    }
  });

// Create extension
fetch('/app/models/api/index.php?resource=extensions', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    extension: '1002',
    password: 'secret',
    enabled: 'true'
  })
})
  .then(response => response.json())
  .then(data => console.log(data));

// Update extension
fetch('/app/models/api/index.php?resource=extensions&id=EXT-UUID', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    description: 'Updated description'
  })
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Using PHP

```php
<?php
// Initialize session
session_start();

// Make API request
function apiRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// List users
$users = apiRequest('GET', 'http://fusionpbx/app/models/api/index.php?resource=users');
print_r($users);

// Create user
$newUser = apiRequest('POST', 'http://fusionpbx/app/models/api/index.php?resource=users', [
    'username' => 'testuser',
    'password' => 'secret123'
]);
print_r($newUser);
```

## Best Practices

1. **Always check permissions** before making requests
2. **Use pagination** for large datasets
3. **Handle errors gracefully** with try-catch blocks
4. **Validate input** before sending to API
5. **Use HTTPS** in production
6. **Cache responses** when appropriate
7. **Respect rate limits** (if implemented)
8. **Log API calls** for debugging

## Security

- All endpoints require authentication via FusionPBX session
- Permission checks are enforced for all operations
- Multi-tenant isolation via domain UUID
- SQL injection prevention via Eloquent ORM
- Domain ownership validation for all resources

## Support

For issues or questions:
- Check the documentation in `app/models/README.md`
- Review examples in `app/models/examples.php`
- See multi-tenant guide in `app/models/MULTITENANT.md`
