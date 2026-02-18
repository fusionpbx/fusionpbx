# FusionPBX Laravel Eloquent Models

This package provides Laravel Eloquent ORM models for easier database management with all FusionPBX database tables.

## Features

- ✅ **Full Database Coverage**: Models for 100+ FusionPBX database tables
- ✅ **Laravel Eloquent ORM**: Powerful query builder and relationships
- ✅ **Multi-Database Support**: PostgreSQL, MySQL, and SQLite
- ✅ **Type Safety**: Proper type casting for all fields
- ✅ **Relationships**: Pre-configured relationships between models
- ✅ **Scopes**: Useful query scopes for common filters
- ✅ **UUID Primary Keys**: Native support for FusionPBX's UUID system
- ✅ **Domain Multi-tenancy**: Built-in domain filtering support

## Installation

### 1. Install Dependencies

The package uses Composer for dependency management. Dependencies are already installed via `composer.json`.

```bash
composer install
```

### 2. Include the Bootstrap File

Include the Eloquent bootstrap file in your PHP code before using any models:

```php
require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');
```

This file:
- Loads Composer autoloader
- Reads FusionPBX database configuration
- Initializes Laravel Eloquent ORM
- Configures database connection

## Configuration

The bootstrap automatically reads FusionPBX's database configuration from:
- `/etc/fusionpbx/config.php` (if exists)
- `resources/pdo.php` (FusionPBX's default config)

It supports:
- **PostgreSQL** (default)
- **MySQL**
- **SQLite**

## Available Models

### Core System Models
- `Domain` - Multi-tenant domains
- `User` - User accounts
- `Group` - Permission groups
- `Contact` - Contact management

### Communication Models
- `Extension` - SIP extensions
- `Voicemail` - Voicemail boxes
- `Gateway` - SIP gateways/trunks
- `Device` - SIP devices
- `Dialplan` - Dialplan routing

### Call Center Models
- `CallCenterQueue` - Call queues
- `CallCenterAgent` - Agents

### Conference Models
- `Conference` - Conference rooms

### IVR & Routing Models
- `IvrMenu` - IVR menus
- `RingGroup` - Ring groups

### Media Models
- `Recording` - Audio recordings
- `MusicOnHold` - Music on hold
- `Fax` - Fax extensions

### CDR Models
- `XmlCdr` - Call detail records

## Usage Examples

### Basic Queries

```php
<?php
require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');

use FusionPBX\Models\Domain;
use FusionPBX\Models\Extension;
use FusionPBX\Models\User;

// Get all domains
$domains = Domain::all();

// Get a specific domain by UUID
$domain = Domain::find('uuid-here');

// Get enabled domains only
$enabledDomains = Domain::enabled()->get();

// Get domain by name
$domain = Domain::where('domain_name', 'example.com')->first();
```

### Working with Extensions

```php
use FusionPBX\Models\Extension;

// Get all extensions for a domain
$extensions = Extension::forDomain('domain-uuid')
    ->enabled()
    ->get();

// Find extension by number
$extension = Extension::where('extension', '1001')
    ->where('domain_uuid', 'domain-uuid')
    ->first();

// Get extension with related data
$extension = Extension::with(['users', 'voicemail', 'domain'])
    ->find('extension-uuid');

// Create new extension
$extension = Extension::create([
    'extension_uuid' => 'new-uuid',
    'domain_uuid' => 'domain-uuid',
    'extension' => '1002',
    'password' => 'secure-password',
    'enabled' => true,
    'description' => 'New Extension'
]);

// Update extension
$extension->update([
    'description' => 'Updated Description',
    'enabled' => true
]);

// Delete extension
$extension->delete();
```

### Working with Users

```php
use FusionPBX\Models\User;

// Get all users for a domain
$users = User::forDomain('domain-uuid')
    ->enabled()
    ->get();

// Get user with groups and extensions
$user = User::with(['groups', 'extensions', 'contact'])
    ->find('user-uuid');

// Find user by username
$user = User::where('username', 'john.doe')
    ->where('domain_uuid', 'domain-uuid')
    ->first();

// Get user's extensions
$extensions = $user->extensions;

// Check user's groups
foreach ($user->groups as $group) {
    echo $group->group_name;
}
```

### Working with Call Detail Records (CDR)

```php
use FusionPBX\Models\XmlCdr;

// Get CDRs for today
$today = date('Y-m-d 00:00:00');
$cdrs = XmlCdr::forDomain('domain-uuid')
    ->where('start_stamp', '>=', $today)
    ->orderBy('start_stamp', 'desc')
    ->get();

// Get CDRs by date range
$cdrs = XmlCdr::dateRange('2024-01-01', '2024-01-31')
    ->forDomain('domain-uuid')
    ->get();

// Get inbound calls only
$inbound = XmlCdr::direction('inbound')
    ->forDomain('domain-uuid')
    ->get();

// Calculate total call duration
$totalDuration = XmlCdr::forDomain('domain-uuid')
    ->whereDate('start_stamp', today())
    ->sum('billsec');

// Get CDRs with extension info
$cdrs = XmlCdr::with('extension')
    ->forDomain('domain-uuid')
    ->limit(100)
    ->get();
```

### Working with Call Center

```php
use FusionPBX\Models\CallCenterQueue;
use FusionPBX\Models\CallCenterAgent;

// Get all queues
$queues = CallCenterQueue::forDomain('domain-uuid')->get();

// Get queue with agents
$queue = CallCenterQueue::with('tiers.agent')
    ->find('queue-uuid');

// Get all agents
$agents = CallCenterAgent::forDomain('domain-uuid')->get();

// Get agent with user info
$agent = CallCenterAgent::with('user')
    ->find('agent-uuid');
```

### Working with Conferences

```php
use FusionPBX\Models\Conference;

// Get all conferences
$conferences = Conference::forDomain('domain-uuid')
    ->enabled()
    ->get();

// Get conference with sessions
$conference = Conference::with('sessions')
    ->find('conference-uuid');

// Create new conference
$conference = Conference::create([
    'conference_uuid' => 'new-uuid',
    'domain_uuid' => 'domain-uuid',
    'conference_name' => 'Sales Team',
    'conference_extension' => '9001',
    'conference_pin_number' => '1234',
    'conference_enabled' => true
]);
```

### Working with IVR Menus

```php
use FusionPBX\Models\IvrMenu;

// Get all IVR menus
$ivrs = IvrMenu::forDomain('domain-uuid')
    ->enabled()
    ->get();

// Get IVR with options
$ivr = IvrMenu::with('options')
    ->find('ivr-uuid');
```

### Advanced Queries

```php
// Complex filtering
$extensions = Extension::forDomain('domain-uuid')
    ->where('enabled', true)
    ->where(function($query) {
        $query->where('extension', 'like', '10%')
              ->orWhere('number_alias', 'like', '10%');
    })
    ->orderBy('extension')
    ->paginate(50);

// Aggregations
$stats = [
    'total_extensions' => Extension::forDomain('domain-uuid')->count(),
    'enabled_extensions' => Extension::forDomain('domain-uuid')->enabled()->count(),
    'total_users' => User::forDomain('domain-uuid')->count(),
    'active_users' => User::forDomain('domain-uuid')->enabled()->count(),
];

// Eager loading to avoid N+1 queries
$domains = Domain::with([
    'users' => function($query) {
        $query->enabled();
    },
    'extensions' => function($query) {
        $query->enabled()->orderBy('extension');
    }
])->get();

// Raw queries when needed
$results = \Illuminate\Database\Capsule\Manager::table('v_extensions')
    ->select('domain_uuid', \Illuminate\Database\Capsule\Manager::raw('count(*) as total'))
    ->groupBy('domain_uuid')
    ->get();
```

### Using Query Scopes

All models extending `BaseModel` have these built-in scopes:

```php
// Filter by domain
Extension::forDomain('domain-uuid')->get();

// Filter enabled records (where enabled = 'true')
Extension::enabled()->get();

// Filter disabled records (where enabled = 'false')
Extension::disabled()->get();

// Combine scopes
Extension::forDomain('domain-uuid')
    ->enabled()
    ->get();
```

### Relationships

Models have pre-configured relationships for easy data access:

```php
// Domain relationships
$domain = Domain::find('uuid');
$domain->users;      // Get all users
$domain->extensions; // Get all extensions
$domain->devices;    // Get all devices
$domain->gateways;   // Get all gateways

// User relationships
$user = User::find('uuid');
$user->domain;     // Get user's domain
$user->contact;    // Get user's contact
$user->groups;     // Get user's groups
$user->extensions; // Get user's extensions

// Extension relationships
$extension = Extension::find('uuid');
$extension->domain;      // Get extension's domain
$extension->users;       // Get associated users
$extension->voicemail;   // Get voicemail box
$extension->deviceLines; // Get device lines
$extension->followMe;    // Get follow-me settings

// CDR relationships
$cdr = XmlCdr::find('uuid');
$cdr->domain;    // Get CDR's domain
$cdr->extension; // Get associated extension
```

## Best Practices

### 1. Always Filter by Domain

For multi-tenant security, always filter queries by domain:

```php
// Good
$extensions = Extension::forDomain($domainUuid)->get();

// Avoid
$extensions = Extension::all(); // Returns data from all domains
```

### 2. Use Eager Loading

Prevent N+1 query problems by eager loading relationships:

```php
// Good - Single query with joins
$extensions = Extension::with(['users', 'voicemail'])->get();

// Avoid - N+1 queries
$extensions = Extension::all();
foreach ($extensions as $ext) {
    $ext->users; // Triggers additional query
}
```

### 3. Use Transactions for Complex Operations

```php
use Illuminate\Database\Capsule\Manager as DB;

DB::beginTransaction();
try {
    $extension = Extension::create([...]);
    $voicemail = Voicemail::create([...]);
    // More operations...
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    throw $e;
}
```

### 4. Validate Input

Always validate and sanitize input before database operations:

```php
// Validate UUID format
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
    throw new InvalidArgumentException('Invalid UUID format');
}
```

## Integration with Existing FusionPBX Code

You can use Eloquent models alongside existing FusionPBX code:

```php
// In any FusionPBX PHP file
require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');

use FusionPBX\Models\Extension;

// Use Eloquent
$extension = Extension::find($_REQUEST['id']);

// Continue with existing FusionPBX code
// ...
```

## Performance Tips

1. **Use select() to limit columns**:
   ```php
   Extension::select('extension_uuid', 'extension', 'description')
       ->get();
   ```

2. **Use pagination for large datasets**:
   ```php
   $extensions = Extension::forDomain($domainUuid)
       ->paginate(50);
   ```

3. **Use indexes** - FusionPBX tables already have proper indexes

4. **Cache frequently accessed data**:
   ```php
   // Implement caching layer if needed
   ```

## Troubleshooting

### Database Connection Issues

If you encounter connection issues:

1. Check database configuration in `/etc/fusionpbx/config.php`
2. Verify database credentials
3. Ensure database server is running
4. Check PostgreSQL/MySQL logs

### Model Not Found

Ensure you've included the bootstrap file and used the correct namespace:

```php
require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');
use FusionPBX\Models\Extension; // Correct namespace
```

### UUID Issues

FusionPBX uses UUIDs as primary keys. When creating records:

```php
// Generate UUID (PHP 7.0+)
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);
```

## Additional Resources

- [Laravel Eloquent Documentation](https://laravel.com/docs/eloquent)
- [FusionPBX Documentation](https://docs.fusionpbx.com)
- [FusionPBX Database Schema](https://github.com/fusionpbx/fusionpbx)

## License

This integration follows the FusionPBX license (Mozilla Public License 1.1).

## Contributing

Contributions are welcome! Please ensure:
- Models follow FusionPBX naming conventions
- Relationships are properly defined
- Documentation is updated
- Code follows PSR standards

## Support

For issues related to:
- **Eloquent Models**: Open an issue in this repository
- **FusionPBX Core**: Visit [FusionPBX Support](https://www.fusionpbx.com/support)
