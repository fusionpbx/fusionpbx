<?php
/**
 * FusionPBX - Eloquent Models Usage Examples
 * 
 * This file demonstrates practical usage of Laravel Eloquent models
 * with FusionPBX database.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

// Include the bootstrap file to initialize Eloquent
require_once(__DIR__ . '/eloquent_bootstrap.php');

// Import the models you need
use FusionPBX\Models\Domain;
use FusionPBX\Models\Extension;
use FusionPBX\Models\User;
use FusionPBX\Models\XmlCdr;
use FusionPBX\Models\CallCenterQueue;
use FusionPBX\Models\Conference;
use Illuminate\Database\Capsule\Manager as DB;

// =============================================================================
// Example 1: List all domains
// =============================================================================
echo "=== Example 1: List all domains ===\n";
$domains = Domain::all();
foreach ($domains as $domain) {
    echo "Domain: {$domain->domain_name} (UUID: {$domain->domain_uuid})\n";
}
echo "\n";

// Get a domain UUID for examples or skip domain-specific examples
$domain = Domain::first();
$domainUuid = $domain ? $domain->domain_uuid : null;

if (!$domainUuid) {
    echo "⚠ WARNING: No domains found in database.\n";
    echo "   Many examples require a domain UUID to function.\n";
    echo "   Some examples will be skipped or may fail.\n";
    echo "   Please configure your database before running examples.\n\n";
}

// =============================================================================
// Example 2: Get extensions for a specific domain
// =============================================================================
echo "=== Example 2: Get enabled extensions for a domain ===\n";

if (!$domainUuid) {
    echo "⚠ Skipping - No domains found in database\n\n";
} else {
    
    $extensions = Extension::forDomain($domainUuid)
        ->enabled()
        ->orderBy('extension')
        ->get();

    echo "Found {$extensions->count()} enabled extensions\n";
    foreach ($extensions->take(5) as $ext) {
        echo "Extension {$ext->extension}: {$ext->description}\n";
    }
}
echo "\n";

// =============================================================================
// Example 3: Get user with related data
// =============================================================================
echo "=== Example 3: Get user with related data ===\n";
$user = User::with(['domain', 'groups', 'extensions'])
    ->where('username', 'admin')
    ->first();

if ($user) {
    echo "Username: {$user->username}\n";
    echo "Domain: {$user->domain->domain_name}\n";
    echo "Groups: ";
    foreach ($user->groups as $group) {
        echo "{$group->group_name}, ";
    }
    echo "\n";
    echo "Extensions: ";
    foreach ($user->extensions as $ext) {
        echo "{$ext->extension}, ";
    }
    echo "\n";
}
echo "\n";

// =============================================================================
// Example 4: Get today's call statistics
// =============================================================================
echo "=== Example 4: Today's Call Statistics ===\n";
$today = date('Y-m-d 00:00:00');

$stats = [
    'total_calls' => XmlCdr::where('start_stamp', '>=', $today)->count(),
    'answered_calls' => XmlCdr::where('start_stamp', '>=', $today)
        ->whereNotNull('answer_stamp')
        ->count(),
    'total_duration' => XmlCdr::where('start_stamp', '>=', $today)
        ->sum('billsec'),
    'avg_duration' => XmlCdr::where('start_stamp', '>=', $today)
        ->whereNotNull('answer_stamp')
        ->avg('billsec'),
];

echo "Total calls: {$stats['total_calls']}\n";
echo "Answered calls: {$stats['answered_calls']}\n";
echo "Total duration: " . round($stats['total_duration'] / 60, 2) . " minutes\n";
echo "Avg duration: " . round($stats['avg_duration'], 2) . " seconds\n";
echo "\n";

// =============================================================================
// Example 5: Find extensions by number pattern
// =============================================================================
echo "=== Example 5: Find extensions starting with '10' ===\n";
$extensions = Extension::forDomain($domainUuid)
    ->where('extension', 'like', '10%')
    ->enabled()
    ->get();

echo "Found {$extensions->count()} extensions\n";
foreach ($extensions->take(3) as $ext) {
    echo "Extension: {$ext->extension}\n";
}
echo "\n";

// =============================================================================
// Example 6: Create a new extension (commented out for safety)
// =============================================================================
echo "=== Example 6: Create a new extension (example) ===\n";
echo "// Commented out for safety - uncomment to use\n";
/*
$newExtension = Extension::create([
    'extension_uuid' => 'new-uuid-here',
    'domain_uuid' => $domainUuid,
    'extension' => '1099',
    'password' => 'secure-password-here',
    'enabled' => true,
    'description' => 'Test Extension',
    'user_context' => 'default',
]);
echo "Created extension: {$newExtension->extension}\n";
*/
echo "\n";

// =============================================================================
// Example 7: Update extension settings
// =============================================================================
echo "=== Example 7: Update extension settings (example) ===\n";
echo "// Commented out for safety - uncomment to use\n";
/*
$extension = Extension::where('extension', '1099')
    ->where('domain_uuid', $domainUuid)
    ->first();

if ($extension) {
    $extension->update([
        'description' => 'Updated Description',
        'call_timeout' => 30,
    ]);
    echo "Updated extension {$extension->extension}\n";
}
*/
echo "\n";

// =============================================================================
// Example 8: Get call center queue with agents
// =============================================================================
echo "=== Example 8: Get call center queues with agents ===\n";
$queues = CallCenterQueue::forDomain($domainUuid)
    ->with('tiers.agent')
    ->get();

echo "Found {$queues->count()} call center queues\n";
foreach ($queues as $queue) {
    echo "Queue: {$queue->queue_name}\n";
    echo "  Agents: {$queue->tiers->count()}\n";
}
echo "\n";

// =============================================================================
// Example 9: Get conference rooms
// =============================================================================
echo "=== Example 9: Get conference rooms ===\n";
$conferences = Conference::forDomain($domainUuid)
    ->enabled()
    ->orderBy('conference_name')
    ->get();

echo "Found {$conferences->count()} conference rooms\n";
foreach ($conferences as $conf) {
    echo "Conference: {$conf->conference_name} (Ext: {$conf->conference_extension})\n";
}
echo "\n";

// =============================================================================
// Example 10: Complex query with joins
// =============================================================================
echo "=== Example 10: Extensions with user count ===\n";
$extensionsWithUsers = Extension::forDomain($domainUuid)
    ->with('users')
    ->get()
    ->map(function($ext) {
        return [
            'extension' => $ext->extension,
            'description' => $ext->description,
            'user_count' => $ext->users->count(),
        ];
    })
    ->take(5);

foreach ($extensionsWithUsers as $data) {
    echo "Extension {$data['extension']}: {$data['user_count']} user(s)\n";
}
echo "\n";

// =============================================================================
// Example 11: Transaction example
// =============================================================================
echo "=== Example 11: Transaction example ===\n";
echo "// Commented out for safety - uncomment to use\n";
/*
DB::beginTransaction();
try {
    // Create extension
    $extension = Extension::create([
        'extension_uuid' => 'uuid-1',
        'domain_uuid' => $domainUuid,
        'extension' => '2001',
        'password' => 'password123',
        'enabled' => true,
    ]);
    
    // Create associated voicemail
    $voicemail = Voicemail::create([
        'voicemail_uuid' => 'uuid-2',
        'domain_uuid' => $domainUuid,
        'voicemail_id' => '2001',
        'voicemail_password' => '1234',
        'voicemail_enabled' => true,
    ]);
    
    DB::commit();
    echo "Transaction completed successfully\n";
} catch (\Exception $e) {
    DB::rollback();
    echo "Transaction failed: " . $e->getMessage() . "\n";
}
*/
echo "\n";

// =============================================================================
// Example 12: Get recent CDRs with extension info
// =============================================================================
echo "=== Example 12: Get 10 most recent CDRs ===\n";
$recentCdrs = XmlCdr::forDomain($domainUuid)
    ->with('extension')
    ->orderBy('start_stamp', 'desc')
    ->limit(10)
    ->get();

echo "Recent calls:\n";
foreach ($recentCdrs as $cdr) {
    $duration = round($cdr->billsec / 60, 2);
    echo "  {$cdr->start_stamp}: {$cdr->caller_id_number} -> {$cdr->destination_number} ({$duration} min)\n";
}
echo "\n";

// =============================================================================
// Example 13: Pagination example
// =============================================================================
echo "=== Example 13: Pagination example ===\n";
$page = 1;
$perPage = 10;

$extensions = Extension::forDomain($domainUuid)
    ->enabled()
    ->orderBy('extension')
    ->paginate($perPage, ['*'], 'page', $page);

echo "Showing page {$page} of extensions\n";
echo "Total: {$extensions->total()} extensions\n";
echo "Per page: {$perPage}\n";
echo "Current page items: {$extensions->count()}\n";
echo "\n";

// =============================================================================
// Example 14: Aggregation queries
// =============================================================================
echo "=== Example 14: Domain statistics ===\n";
$stats = [
    'domains' => Domain::count(),
    'extensions' => Extension::forDomain($domainUuid)->count(),
    'enabled_extensions' => Extension::forDomain($domainUuid)->enabled()->count(),
    'users' => User::forDomain($domainUuid)->count(),
    'enabled_users' => User::forDomain($domainUuid)->enabled()->count(),
    'devices' => Device::forDomain($domainUuid)->count(),
];

foreach ($stats as $key => $value) {
    echo ucfirst($key) . ": {$value}\n";
}
echo "\n";

echo "=== Examples completed ===\n";
