<?php
/**
 * FusionPBX - Laravel Eloquent Bootstrap
 * 
 * This file initializes Laravel Eloquent ORM for use with FusionPBX database.
 * Include this file before using any Eloquent models.
 * 
 * Usage:
 *   require_once(__DIR__ . '/app/models/eloquent_bootstrap.php');
 *   
 * @package    FusionPBX
 * @subpackage Models
 */

// Load Composer autoloader
require_once(__DIR__ . '/../../vendor/autoload.php');

// Load FusionPBX database configuration
require_once(__DIR__ . '/../../resources/pdo.php');

use Illuminate\Database\Capsule\Manager as Capsule;

// Create a new Database Capsule Manager instance
$capsule = new Capsule;

// Get database configuration from FusionPBX
if (!isset($db)) {
    // If $db is not set, try to load from config
    if (file_exists('/etc/fusionpbx/config.php')) {
        require_once('/etc/fusionpbx/config.php');
    }
}

// Set up database connection based on FusionPBX configuration
if (isset($db) && is_array($db)) {
    $db_type = $db[0]['type'] ?? 'pgsql';
    $db_host = $db[0]['host'] ?? 'localhost';
    $db_port = $db[0]['port'] ?? ($db_type === 'pgsql' ? 5432 : 3306);
    $db_name = $db[0]['name'] ?? 'fusionpbx';
    $db_username = $db[0]['username'] ?? 'fusionpbx';
    $db_password = $db[0]['password'] ?? '';
    
    // Map FusionPBX database type to Laravel driver name
    $driver_map = [
        'pgsql' => 'pgsql',
        'mysql' => 'mysql',
        'sqlite' => 'sqlite'
    ];
    
    $driver = $driver_map[$db_type] ?? 'pgsql';
    
    // Configure the connection
    $config = [
        'driver'    => $driver,
        'host'      => $db_host,
        'port'      => $db_port,
        'database'  => $db_name,
        'username'  => $db_username,
        'password'  => $db_password,
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ];
    
    // Add PostgreSQL specific options
    if ($driver === 'pgsql') {
        $config['schema'] = 'public';
        $config['sslmode'] = 'prefer';
    }
    
    // Add MySQL specific options
    if ($driver === 'mysql') {
        $config['strict'] = false;
        $config['engine'] = null;
    }
    
    $capsule->addConnection($config);
} else {
    // Default PostgreSQL configuration
    $capsule->addConnection([
        'driver'    => 'pgsql',
        'host'      => 'localhost',
        'port'      => 5432,
        'database'  => 'fusionpbx',
        'username'  => 'fusionpbx',
        'password'  => '',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
        'schema'    => 'public',
        'sslmode'   => 'prefer',
    ]);
}

// Set the event dispatcher used by Eloquent models
$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher(new Illuminate\Container\Container));

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Boot Eloquent
$capsule->bootEloquent();

return $capsule;
