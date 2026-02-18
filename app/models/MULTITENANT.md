# FusionPBX Eloquent - Multi-Tenant with Permissions Guide

This guide explains how to use the enhanced Eloquent models for comprehensive multi-tenant support with user/group permissions in FusionPBX.

## Table of Contents
1. [Overview](#overview)
2. [Models](#models)
3. [Domain Multi-Tenancy](#domain-multi-tenancy)
4. [User & Group Permissions](#user--group-permissions)
5. [User Settings](#user-settings)
6. [Dashboard Management](#dashboard-management)
7. [Best Practices](#best-practices)
8. [Security](#security)

## Overview

FusionPBX supports multi-tenant deployments where each domain represents an isolated tenant. The Eloquent models provide comprehensive support for:

- **Domain Isolation**: All data is scoped by domain
- **Permission Management**: Role-based access control (RBAC)
- **User Settings**: Per-user preferences and configuration
- **Dashboard Support**: Customizable dashboards per domain

## Models

### Core Multi-Tenant Models

- **Domain**: Represents a tenant/organization
- **User**: User accounts with permission checking
- **UserSetting**: User-specific settings and preferences
- **Group**: Permission groups (roles)
- **Permission**: System-wide permissions
- **GroupPermission**: Links permissions to groups within a domain
- **Dashboard**: Domain-specific dashboards
- **DashboardWidget**: Dashboard widgets with group-based visibility

## Domain Multi-Tenancy

### Domain Model

Every domain represents an isolated tenant in FusionPBX.

```php
use FusionPBX\Models\Domain;

// Get a domain
$domain = Domain::find($domainUuid);

// Get domain relationships
$users = $domain->users;              // All users in domain
$extensions = $domain->extensions;    // All extensions
$dashboards = $domain->dashboards;    // All dashboards
$groups = $domain->groups;            // All groups
$permissions = $domain->groupPermissions; // All permission assignments

// Get only enabled resources
$enabledDashboards = $domain->enabledDashboards;

// Get assigned permissions
$assignedPermissions = $domain->getAssignedPermissions();

// Hierarchical domains
$parent = $domain->parent;            // Parent domain
$children = $domain->children;        // Child domains
```

### Domain-Scoped Queries

**ALWAYS** scope queries by domain for multi-tenant security:

```php
use FusionPBX\Models\User;
use FusionPBX\Models\Extension;
use FusionPBX\Models\Group;

// Good - Domain-scoped
$users = User::forDomain($domainUuid)->get();
$extensions = Extension::forDomain($domainUuid)->enabled()->get();
$groups = Group::forDomain($domainUuid)->get();

// Bad - Returns data from ALL domains (security risk!)
$users = User::all();
```

## User & Group Permissions

### Permission Model

Represents system-wide permissions that can be assigned to groups.

```php
use FusionPBX\Models\Permission;

// Find permission by name
$permission = Permission::findByName('extension_view');

// Get permissions by application
$extensionPerms = Permission::byApplication('extensions')->get();

// Check if assigned in domain
$isAssigned = $permission->isAssignedInDomain($domainUuid);

// Get groups with this permission
$groups = $permission->groups;
```

### Group Model with Permissions

Groups act as roles in the RBAC system.

```php
use FusionPBX\Models\Group;

$group = Group::forDomain($domainUuid)->first();

// Check permissions
$hasPermission = $group->hasPermission('extension_add');

// Get all permissions
$permissions = $group->permissionsList;
$permissionNames = $group->getPermissionNames();

// Grant permission to group
$group->grantPermission($permissionUuid, $domainUuid);

// Revoke permission from group
$group->revokePermission($permissionUuid, $domainUuid);

// Sync multiple permissions (removes old, adds new)
$permissionUuids = ['uuid-1', 'uuid-2', 'uuid-3'];
$group->syncPermissions($permissionUuids, $domainUuid);

// Get users in group
$users = $group->users;
```

### User Model with Permission Checking

Users inherit permissions from their groups.

```php
use FusionPBX\Models\User;

$user = User::find($userUuid);

// Check single permission
if ($user->hasPermission('extension_add')) {
    // User can add extensions
}

// Check any of multiple permissions
if ($user->hasAnyPermission(['extension_add', 'extension_edit'])) {
    // User can add OR edit
}

// Check all permissions
if ($user->hasAllPermissions(['extension_view', 'extension_add'])) {
    // User has both permissions
}

// Get all permissions
$allPermissions = $user->getAllPermissions();
$permissionNames = $user->getPermissionNames();

// Check group membership
if ($user->hasGroup('admin')) {
    // User is in admin group
}

if ($user->hasAnyGroup(['admin', 'superadmin'])) {
    // User is in at least one of these groups
}

// Convenience methods
if ($user->isSuperAdmin()) {
    // User is a superadmin
}

if ($user->isAdmin()) {
    // User is admin or superadmin
}

// Get user's groups
$groups = $user->groups;
```

### Permission Management

```php
use FusionPBX\Models\GroupPermission;
use Illuminate\Database\Capsule\Manager as DB;

// Grant permission to group
GroupPermission::grant($groupUuid, $permissionUuid, $domainUuid);

// Revoke permission from group
GroupPermission::revoke($groupUuid, $permissionUuid, $domainUuid);

// Bulk operations with transaction
DB::beginTransaction();
try {
    // Create group
    $group = Group::create([
        'group_uuid' => 'new-uuid',
        'domain_uuid' => $domainUuid,
        'group_name' => 'Operators',
        'group_level' => 50,
    ]);
    
    // Assign permissions
    $permissions = Permission::byApplication('extensions')->get();
    foreach ($permissions as $perm) {
        $group->grantPermission($perm->permission_uuid, $domainUuid);
    }
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    throw $e;
}
```

## User Settings

User settings provide per-user preferences organized by category and subcategory.

### UserSetting Model

```php
use FusionPBX\Models\UserSetting;

$user = User::find($userUuid);

// Get setting value
$theme = $user->getSetting('dashboard', 'theme', 'color', 'default');
//                         ↑category   ↑subcat   ↑name    ↑default

// Set setting value
$user->setSetting('dashboard', 'layout', 'columns', '3');

// Get settings by category
$dashboardSettings = $user->settingsByCategory('dashboard')->get();

// Get all user settings
$allSettings = $user->settings;

// Get only enabled settings
$enabledSettings = $user->enabledSettings;
```

### Query User Settings

```php
// Get settings by category
$settings = UserSetting::forDomain($domainUuid)
    ->category('theme')
    ->enabled()
    ->get();

// Get settings by category and subcategory
$layoutSettings = UserSetting::forDomain($domainUuid)
    ->categorySubcategory('dashboard', 'layout')
    ->enabled()
    ->get();

// Get specific setting
$setting = UserSetting::forDomain($domainUuid)
    ->name('language')
    ->first();

// Static helper methods
$value = UserSetting::getValue(
    $userUuid,
    'dashboard',
    'theme',
    'color',
    'blue' // default
);

UserSetting::setValue(
    $userUuid,
    $domainUuid,
    'dashboard',
    'theme',
    'color',
    'red'
);
```

## Dashboard Management

Dashboards are domain-scoped with widget management.

### Dashboard Model

```php
use FusionPBX\Models\Dashboard;

// Get dashboards for domain
$dashboards = Dashboard::forDomain($domainUuid)
    ->enabled()
    ->with('widgets')
    ->get();

// Get dashboard with relationships
$dashboard = Dashboard::with(['widgets', 'domain'])->find($dashboardUuid);

// Get enabled widgets
$widgets = $dashboard->enabledWidgets;

// Get user's accessible dashboards
$user = User::find($userUuid);
$userDashboards = $user->dashboards;
```

### Dashboard Widget Model

```php
use FusionPBX\Models\DashboardWidget;

$dashboard = Dashboard::find($dashboardUuid);

// Get all widgets
$widgets = $dashboard->widgets;

// Get enabled widgets in order
$widgets = $dashboard->enabledWidgets;

// Query widgets
$topLevelWidgets = DashboardWidget::where('dashboard_uuid', $dashboardUuid)
    ->topLevel()
    ->enabled()
    ->ordered()
    ->get();

// Widget relationships
$widget = DashboardWidget::find($widgetUuid);
$parent = $widget->parent;      // Parent widget (for nesting)
$children = $widget->children;  // Child widgets
$groups = $widget->widgetGroups; // Groups that can see this widget
```

### Widget Group Visibility

```php
use FusionPBX\Models\DashboardWidgetGroup;

$widget = DashboardWidget::find($widgetUuid);

// Get groups that can see this widget
$widgetGroups = $widget->widgetGroups()->with('group')->get();

foreach ($widgetGroups as $wg) {
    echo "Visible to group: {$wg->group->group_name}\n";
}
```

## Best Practices

### 1. Always Scope by Domain

```php
// Good
$users = User::forDomain($domainUuid)->get();

// Bad - security risk
$users = User::all();
```

### 2. Check Permissions Before Actions

```php
public function addExtension($user, $data) {
    if (!$user->hasPermission('extension_add')) {
        throw new UnauthorizedException('No permission to add extensions');
    }
    
    // Proceed with adding extension
}
```

### 3. Use Transactions for Related Operations

```php
DB::beginTransaction();
try {
    $user = User::create([...]);
    $user->groups()->attach($groupIds);
    $user->setSetting('theme', 'color', 'name', 'blue');
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    throw $e;
}
```

### 4. Use Eager Loading

```php
// Good - one query with joins
$users = User::with(['groups', 'settings', 'domain'])->get();

// Bad - N+1 queries
$users = User::all();
foreach ($users as $user) {
    $user->groups; // Separate query for each user
}
```

### 5. Cache Permission Checks

```php
// Cache expensive permission lookups
$cacheKey = "user_permissions_{$userUuid}";
$permissions = Cache::remember($cacheKey, 3600, function() use ($user) {
    return $user->getPermissionNames();
});
```

## Security

### Multi-Tenant Isolation

1. **Always filter by domain** in queries
2. **Validate domain ownership** before allowing actions
3. **Check user belongs to domain** before operations

```php
// Validate user belongs to domain
if ($user->domain_uuid !== $domainUuid) {
    throw new UnauthorizedException();
}

// Validate resource belongs to domain
if ($extension->domain_uuid !== $user->domain_uuid) {
    throw new UnauthorizedException();
}
```

### Permission-Based Access Control

```php
class ExtensionController {
    public function view($extensionUuid) {
        $user = Auth::user();
        
        // Check permission
        if (!$user->hasPermission('extension_view')) {
            abort(403, 'No permission');
        }
        
        // Check domain ownership
        $extension = Extension::findOrFail($extensionUuid);
        if ($extension->domain_uuid !== $user->domain_uuid) {
            abort(403, 'Access denied');
        }
        
        return view('extension', compact('extension'));
    }
}
```

### SQL Injection Prevention

Eloquent automatically escapes parameters:

```php
// Safe - parameterized
User::where('username', $username)->first();

// Never use raw SQL without bindings
User::whereRaw("username = '$username'"); // DANGEROUS!

// If you must use raw SQL, use bindings
User::whereRaw("username = ?", [$username]); // Safe
```

## Examples

See `app/models/multitenant_examples.php` for 15 comprehensive examples covering:

1. Domain multi-tenant relationships
2. User settings management
3. User permission checking
4. Group permission management
5. Permission grant/revoke
6. Dashboard multi-tenant access
7. User dashboard access
8. Multi-tenant permission isolation
9. Domain-scoped queries
10. User settings with categories
11. Complex permission queries
12. Dashboard widgets with permissions
13. Hierarchical permissions
14. Bulk operations with transactions
15. Permission audit trail

Run: `php app/models/multitenant_examples.php`

## Summary

The multi-tenant support in FusionPBX Eloquent models provides:

✅ **Complete domain isolation** for secure multi-tenancy  
✅ **Flexible RBAC** with groups and permissions  
✅ **User settings** with categories for organization  
✅ **Dashboard management** with widget visibility control  
✅ **Helper methods** for common permission checks  
✅ **Relationship support** for easy data access  
✅ **Transaction support** for safe bulk operations  

For more information, see:
- `app/models/README.md` - General documentation
- `app/models/QUICKSTART.md` - Quick start guide
- `app/models/multitenant_examples.php` - Usage examples
