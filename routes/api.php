<?php

use App\Http\Controllers\API\AdminAuthenticationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/auth.php'; //edit image DONE       EDIT THE CATCH BLOCK TO PUT LOG!!
require __DIR__ . '/alumnilist.php';
require __DIR__ . '/announcement.php'; //edit image DONE
require __DIR__ . '/institute_course.php'; //edit image DONE
require __DIR__ . '/post.php'; //edit image DONE
require __DIR__ . '/survey.php';
require __DIR__ . '/accounts.php'; //edit image NOT SURE
require __DIR__ . '/profile.php'; //edit image DONE
require __DIR__ . '/analytics.php'; //edit image DONE
require __DIR__ . '/conversations.php';
require __DIR__ . '/career.php'; // CAREER TRACKING
require __DIR__ . '/jobfit.php'; // JOB FIT ANALYSIS

// FOR TESTING ONLY
Route::get('/qr-test', function () {
    return QrCode::size(300)->generate('https://example.com');
});

Route::post('/upload-image', function (Request $request) {
    $request->validate(['image' => 'required|image']);

    $cloudinary = new Cloudinary();

    $uploadResult = $cloudinary->uploadApi()->upload($request->file('image')->getRealPath());

    // Create URL with f_auto,q_auto transformations
    $optimizedUrl = $cloudinary->image($uploadResult['public_id'])
                              ->format('auto')
                              ->quality('auto')
                              ->toUrl();

    return response()->json([
        'original_url' => $uploadResult['secure_url'],
        'optimized_url' => $optimizedUrl,
    ]);
});

Route::get('/test', function (Request $request) {
    return response()->json(['message' => $request->header('User-Agent'),]);
});

Route::post('/test-admin', [AdminAuthenticationController::class, 'createAdmin']);

Route::get('/debug', function () {
    return 'Laravel is running!';
});

// Test environment variables
Route::get('/debug-env', function () {
    return [
        'APP_ENV' => env('APP_ENV'),
        'APP_DEBUG' => env('APP_DEBUG'),
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_DATABASE' => env('DB_DATABASE'),
    ];
});

// Test DB connection
Route::get('/debug-db', function () {
    try {
        DB::connection()->getPdo();
        return 'DB connection works!';
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
});

// Force a crash to show full error (Laravel must boot)
Route::get('/debug-error', function () {
    throw new \Exception('This is a test exception to see the full error.');
});


// Enhanced database debugging routes
Route::get('/debug-connection-details', function () {
    $config = config('database.connections.' . config('database.default'));
    return [
        'connection_name' => config('database.default'),
        'driver' => $config['driver'],
        'host' => $config['host'],
        'port' => $config['port'],
        'database' => $config['database'],
        'username' => $config['username'],
        'password' => $config['password'] ? '***HIDDEN***' : 'NOT SET',
        'ssl_mode' => $config['sslmode'] ?? 'not set',
        'options' => $config['options'] ?? [],
    ];
});

// Test raw socket connection to database host/port
Route::get('/debug-socket', function () {
    $host = config('database.connections.' . config('database.default') . '.host');
    $port = config('database.connections.' . config('database.default') . '.port');
    
    $connection = @fsockopen($host, $port, $errno, $errstr, 10);
    
    if ($connection) {
        fclose($connection);
        return [
            'status' => 'success',
            'message' => "Socket connection to {$host}:{$port} successful"
        ];
    } else {
        return [
            'status' => 'failed',
            'error_code' => $errno,
            'error_message' => $errstr,
            'host' => $host,
            'port' => $port
        ];
    }
});

// Test DNS resolution
Route::get('/debug-dns', function () {
    $host = config('database.connections.' . config('database.default') . '.host');
    
    $ip = gethostbyname($host);
    
    return [
        'hostname' => $host,
        'resolved_ip' => $ip,
        'dns_working' => $ip !== $host
    ];
});

// Test with different SSL modes
Route::get('/debug-ssl-modes', function () {
    $results = [];
    $originalConfig = config('database.connections.' . config('database.default'));
    
    $sslModes = ['disable', 'require', 'prefer'];
    
    foreach ($sslModes as $mode) {
        try {
            // Temporarily modify config
            config(['database.connections.' . config('database.default') . '.sslmode' => $mode]);
            
            // Clear any cached connections
            DB::purge();
            
            // Test connection
            DB::connection()->getPdo();
            $results[$mode] = 'SUCCESS';
        } catch (\Exception $e) {
            $results[$mode] = $e->getMessage();
        }
    }
    
    // Restore original config
    config(['database.connections.' . config('database.default') => $originalConfig]);
    DB::purge();
    
    return $results;
});

// Test connection with custom PDO options
Route::get('/debug-pdo-options', function () {
    $config = config('database.connections.' . config('database.default'));
    
    try {
        $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        // Try with SSL disabled
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        
        return [
            'status' => 'success',
            'message' => 'Direct PDO connection successful with SSL verification disabled'
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'dsn' => $dsn ?? 'DSN not created'
        ];
    }
});

// Check server environment and network
Route::get('/debug-server-info', function () {
    return [
        'php_version' => PHP_VERSION,
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'loaded_extensions' => get_loaded_extensions(),
        'pdo_drivers' => PDO::getAvailableDrivers(),
        'curl_version' => function_exists('curl_version') ? curl_version() : 'not available',
    ];
});


// Check raw environment variables vs Laravel config
Route::get('/debug-env-vars', function () {
    return [
        'raw_env_vars' => [
            'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? 'NOT SET',
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT SET',
            'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT SET',
            'DB_DATABASE' => $_ENV['DB_DATABASE'] ?? 'NOT SET',
            'DB_USERNAME' => $_ENV['DB_USERNAME'] ?? 'NOT SET',
            'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ? '***HIDDEN***' : 'NOT SET',
            'DB_SSLMODE' => $_ENV['DB_SSLMODE'] ?? 'NOT SET',
        ],
        'laravel_env_function' => [
            'DB_CONNECTION' => env('DB_CONNECTION', 'NOT SET'),
            'DB_HOST' => env('DB_HOST', 'NOT SET'),
            'DB_PORT' => env('DB_PORT', 'NOT SET'),
            'DB_DATABASE' => env('DB_DATABASE', 'NOT SET'),
            'DB_USERNAME' => env('DB_USERNAME', 'NOT SET'),
            'DB_PASSWORD' => env('DB_PASSWORD') ? '***HIDDEN***' : 'NOT SET',
            'DB_SSLMODE' => env('DB_SSLMODE', 'NOT SET'),
        ],
        'laravel_config' => [
            'default_connection' => config('database.default'),
            'mysql_config' => config('database.connections.mysql'),
        ],
        'app_env' => env('APP_ENV'),
        'config_cached' => app()->configurationIsCached(),
    ];
});

// Test direct connection with environment variables
Route::get('/debug-direct-env-connection', function () {
    try {
        $host = env('DB_HOST');
        $port = env('DB_PORT');
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        
        if (!$host || !$database || !$username || !$password) {
            return [
                'status' => 'failed',
                'error' => 'Missing required environment variables',
                'missing' => [
                    'DB_HOST' => !$host,
                    'DB_DATABASE' => !$database,
                    'DB_USERNAME' => !$username,
                    'DB_PASSWORD' => !$password,
                ]
            ];
        }
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        
        return [
            'status' => 'success',
            'message' => 'Direct connection with env vars successful',
            'host' => $host,
            'database' => $database,
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'host' => $host ?? 'not set',
            'database' => $database ?? 'not set',
        ];
    }
});

// Clear config cache (useful for debugging)
Route::get('/debug-clear-config', function () {
    try {
        Artisan::call('config:clear');
        return [
            'status' => 'success',
            'message' => 'Configuration cache cleared successfully'
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
});

Route::get('/debug-throttle', function () {
    return [
        'status' => 'success',
        'message' => 'You have reached this endpoint successfully'
    ];
})->middleware('throttle:5,1');

Route::get('/env-debug', function(Request $request) {
    Log::info('DONWWWWWWWWWWWWWWWWWWWWWWWWWWWWWW');
    return [
        'CACHE_STORE' => env('CACHE_STORE'),
        'DB_CACHE_TABLE' => env('DB_CACHE_TABLE'), 
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'APP_ENV' => env('APP_ENV'),
        'config_cache_default' => config('cache.default'),
        'all_cache_stores' => array_keys(config('cache.stores')),
        'frontend_url' => env('FRONTEND_URL'),
        'cookie' => $request->cookie('auth_token'),
    ];
});


Route::middleware('ctb')->get('/trytry', function (Request $request) {
    return response()->json([
        'response_code' => 200,
        'status' => 'success',
        'message' => 'CBT Middleware: This is a test message from CBT middleware.',
    ], 200);
});