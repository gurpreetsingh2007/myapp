<?php
session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Catch all PHP errors (warnings, notices, etc.)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("PHP ERROR [$errno]: $errstr in $errfile on line $errline\n", 3, "/tmp/php_debug.log");
});

// Catch fatal errors on shutdown
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL) {
        error_log("FATAL ERROR: " . print_r($error, true) . "\n", 3, "/tmp/php_debug.log");
    }
});

require __DIR__ . '/vendor/autoload.php';







require_once 'login_functions/login.php';

//imports
$privateKey = file_get_contents('keys/private_key.pem');
$publicKey = file_get_contents('keys/public_key.pem');

// Disable execution time limit
set_time_limit(0);
$endpoint = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
$endpoint = str_replace("/backend", "", $endpoint);



error_log("Session ID: index.php " . session_id() . "\n", 3, "/var/log/myapp_error.log");
try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // Set CORS headers for preflight request
        error_log("Session ID: options " . session_id() . "\n", 3, "/var/log/myapp_error.log");
        http_response_code(200);
        exit;
    }

    if ($endpoint === "/test") {
        echo json_encode(["success" => true, "message" => "BACKEND IS WORKING"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $endpoint === "/keys/PublicKey") {
        require_once __DIR__ . '/keys/PublicKey.php';
        error_log("Session ID: public keys " . session_id() . "\n", 3, "/var/log/myapp_error.log");
        keys_PublicKey();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === "/credentials/login") {
        require_once __DIR__ . '/login_functions/login.php';
        $data = json_decode(file_get_contents("php://input"), true);
        main_login($data);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === "/credentials/logincheack") {
        $data = json_decode(file_get_contents("php://input"), true);
        header('Content-Type: application/json');
        login_cheack($data);
    }


    // 404 for unknown routes
    http_response_code(404);
    echo json_encode(["error" => "Not Found"]);
} catch (Throwable $e) {
    $logMessage = sprintf(
        "THROWABLE: %s in %s on line %d\nStack trace:\n%s\n",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($logMessage, 3, "/var/log/myapp_error.log");

    http_response_code(500);
    echo nl2br($logMessage);
    echo json_encode(["error" => "Server error"]);
}