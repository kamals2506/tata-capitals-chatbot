<?php

/**
 * websocket_server.php — Ratchet WebSocket Server Entry Point
 *
 * Starts a standalone WebSocket server on port 8282 for real-time
 * live-chat between customers and agents.
 *
 * USAGE (from project root):
 *   php websocket_server.php
 *
 * SUPERVISOR CONFIG (/etc/supervisor/conf.d/allcargo-ws.conf):
 *   [program:allcargo-ws]
 *   command=php /home/bpoc/public_html/Allcargo/websocket_server.php
 *   directory=/home/bpoc/public_html/Allcargo
 *   autostart=true
 *   autorestart=true
 *   stderr_logfile=/var/log/allcargo-ws.err.log
 *   stdout_logfile=/var/log/allcargo-ws.out.log
 *   user=nobody
 *   environment=APP_SECRET="your_secret_here",CI_ENVIRONMENT="production"
 *
 * APACHE mod_proxy_wstunnel:
 *   ProxyPass        /ws  ws://127.0.0.1:8282/
 *   ProxyPassReverse /ws  ws://127.0.0.1:8282/
 */

// ---------------------------------------------------------------------------
// 1. Resolve project root
//    This file lives at project root alongside vendor/, app/, system/
// ---------------------------------------------------------------------------
define('PROJECT_ROOT', __DIR__);

// ---------------------------------------------------------------------------
// 2. Composer autoloader
// ---------------------------------------------------------------------------
$autoloadFile = PROJECT_ROOT . '/vendor/autoload.php';
if (!is_file($autoloadFile)) {
    fwrite(STDERR, "ERROR: vendor/autoload.php not found. Run 'composer install'.\n");
    exit(1);
}
$loader = require $autoloadFile;

// ---------------------------------------------------------------------------
// 3. Register App\ namespace manually so WebSocket handler class is found.
//    Composer's autoload section may map App\ to app/ but the WebSocket
//    subdirectory needs to be explicitly reachable.
// ---------------------------------------------------------------------------
$loader->addPsr4('App\\',    PROJECT_ROOT . '/app/');
$loader->addPsr4('Config\\', PROJECT_ROOT . '/app/Config/');

// ---------------------------------------------------------------------------
// 4. Define CI4 path constants
// ---------------------------------------------------------------------------
defined('FCPATH')   || define('FCPATH',   PROJECT_ROOT . '/public/');
defined('ROOTPATH') || define('ROOTPATH', PROJECT_ROOT . '/');

// Locate system directory — support both vendor layout and local system/
$systemCandidates = [
    PROJECT_ROOT . '/system',                                          // local system/ folder
    PROJECT_ROOT . '/vendor/codeigniter4/framework/system',            // Composer vendor
];
$systemPath = null;
foreach ($systemCandidates as $candidate) {
    if (is_dir($candidate) && is_file($candidate . '/Common.php')) {
        $systemPath = realpath($candidate);
        break;
    }
}
if ($systemPath === null) {
    fwrite(STDERR, "ERROR: CI4 system directory not found.\n");
    exit(1);
}

define('SYSTEMPATH', $systemPath . DIRECTORY_SEPARATOR);
define('APPPATH',    PROJECT_ROOT . '/app/');

$writablePath = PROJECT_ROOT . '/writable';
if (!is_dir($writablePath)) { mkdir($writablePath, 0755, true); }
define('WRITEPATH', $writablePath . DIRECTORY_SEPARATOR);

// ---------------------------------------------------------------------------
// 5. Load environment variables from .env
// ---------------------------------------------------------------------------
$dotEnv = PROJECT_ROOT . '/.env';
if (is_file($dotEnv)) {
    foreach (file($dotEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if (!array_key_exists($k, $_ENV)) {
            putenv("$k=$v");
            $_ENV[$k] = $_SERVER[$k] = $v;
        }
    }
}

// ---------------------------------------------------------------------------
// 6. Boot CI4 — load Common functions so log_message(), db_connect() etc work
// ---------------------------------------------------------------------------
defined('ENVIRONMENT') || define('ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'production');

// CI_DEBUG must be defined before loading Common.php — the Logger depends on it
defined('CI_DEBUG') || define('CI_DEBUG', ENVIRONMENT !== 'production');

require SYSTEMPATH . 'Common.php';

// CI4 4.5+ uses CodeIgniter\Boot — do NOT call the old bootstrap.php
// We only need the autoloader + DB layer, not a full HTTP boot.
// Initialise the Config factory so models can resolve their DB group.
if (class_exists('\CodeIgniter\Config\Factories')) {
    // Triggers autoloader registration for app config classes
    \CodeIgniter\Config\Factories::config('App');
}

// Ensure DB is initialised
try {
    \CodeIgniter\Database\Config::connect();
} catch (\Throwable $e) {
    fwrite(STDERR, "DB connect warning: " . $e->getMessage() . "\n");
    // Non-fatal — models will reconnect on first query
}

// ---------------------------------------------------------------------------
// 7. Start the Ratchet WebSocket server
// ---------------------------------------------------------------------------
use App\WebSocket\ChatWebSocketHandler;
use App\Models\LiveChatSessionModel;
use App\Models\LiveChatMessageModel;
use App\Models\AgentModel;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$handler = new ChatWebSocketHandler(
    new LiveChatSessionModel(),
    new LiveChatMessageModel(),
    new AgentModel()
);

// Keep MySQL alive — ping every 60 seconds to prevent "gone away" errors
$loop = \React\EventLoop\Loop::get();
$loop->addPeriodicTimer(60, function () {
    try {
        $db = \CodeIgniter\Database\Config::connect();
        if ($db->connID) {
            @mysqli_ping($db->connID);
        }
    } catch (\Throwable $e) {
        error_log('[WS] DB keepalive failed: ' . $e->getMessage());
    }
});

$port   = (int) (getenv('WS_PORT') ?: 8282);
$server = IoServer::factory(
    new HttpServer(new WsServer($handler)),
    $port
);

echo "Allcargo WebSocket server listening on 0.0.0.0:{$port}" . PHP_EOL;
echo "Press Ctrl+C to stop." . PHP_EOL;

$server->run();
