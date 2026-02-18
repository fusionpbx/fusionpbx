<?php
/**
 * FusionPBX - Filament Admin Panel Bootstrap
 * 
 * Initialize Filament Admin Panel for FusionPBX.
 * This file sets up the Filament admin panel with Laravel components.
 * 
 * @package    FusionPBX
 * @subpackage Filament
 */

// Load Eloquent bootstrap first
require_once(__DIR__ . '/../eloquent_bootstrap.php');

// Ensure vendor autoload is included
$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    die('Composer dependencies not installed. Please run: composer install');
}

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Factory as ViewFactory;

// Create application container
$app = Container::getInstance();

// Set up events dispatcher
$events = new Dispatcher($app);
$app->instance('events', $events);

// Set up filesystem
$filesystem = new Filesystem;
$app->instance('files', $filesystem);

// Set up view factory for Blade templates
$viewPaths = [
    __DIR__ . '/../../resources/views',
    __DIR__ . '/../views',
];

// Create cache directory for compiled views if it doesn't exist
$cachePath = __DIR__ . '/../../storage/framework/views';
if (!is_dir($cachePath)) {
    @mkdir($cachePath, 0755, true);
}

$viewResolver = new EngineResolver;

// Register PHP engine
$viewResolver->register('php', function () {
    return new PhpEngine;
});

// Register Blade engine
$viewResolver->register('blade', function () use ($filesystem, $cachePath) {
    $compiler = new BladeCompiler($filesystem, $cachePath);
    return new CompilerEngine($compiler);
});

$viewFinder = new FileViewFinder($filesystem, $viewPaths);
$viewFactory = new ViewFactory($viewResolver, $viewFinder, $events);

$app->instance('view', $viewFactory);

// Load Filament Panel Provider
try {
    $panelProvider = new FusionPBX\Filament\AdminPanelProvider();
    // Additional Filament setup would go here
} catch (Exception $e) {
    // If Filament is not fully installed, show error
    if (php_sapi_name() === 'cli') {
        echo "Filament not fully installed. Run: composer install\n";
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        echo "<h1>Filament Admin Panel</h1>";
        echo "<p>Dependencies not installed. Please run: <code>composer install</code></p>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit(1);
}

// Return the app instance for use in other files
return $app;
