# FusionPBX Laravel Eloquent - Quick Start Guide

This guide will help you get started with Laravel Eloquent models in FusionPBX in under 5 minutes.

## Installation

Dependencies are already installed via Composer. If you need to reinstall:

```bash
cd /path/to/fusionpbx
composer install
```

## Basic Usage

### 1. Include the Bootstrap

At the top of any PHP file where you want to use Eloquent:

```php
<?php
require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');

use FusionPBX\Models\Extension;
use FusionPBX\Models\Domain;
use FusionPBX\Models\User;
```

### 2. Simple Queries

```php
// Get all extensions
$extensions = Extension::all();

// Get extensions for a specific domain
$extensions = Extension::forDomain($domain_uuid)
    ->enabled()
    ->get();

// Find by primary key
$extension = Extension::find($extension_uuid);

// Find by extension number
$extension = Extension::where('extension', '1001')
    ->where('domain_uuid', $domain_uuid)
    ->first();

// Count extensions
$count = Extension::forDomain($domain_uuid)->count();
```

### 3. Working with Relationships

```php
// Get extension with voicemail
$extension = Extension::with('voicemail')->find($uuid);
$voicemail = $extension->voicemail;

// Get user with all extensions
$user = User::with('extensions')->find($uuid);
foreach ($user->extensions as $ext) {
    echo $ext->extension;
}

// Get domain with users and extensions
$domain = Domain::with(['users', 'extensions'])->find($uuid);
```

### 4. Creating Records

```php
$extension = Extension::create([
    'extension_uuid' => 'your-uuid-here',
    'domain_uuid' => $domain_uuid,
    'extension' => '1002',
    'password' => 'secure-password',
    'enabled' => true,
    'description' => 'New Extension'
]);
```

### 5. Updating Records

```php
$extension = Extension::find($uuid);
$extension->update([
    'description' => 'Updated',
    'enabled' => true
]);

// Or use save()
$extension->description = 'Updated';
$extension->save();
```

### 6. Deleting Records

```php
$extension = Extension::find($uuid);
$extension->delete();

// Or delete by query
Extension::where('extension', '1099')->delete();
```

## Common Use Cases

### Get Today's Call Statistics

```php
use FusionPBX\Models\XmlCdr;

$today = date('Y-m-d 00:00:00');
$stats = [
    'total_calls' => XmlCdr::where('start_stamp', '>=', $today)->count(),
    'total_duration' => XmlCdr::where('start_stamp', '>=', $today)->sum('billsec'),
    'avg_duration' => XmlCdr::where('start_stamp', '>=', $today)->avg('billsec'),
];
```

### List Call Center Queues

```php
use FusionPBX\Models\CallCenterQueue;

$queues = CallCenterQueue::forDomain($domain_uuid)
    ->with('tiers.agent')
    ->get();

foreach ($queues as $queue) {
    echo "Queue: {$queue->queue_name}\n";
    echo "Agents: {$queue->tiers->count()}\n";
}
```

### Search Extensions

```php
$extensions = Extension::forDomain($domain_uuid)
    ->where('extension', 'like', '10%')
    ->enabled()
    ->orderBy('extension')
    ->get();
```

### Pagination

```php
$extensions = Extension::forDomain($domain_uuid)
    ->enabled()
    ->orderBy('extension')
    ->paginate(50);

echo "Total: {$extensions->total()}\n";
foreach ($extensions as $ext) {
    echo $ext->extension . "\n";
}
```

## Advanced Features

### Query Scopes

All models have these built-in scopes:

- `forDomain($uuid)` - Filter by domain
- `enabled()` - Filter enabled records
- `disabled()` - Filter disabled records

```php
// Combine scopes
$extensions = Extension::forDomain($domain_uuid)
    ->enabled()
    ->get();
```

### Transactions

```php
use Illuminate\Database\Capsule\Manager as DB;

DB::beginTransaction();
try {
    $extension = Extension::create([...]);
    $voicemail = Voicemail::create([...]);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    throw $e;
}
```

### Eager Loading (Prevent N+1 Queries)

```php
// Bad - N+1 queries
$extensions = Extension::all();
foreach ($extensions as $ext) {
    echo $ext->domain->domain_name; // Triggers query for each extension
}

// Good - Single query with joins
$extensions = Extension::with('domain')->get();
foreach ($extensions as $ext) {
    echo $ext->domain->domain_name; // No additional queries
}
```

## Available Models

- **Core**: Domain, User, Group, Contact
- **Communication**: Extension, Voicemail, Gateway, Device, Dialplan
- **Call Center**: CallCenterQueue, CallCenterAgent
- **Conference**: Conference
- **IVR**: IvrMenu, RingGroup
- **Media**: Recording, MusicOnHold, Fax
- **CDR**: XmlCdr
- **And 40+ more supporting models**

## Testing

Run the test suite:

```bash
php app/models/test.php
```

## Getting Help

- **Documentation**: See `app/models/README.md`
- **Examples**: See `app/models/examples.php`
- **Laravel Eloquent Docs**: https://laravel.com/docs/eloquent

## Tips

1. Always filter by domain for multi-tenant security
2. Use eager loading to prevent N+1 queries
3. Use transactions for multiple related operations
4. Use pagination for large datasets
5. Validate UUIDs before querying

## Integration with Existing Code

You can use Eloquent alongside existing FusionPBX code:

```php
// In any FusionPBX file
require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');
use FusionPBX\Models\Extension;

// Use Eloquent
$extensions = Extension::forDomain($_SESSION['domain_uuid'])
    ->enabled()
    ->get();

// Continue with regular FusionPBX code
foreach ($extensions as $ext) {
    // Process extension
}
```

## Next Steps

1. Run `php app/models/test.php` to verify installation
2. Read `app/models/README.md` for comprehensive documentation
3. Check `app/models/examples.php` for more usage examples
4. Start using Eloquent in your FusionPBX applications!

---

**Note**: Make sure your database is properly configured in `/etc/fusionpbx/config.php` or `resources/config.php` before using these models.
