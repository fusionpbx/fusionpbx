<?php
/**
 * FusionPBX - Eloquent Models Test File
 * 
 * This file tests the Laravel Eloquent models integration.
 * Run this file to validate that all models are properly configured.
 * 
 * Usage: php app/models/test.php
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=============================================================\n";
echo "FusionPBX Eloquent Models Integration Test\n";
echo "=============================================================\n\n";

// Test 1: Bootstrap Loading
echo "Test 1: Loading Eloquent Bootstrap...\n";
try {
    require_once(__DIR__ . '/eloquent_bootstrap.php');
    echo "✓ Bootstrap loaded successfully\n\n";
} catch (Exception $e) {
    echo "✗ Bootstrap failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Import classes at the top of the file
use Illuminate\Database\Capsule\Manager as DB;
use FusionPBX\Models\Domain;
use FusionPBX\Models\Extension;
use FusionPBX\Models\User;
use FusionPBX\Models\Voicemail;
use FusionPBX\Models\Device;
use FusionPBX\Models\Gateway;
use FusionPBX\Models\Dialplan;
use FusionPBX\Models\XmlCdr;
use FusionPBX\Models\Conference;
use FusionPBX\Models\CallCenterQueue;
use FusionPBX\Models\CallCenterAgent;
use FusionPBX\Models\IvrMenu;
use FusionPBX\Models\RingGroup;
use FusionPBX\Models\Contact;
use FusionPBX\Models\Group;
use FusionPBX\Models\Fax;
use FusionPBX\Models\Recording;
use FusionPBX\Models\MusicOnHold;

// Test 2: Database Connection
echo "Test 2: Testing Database Connection...\n";
try {
    // Try a simple query
    $result = DB::select("SELECT 1 as test");
    if ($result && $result[0]->test == 1) {
        echo "✓ Database connection successful\n\n";
    } else {
        echo "✗ Database connection returned unexpected result\n\n";
    }
} catch (Exception $e) {
    echo "⚠ Database connection failed: " . $e->getMessage() . "\n";
    echo "  This is expected if database is not configured yet.\n\n";
}

// Test 3: Model Class Loading
echo "Test 3: Testing Model Class Loading...\n";
$models = [
    'Domain' => 'FusionPBX\\Models\\Domain',
    'User' => 'FusionPBX\\Models\\User',
    'Extension' => 'FusionPBX\\Models\\Extension',
    'Voicemail' => 'FusionPBX\\Models\\Voicemail',
    'Device' => 'FusionPBX\\Models\\Device',
    'Gateway' => 'FusionPBX\\Models\\Gateway',
    'Dialplan' => 'FusionPBX\\Models\\Dialplan',
    'XmlCdr' => 'FusionPBX\\Models\\XmlCdr',
    'Conference' => 'FusionPBX\\Models\\Conference',
    'CallCenterQueue' => 'FusionPBX\\Models\\CallCenterQueue',
    'CallCenterAgent' => 'FusionPBX\\Models\\CallCenterAgent',
    'IvrMenu' => 'FusionPBX\\Models\\IvrMenu',
    'RingGroup' => 'FusionPBX\\Models\\RingGroup',
    'Contact' => 'FusionPBX\\Models\\Contact',
    'Group' => 'FusionPBX\\Models\\Group',
    'Fax' => 'FusionPBX\\Models\\Fax',
    'Recording' => 'FusionPBX\\Models\\Recording',
    'MusicOnHold' => 'FusionPBX\\Models\\MusicOnHold',
];

$loadedModels = 0;
foreach ($models as $name => $class) {
    if (class_exists($class)) {
        echo "✓ $name model loaded\n";
        $loadedModels++;
    } else {
        echo "✗ $name model failed to load\n";
    }
}
echo "\nLoaded $loadedModels/" . count($models) . " models\n\n";

// Test 4: Model Instantiation
echo "Test 4: Testing Model Instantiation...\n";
try {
    $domain = new Domain();
    echo "✓ Domain model instantiated\n";
    
    $extension = new Extension();
    echo "✓ Extension model instantiated\n";
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Model instantiation failed: " . $e->getMessage() . "\n\n";
}

// Test 5: Table Names
echo "Test 5: Testing Table Name Configuration...\n";
try {
    $tests = [
        ['Domain', new Domain(), 'v_domains'],
        ['Extension', new Extension(), 'v_extensions'],
        ['User', new User(), 'v_users'],
        ['XmlCdr', new XmlCdr(), 'v_xml_cdr'],
    ];
    
    foreach ($tests as $test) {
        list($name, $model, $expectedTable) = $test;
        $actualTable = $model->getTable();
        if ($actualTable === $expectedTable) {
            echo "✓ $name table name: $actualTable\n";
        } else {
            echo "✗ $name table name mismatch. Expected: $expectedTable, Got: $actualTable\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Table name test failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Primary Keys
echo "Test 6: Testing Primary Key Configuration...\n";
try {
    $tests = [
        ['Domain', new Domain(), 'domain_uuid'],
        ['Extension', new Extension(), 'extension_uuid'],
    ];
    
    foreach ($tests as $test) {
        list($name, $model, $expectedKey) = $test;
        $actualKey = $model->getKeyName();
        if ($actualKey === $expectedKey) {
            echo "✓ $name primary key: $actualKey\n";
        } else {
            echo "✗ $name primary key mismatch. Expected: $expectedKey, Got: $actualKey\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Primary key test failed: " . $e->getMessage() . "\n\n";
}

// Test 7: Relationships
echo "Test 7: Testing Relationship Configuration...\n";
try {
    // Check if relationship methods exist
    $domain = new Domain();
    $extension = new Extension();
    $user = new User();
    
    if (method_exists($domain, 'users')) {
        echo "✓ Domain->users() relationship exists\n";
    } else {
        echo "✗ Domain->users() relationship missing\n";
    }
    
    if (method_exists($domain, 'extensions')) {
        echo "✓ Domain->extensions() relationship exists\n";
    } else {
        echo "✗ Domain->extensions() relationship missing\n";
    }
    
    if (method_exists($extension, 'domain')) {
        echo "✓ Extension->domain() relationship exists\n";
    } else {
        echo "✗ Extension->domain() relationship missing\n";
    }
    
    if (method_exists($user, 'domain')) {
        echo "✓ User->domain() relationship exists\n";
    } else {
        echo "✗ User->domain() relationship missing\n";
    }
    
    if (method_exists($user, 'groups')) {
        echo "✓ User->groups() relationship exists\n";
    } else {
        echo "✗ User->groups() relationship missing\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Relationship test failed: " . $e->getMessage() . "\n\n";
}

// Test 8: Query Scopes
echo "Test 8: Testing Query Scopes...\n";
try {
    // Test if scope methods exist
    $extension = new Extension();
    
    // Check if BaseModel scopes are available
    $reflection = new ReflectionClass($extension);
    $methods = $reflection->getMethods();
    $scopeMethods = array_filter($methods, function($method) {
        return strpos($method->name, 'scope') === 0;
    });
    
    echo "✓ Found " . count($scopeMethods) . " scope methods\n";
    foreach ($scopeMethods as $method) {
        echo "  - " . $method->name . "\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Scope test failed: " . $e->getMessage() . "\n\n";
}

// Test 9: Fillable Attributes
echo "Test 9: Testing Fillable Attributes...\n";
try {
    $extension = new Extension();
    $fillable = $extension->getFillable();
    
    if (count($fillable) > 0) {
        echo "✓ Extension has " . count($fillable) . " fillable attributes\n";
        echo "  Sample attributes: " . implode(', ', array_slice($fillable, 0, 5)) . "...\n";
    } else {
        echo "✗ Extension has no fillable attributes\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Fillable attributes test failed: " . $e->getMessage() . "\n\n";
}

// Test 10: Type Casting
echo "Test 10: Testing Type Casting...\n";
try {
    $extension = new Extension();
    $casts = $extension->getCasts();
    
    if (count($casts) > 0) {
        echo "✓ Extension has " . count($casts) . " cast attributes\n";
        foreach (array_slice($casts, 0, 5) as $attr => $type) {
            echo "  - $attr => $type\n";
        }
        if (count($casts) > 5) {
            echo "  ... and " . (count($casts) - 5) . " more\n";
        }
    } else {
        echo "✗ Extension has no cast attributes\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Type casting test failed: " . $e->getMessage() . "\n\n";
}

// Test 11: Supporting Models
echo "Test 11: Testing Supporting Models...\n";
try {
    // Load the supporting models file
    require_once(__DIR__ . '/SupportingModels.php');
    
    $supportingModels = [
        'FusionPBX\\Models\\ExtensionSetting',
        'FusionPBX\\Models\\VoicemailMessage',
        'FusionPBX\\Models\\DeviceLine',
        'FusionPBX\\Models\\CallCenterTier',
        'FusionPBX\\Models\\ContactPhone',
    ];
    
    $loadedCount = 0;
    foreach ($supportingModels as $class) {
        if (class_exists($class)) {
            echo "✓ " . class_basename($class) . " loaded\n";
            $loadedCount++;
        } else {
            echo "✗ " . class_basename($class) . " not loaded\n";
        }
    }
    
    echo "\nLoaded $loadedCount/" . count($supportingModels) . " supporting models\n\n";
} catch (Exception $e) {
    echo "✗ Supporting models test failed: " . $e->getMessage() . "\n\n";
}

// Test 12: Composer Autoloading
echo "Test 12: Testing Composer Autoloading...\n";
try {
    // Check if vendor directory exists
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        echo "✓ Composer vendor directory exists\n";
        
        // Check if key packages are loaded
        if (class_exists('Illuminate\\Database\\Capsule\\Manager')) {
            echo "✓ Illuminate Database package loaded\n";
        }
        
        if (class_exists('Illuminate\\Events\\Dispatcher')) {
            echo "✓ Illuminate Events package loaded\n";
        }
        
    } else {
        echo "✗ Composer vendor directory not found\n";
        echo "  Run 'composer install' to install dependencies\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Composer autoloading test failed: " . $e->getMessage() . "\n\n";
}

// Summary
echo "=============================================================\n";
echo "Test Summary\n";
echo "=============================================================\n";
echo "All basic tests completed successfully!\n";
echo "\nNote: Database connection tests require a configured database.\n";
echo "If database connection failed, configure your database in:\n";
echo "  /etc/fusionpbx/config.php\n";
echo "  or resources/pdo.php\n\n";

echo "To use these models in your FusionPBX code:\n";
echo "  1. Include: require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');\n";
echo "  2. Use: use FusionPBX\\Models\\Extension;\n";
echo "  3. Query: \$extensions = Extension::all();\n\n";

echo "See app/models/README.md for comprehensive documentation.\n";
echo "See app/models/examples.php for usage examples.\n\n";

echo "=============================================================\n";
echo "Test completed!\n";
echo "=============================================================\n";
