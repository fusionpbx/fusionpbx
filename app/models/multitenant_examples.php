<?php
/**
 * FusionPBX - Multi-Tenant with User/Group Permissions Examples
 * 
 * This file demonstrates how to use the enhanced Eloquent models for
 * multi-tenant support with comprehensive user/group permissions.
 * 
 * Usage: php app/models/multitenant_examples.php
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

// Include the bootstrap file to initialize Eloquent
require_once(__DIR__ . '/eloquent_bootstrap.php');

// Import the models
use FusionPBX\Models\Domain;
use FusionPBX\Models\User;
use FusionPBX\Models\UserSetting;
use FusionPBX\Models\Group;
use FusionPBX\Models\Permission;
use FusionPBX\Models\GroupPermission;
use FusionPBX\Models\Dashboard;
use FusionPBX\Models\DashboardWidget;
use Illuminate\Database\Capsule\Manager as DB;

echo "=============================================================\n";
echo "FusionPBX Multi-Tenant with Permissions Examples\n";
echo "=============================================================\n\n";

// Get a domain for examples
$domain = Domain::first();
if (!$domain) {
    echo "⚠ No domains found. Please create a domain first.\n";
    exit(1);
}

$domainUuid = $domain->domain_uuid;
echo "Using domain: {$domain->domain_name} ({$domainUuid})\n\n";

// =============================================================================
// Example 1: Domain Multi-Tenant Relationships
// =============================================================================
echo "=== Example 1: Domain Multi-Tenant Relationships ===\n";

// Get domain with all related entities
$domainWithRelations = Domain::with([
    'users', 
    'extensions', 
    'dashboards', 
    'groups',
    'groupPermissions'
])->find($domainUuid);

if ($domainWithRelations) {
    echo "Domain: {$domainWithRelations->domain_name}\n";
    echo "  Users: {$domainWithRelations->users->count()}\n";
    echo "  Extensions: {$domainWithRelations->extensions->count()}\n";
    echo "  Dashboards: {$domainWithRelations->dashboards->count()}\n";
    echo "  Groups: {$domainWithRelations->groups->count()}\n";
    echo "  Group Permissions: {$domainWithRelations->groupPermissions->count()}\n";
}
echo "\n";

// =============================================================================
// Example 2: User Settings Management
// =============================================================================
echo "=== Example 2: User Settings Management ===\n";

$user = User::forDomain($domainUuid)->first();
if ($user) {
    echo "User: {$user->username}\n";
    
    // Get all user settings
    $settings = $user->settings;
    echo "  Total settings: {$settings->count()}\n";
    
    // Get settings by category
    $themeSettings = $user->settingsByCategory('theme')->get();
    echo "  Theme settings: {$themeSettings->count()}\n";
    
    // Get a specific setting value
    $language = $user->getSetting('domain', 'language', 'code', 'en-us');
    echo "  Language: {$language}\n";
    
    // Set a user setting
    // Commented out for safety
    // $user->setSetting('dashboard', 'layout', 'columns', '3');
    // echo "  Dashboard columns setting updated\n";
}
echo "\n";

// =============================================================================
// Example 3: User Permissions Checking
// =============================================================================
echo "=== Example 3: User Permissions Checking ===\n";

if ($user) {
    // Get user's groups
    $groups = $user->groups;
    echo "User Groups: " . $groups->pluck('group_name')->implode(', ') . "\n";
    
    // Check specific permission
    $canViewExtensions = $user->hasPermission('extension_view');
    echo "Can view extensions: " . ($canViewExtensions ? 'Yes' : 'No') . "\n";
    
    // Check multiple permissions
    $hasAny = $user->hasAnyPermission(['extension_add', 'extension_edit']);
    echo "Can add or edit extensions: " . ($hasAny ? 'Yes' : 'No') . "\n";
    
    // Check if user is admin
    $isAdmin = $user->isAdmin();
    echo "Is admin: " . ($isAdmin ? 'Yes' : 'No') . "\n";
    
    $isSuperAdmin = $user->isSuperAdmin();
    echo "Is superadmin: " . ($isSuperAdmin ? 'Yes' : 'No') . "\n";
    
    // Get all permission names
    $permissionNames = $user->getPermissionNames();
    echo "Total permissions: {$permissionNames->count()}\n";
    if ($permissionNames->count() > 0) {
        echo "  Sample permissions: " . $permissionNames->take(5)->implode(', ') . "\n";
    }
}
echo "\n";

// =============================================================================
// Example 4: Group Permissions Management
// =============================================================================
echo "=== Example 4: Group Permissions Management ===\n";

$group = Group::forDomain($domainUuid)->first();
if ($group) {
    echo "Group: {$group->group_name}\n";
    
    // Get group's permissions
    $groupPermissions = $group->permissionsList;
    echo "  Total permissions: {$groupPermissions->count()}\n";
    
    // Check if group has a specific permission
    $hasPermission = $group->hasPermission('extension_view');
    echo "  Has extension_view: " . ($hasPermission ? 'Yes' : 'No') . "\n";
    
    // Get all permission names for the group
    $permNames = $group->getPermissionNames();
    if ($permNames->count() > 0) {
        echo "  Sample permissions: " . $permNames->take(5)->implode(', ') . "\n";
    }
    
    // Get users in this group
    $usersInGroup = $group->users;
    echo "  Users in group: {$usersInGroup->count()}\n";
}
echo "\n";

// =============================================================================
// Example 5: Permission Management (Grant/Revoke)
// =============================================================================
echo "=== Example 5: Permission Management (Grant/Revoke) ===\n";
echo "// Example code (commented for safety):\n";
echo "/*\n";
echo "// Find a permission\n";
echo "\$permission = Permission::findByName('extension_view');\n";
echo "\n";
echo "// Grant permission to a group\n";
echo "if (\$permission && \$group) {\n";
echo "    \$group->grantPermission(\$permission->permission_uuid, \$domainUuid);\n";
echo "    echo \"Permission granted to group\\n\";\n";
echo "}\n";
echo "\n";
echo "// Revoke permission from a group\n";
echo "\$group->revokePermission(\$permission->permission_uuid, \$domainUuid);\n";
echo "\n";
echo "// Sync multiple permissions at once\n";
echo "\$permissionUuids = [\n";
echo "    'uuid-1', 'uuid-2', 'uuid-3'\n";
echo "];\n";
echo "\$group->syncPermissions(\$permissionUuids, \$domainUuid);\n";
echo "*/\n";
echo "\n";

// =============================================================================
// Example 6: Dashboard Multi-Tenant Access
// =============================================================================
echo "=== Example 6: Dashboard Multi-Tenant Access ===\n";

// Get dashboards for a specific domain
$dashboards = Dashboard::forDomain($domainUuid)
    ->enabled()
    ->with('widgets')
    ->get();

echo "Dashboards in domain: {$dashboards->count()}\n";
foreach ($dashboards->take(3) as $dashboard) {
    echo "  - {$dashboard->dashboard_name}\n";
    echo "    Widgets: {$dashboard->widgets->count()}\n";
}
echo "\n";

// =============================================================================
// Example 7: User Dashboard Access
// =============================================================================
echo "=== Example 7: User Dashboard Access ===\n";

if ($user) {
    // Get dashboards accessible to the user (via their domain)
    $userDashboards = $user->dashboards;
    echo "Dashboards accessible to {$user->username}: {$userDashboards->count()}\n";
    
    foreach ($userDashboards->take(2) as $dashboard) {
        echo "  - {$dashboard->dashboard_name}\n";
    }
}
echo "\n";

// =============================================================================
// Example 8: Multi-Tenant Permission Isolation
// =============================================================================
echo "=== Example 8: Multi-Tenant Permission Isolation ===\n";

// Get all permissions assigned in this domain
$domainPermissions = $domain->getAssignedPermissions();
echo "Permissions assigned in domain: {$domainPermissions->count()}\n";

// Get permissions by application
$extensionPermissions = Permission::byApplication('extensions')->get();
echo "Extension-related permissions: {$extensionPermissions->count()}\n";

// Check if a permission is assigned in the domain
$permission = Permission::findByName('extension_view');
if ($permission) {
    $isAssigned = $permission->isAssignedInDomain($domainUuid);
    echo "extension_view is assigned in domain: " . ($isAssigned ? 'Yes' : 'No') . "\n";
}
echo "\n";

// =============================================================================
// Example 9: Domain-Scoped Queries (Multi-Tenant Security)
// =============================================================================
echo "=== Example 9: Domain-Scoped Queries ===\n";

// Always scope queries by domain for security
$domainUsers = User::forDomain($domainUuid)->enabled()->get();
echo "Enabled users in domain: {$domainUsers->count()}\n";

$domainGroups = Group::forDomain($domainUuid)->get();
echo "Groups in domain: {$domainGroups->count()}\n";

// Get permissions for a specific domain
$domainGroupPermissions = GroupPermission::forDomain($domainUuid)->get();
echo "Group permissions in domain: {$domainGroupPermissions->count()}\n";
echo "\n";

// =============================================================================
// Example 10: User Settings with Categories
// =============================================================================
echo "=== Example 10: User Settings with Categories ===\n";

// Get settings by category
$dashboardSettings = UserSetting::forDomain($domainUuid)
    ->category('dashboard')
    ->enabled()
    ->get();
echo "Dashboard settings in domain: {$dashboardSettings->count()}\n";

// Get settings by category and subcategory
$layoutSettings = UserSetting::forDomain($domainUuid)
    ->categorySubcategory('dashboard', 'layout')
    ->enabled()
    ->get();
echo "Dashboard layout settings: {$layoutSettings->count()}\n";

// Get a specific setting
$specificSetting = UserSetting::forDomain($domainUuid)
    ->name('theme')
    ->first();
if ($specificSetting) {
    echo "Theme setting value: {$specificSetting->user_setting_value}\n";
}
echo "\n";

// =============================================================================
// Example 11: Complex Permission Queries
// =============================================================================
echo "=== Example 11: Complex Permission Queries ===\n";

// Get all users with a specific permission
$usersWithPermission = User::whereHas('groups.permissionsList', function($query) {
    $query->where('permission_name', 'extension_add');
})->forDomain($domainUuid)->get();
echo "Users with 'extension_add' permission: {$usersWithPermission->count()}\n";

// Get all groups that have dashboard permissions
$groupsWithDashboardPerms = Group::whereHas('permissionsList', function($query) {
    $query->where('permission_name', 'like', 'dashboard_%');
})->forDomain($domainUuid)->get();
echo "Groups with dashboard permissions: {$groupsWithDashboardPerms->count()}\n";
echo "\n";

// =============================================================================
// Example 12: Dashboard Widgets with Permissions
// =============================================================================
echo "=== Example 12: Dashboard Widgets with Permissions ===\n";

$dashboard = Dashboard::forDomain($domainUuid)->with('enabledWidgets')->first();
if ($dashboard) {
    echo "Dashboard: {$dashboard->dashboard_name}\n";
    $widgets = $dashboard->enabledWidgets;
    echo "  Enabled widgets: {$widgets->count()}\n";
    
    foreach ($widgets->take(3) as $widget) {
        echo "  - {$widget->widget_name}\n";
        
        // Get widget groups (for permission-based visibility)
        $widgetGroups = $widget->widgetGroups()->with('group')->get();
        echo "    Visible to " . $widgetGroups->count() . " group(s)\n";
    }
}
echo "\n";

// =============================================================================
// Example 13: Hierarchical Permissions (Parent/Child Domains)
// =============================================================================
echo "=== Example 13: Hierarchical Permissions ===\n";

// Get parent domain
$parentDomain = $domain->parent;
if ($parentDomain) {
    echo "Parent domain: {$parentDomain->domain_name}\n";
} else {
    echo "This is a top-level domain\n";
}

// Get child domains
$childDomains = $domain->children;
echo "Child domains: {$childDomains->count()}\n";
foreach ($childDomains->take(3) as $child) {
    echo "  - {$child->domain_name}\n";
}
echo "\n";

// =============================================================================
// Example 14: Bulk Operations with Transactions
// =============================================================================
echo "=== Example 14: Bulk Operations with Transactions ===\n";
echo "// Example code (commented for safety):\n";
echo "/*\n";
echo "DB::beginTransaction();\n";
echo "try {\n";
echo "    // Create a new group\n";
echo "    \$newGroup = Group::create([\n";
echo "        'group_uuid' => 'new-uuid',\n";
echo "        'domain_uuid' => \$domainUuid,\n";
echo "        'group_name' => 'custom_group',\n";
echo "        'group_level' => 50,\n";
echo "    ]);\n";
echo "    \n";
echo "    // Assign multiple permissions\n";
echo "    \$permissions = Permission::byApplication('extensions')->get();\n";
echo "    foreach (\$permissions as \$perm) {\n";
echo "        \$newGroup->grantPermission(\$perm->permission_uuid, \$domainUuid);\n";
echo "    }\n";
echo "    \n";
echo "    // Add users to the group\n";
echo "    \$users = User::forDomain(\$domainUuid)->enabled()->take(5)->get();\n";
echo "    \$newGroup->users()->attach(\$users->pluck('user_uuid'));\n";
echo "    \n";
echo "    DB::commit();\n";
echo "    echo \"Group created with permissions and users\\n\";\n";
echo "} catch (\\Exception \$e) {\n";
echo "    DB::rollback();\n";
echo "    echo \"Error: \" . \$e->getMessage() . \"\\n\";\n";
echo "}\n";
echo "*/\n";
echo "\n";

// =============================================================================
// Example 15: Permission Audit Trail
// =============================================================================
echo "=== Example 15: Permission Audit Trail ===\n";

// Get all permission assignments with details
$permissionAudit = GroupPermission::with(['group', 'permission', 'domain'])
    ->forDomain($domainUuid)
    ->get();

echo "Permission assignments in domain: {$permissionAudit->count()}\n";
foreach ($permissionAudit->take(5) as $assignment) {
    if ($assignment->group && $assignment->permission) {
        echo "  - Group '{$assignment->group->group_name}' has '{$assignment->permission->permission_name}'\n";
    }
}
echo "\n";

echo "=============================================================\n";
echo "Multi-Tenant Examples Completed!\n";
echo "=============================================================\n\n";

echo "Key Takeaways:\n";
echo "1. Always use forDomain() scope for multi-tenant isolation\n";
echo "2. Check user permissions before allowing actions\n";
echo "3. Use transactions for bulk permission operations\n";
echo "4. User settings support categorization for organization\n";
echo "5. Dashboards are domain-scoped for multi-tenancy\n";
echo "6. Groups provide flexible permission management\n";
echo "7. Permission checks can be done at user or group level\n\n";
