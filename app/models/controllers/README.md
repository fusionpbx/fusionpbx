# FusionPBX Eloquent Controllers

HTTP controllers providing REST API endpoints for FusionPBX Eloquent models.

## Overview

This directory contains REST API controllers that provide programmatic access to FusionPBX resources using Laravel Eloquent ORM.

## Architecture

```
controllers/
├── BaseController.php          # Base class with common functionality
├── UserController.php          # User management
├── ExtensionController.php     # Extension management
├── GroupController.php         # Group & permission management
├── DashboardController.php     # Dashboard management
└── PermissionController.php    # Permission listing
```

## Controllers

### BaseController

Abstract base class providing:
- Session management
- Permission checking
- JSON response formatting
- Error handling
- Input validation
- Domain ownership validation

**Key Methods:**
- `requireAuth()` - Require authentication
- `requirePermission($name)` - Require specific permission
- `hasPermission($name)` - Check permission
- `validateDomainOwnership($resource)` - Validate resource belongs to user's domain
- `success($data, $message)` - Send success response
- `error($message, $code)` - Send error response
- `param($key, $default)` - Get request parameter
- `validateRequired($fields)` - Validate required fields

### UserController

Manages users with full CRUD operations.

**Endpoints:**
- `GET /api?resource=users` - List users
- `GET /api?resource=users&id={uuid}` - Get user
- `POST /api?resource=users` - Create user
- `PUT /api?resource=users&id={uuid}` - Update user
- `DELETE /api?resource=users&id={uuid}` - Delete user
- `GET /api?resource=users&id={uuid}&action=permissions` - Get permissions
- `GET /api?resource=users&id={uuid}&action=settings` - Get settings
- `PUT /api?resource=users&id={uuid}&action=settings` - Update setting

**Permissions Required:**
- `user_view` - View users
- `user_add` - Create users
- `user_edit` - Update users
- `user_delete` - Delete users

### ExtensionController

Manages extensions with CRUD operations.

**Endpoints:**
- `GET /api?resource=extensions` - List extensions
- `GET /api?resource=extensions&id={uuid}` - Get extension
- `POST /api?resource=extensions` - Create extension
- `PUT /api?resource=extensions&id={uuid}` - Update extension
- `DELETE /api?resource=extensions&id={uuid}` - Delete extension

**Permissions Required:**
- `extension_view` - View extensions
- `extension_add` - Create extensions
- `extension_edit` - Update extensions
- `extension_delete` - Delete extensions

### GroupController

Manages groups and their permissions.

**Endpoints:**
- `GET /api?resource=groups` - List groups
- `GET /api?resource=groups&id={uuid}` - Get group
- `POST /api?resource=groups` - Create group
- `PUT /api?resource=groups&id={uuid}` - Update group
- `DELETE /api?resource=groups&id={uuid}` - Delete group
- `GET /api?resource=groups&id={uuid}&action=permissions` - Get permissions
- `POST /api?resource=groups&id={uuid}&action=permissions` - Grant permission
- `DELETE /api?resource=groups&id={uuid}&action=permissions` - Revoke permission

**Permissions Required:**
- `group_view` - View groups
- `group_add` - Create groups
- `group_edit` - Update groups & manage permissions
- `group_delete` - Delete groups

### DashboardController

Manages dashboards with CRUD operations.

**Endpoints:**
- `GET /api?resource=dashboards` - List dashboards
- `GET /api?resource=dashboards&id={uuid}` - Get dashboard
- `POST /api?resource=dashboards` - Create dashboard
- `PUT /api?resource=dashboards&id={uuid}` - Update dashboard
- `DELETE /api?resource=dashboards&id={uuid}` - Delete dashboard

**Permissions Required:**
- `dashboard_view` - View dashboards
- `dashboard_add` - Create dashboards
- `dashboard_edit` - Update dashboards
- `dashboard_delete` - Delete dashboards

### PermissionController

Lists system permissions (read-only).

**Endpoints:**
- `GET /api?resource=permissions` - List permissions
- `GET /api?resource=permissions&id={uuid}` - Get permission
- `GET /api?resource=permissions&id={app}&action=by-application` - By application

**Permissions Required:**
- `permission_view` - View permissions

## Usage

### Accessing the API

The API is accessed via `/app/models/api/index.php` with query parameters:

```
/app/models/api/index.php?resource={resource}&id={uuid}&action={action}
```

### Example Requests

**List users:**
```bash
curl "http://fusionpbx/app/models/api/index.php?resource=users"
```

**Get specific user:**
```bash
curl "http://fusionpbx/app/models/api/index.php?resource=users&id=USER-UUID"
```

**Create extension:**
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"extension":"1001","password":"secret"}' \
  "http://fusionpbx/app/models/api/index.php?resource=extensions"
```

**Update user:**
```bash
curl -X PUT \
  -H "Content-Type: application/json" \
  -d '{"user_enabled":"false"}' \
  "http://fusionpbx/app/models/api/index.php?resource=users&id=USER-UUID"
```

## Creating New Controllers

To add a new controller:

1. **Create controller class** extending `BaseController`:

```php
<?php
namespace FusionPBX\Controllers;

use FusionPBX\Models\YourModel;

class YourController extends BaseController
{
    public function handle()
    {
        $this->requireAuth();
        
        $id = $this->param('id');
        
        switch ($this->method) {
            case 'GET':
                return $id ? $this->show($id) : $this->index();
            case 'POST':
                return $this->store();
            case 'PUT':
            case 'PATCH':
                return $this->update($id);
            case 'DELETE':
                return $this->destroy($id);
        }
    }
    
    protected function index()
    {
        $this->requirePermission('your_resource_view');
        
        $resources = YourModel::forDomain($this->domain_uuid)->get();
        $this->success(['resources' => $resources]);
    }
    
    // Implement other methods...
}
```

2. **Add route** in `api/index.php`:

```php
case 'your-resource':
    $controller = new \FusionPBX\Controllers\YourController();
    $_GET['id'] = $id;
    $controller->handle();
    break;
```

3. **Document** in `API.md`

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Success",
  "data": { }
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
    "field": "Error message"
  }
}
```

## Security

All controllers implement:

1. **Authentication** - Session-based via `requireAuth()`
2. **Authorization** - Permission checks via `requirePermission()`
3. **Multi-tenant** - Domain isolation via `forDomain()`
4. **Domain Validation** - Resource ownership via `validateDomainOwnership()`
5. **SQL Injection** - Prevention via Eloquent ORM

## Best Practices

1. **Always require authentication** - Use `requireAuth()` in handle()
2. **Check permissions** - Use `requirePermission()` for each action
3. **Validate domain** - Use `validateDomainOwnership()` for resources
4. **Validate input** - Use `validateRequired()` for required fields
5. **Use transactions** - For complex operations with multiple models
6. **Return proper codes** - Use appropriate HTTP status codes
7. **Handle exceptions** - Catch and return user-friendly errors

## Testing

Test controllers with:

```bash
# Start PHP server
php -S localhost:8000

# Test endpoints
curl "http://localhost:8000/app/models/api/index.php?resource=users"
curl "http://localhost:8000/app/models/api/index.php?resource=extensions"
curl "http://localhost:8000/app/models/api/index.php?resource=permissions"
```

## Documentation

- **API.md** - Complete REST API documentation
- **README.md** (Models) - Eloquent models documentation
- **MULTITENANT.md** - Multi-tenant guide
- **examples.php** - Model usage examples

## Support

For issues or questions:
- Review controller code for implementation details
- Check API.md for endpoint documentation
- See models documentation in parent directory
- Test with cURL or browser developer tools
