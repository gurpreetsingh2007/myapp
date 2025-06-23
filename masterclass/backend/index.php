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
require 'vendor/autoload.php';
require_once 'keys/keys.php';
require_once 'login/login.php'; 
require_once 'db/config.php';

set_time_limit(0);
$endpoint = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
$endpoint = str_replace("/backend", "", $endpoint);
$cleanEndpoint = explode('?', $endpoint)[0];

try {

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if ($cleanEndpoint === "/test") {
        echo json_encode(["success" => true, "message" => "BACKEND IS WORKING"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/keys/PublicKey") {
        echoPublicKey();
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/credentials/login") {
        mainLogin(json_decode(file_get_contents('php://input'), true));
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/credentials/exit") {
        pageLogin(json_decode(file_get_contents('php://input'), true));
        EXIT_FUNC();
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/credentials/logincheack") {
        pageLogin(json_decode(file_get_contents('php://input'), true));
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/credentials/get/index") {
        getConfigFileIndex();
        exit;
    }
    if ($_SERVER["REQUEST_METHOD"] === "GET" && $cleanEndpoint === "/credentials/get/block") {
        $getData = $_GET;
        $path = isset($getData['path']) ? urldecode($getData['path']) : null;
        getBlock($path);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === "POST" && $cleanEndpoint === "/credentials/create/block") {
        $getData = $_GET;
        $path = isset($getData['path']) ? urldecode($getData['path']) : null;
        createBlock($path);
        exit;
    }
    //put data form the json file if destroyed
    if ($_SERVER['REQUEST_METHOD'] === "GET" && $cleanEndpoint === "/credentials/put/data") {
        loadDataJson();
        createHistoryTable();
        createLogTable();
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === "GET" && $cleanEndpoint === "/credentials/get/history") {
        giveHistory();
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === "GET" && $cleanEndpoint === "/credentials/get/searchHistory") {
        searchHistory($_GET['q'] ?? '');
        exit;
    }

    // Update existing block (PUT)
    if ($_SERVER['REQUEST_METHOD'] === "PUT" && $cleanEndpoint === "/credentials/update/block") {
        $getData = $_GET;
        $id = isset($getData['id']) ? $getData['id'] : null;
        $path = isset($getData['path']) ? urldecode($getData['path']) : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Block ID is required']);
            exit;
        }

        updateBlock($id, $path);
        exit;
    }
    // Delete block (DELETE)
    if ($_SERVER['REQUEST_METHOD'] === "DELETE" && $cleanEndpoint === "/credentials/delete/block") {
        $getData = $_GET;
        $id = isset($getData['id']) ? $getData['id'] : null;
        $path = isset($getData['path']) ? urldecode($getData['path']) : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Block ID is required']);
            exit;
        }

        deleteBlock($id, $path);
        exit;
    }
    // GET endpoint for retrieving JSON data
    if ($_SERVER["REQUEST_METHOD"] === "GET" && $cleanEndpoint === "/credentials/get/json") {
        $getData = $_GET;
        $path = isset($getData['path']) ? urldecode($getData['path']) : null;
        $id = isset($getData['id']) ? $getData['id'] : null;
        getJsonData($path, $id);
        exit;
    }

    // Update existing JSON data (PUT)
    if ($_SERVER['REQUEST_METHOD'] === "PUT" && $cleanEndpoint === "/credentials/update/json") {
        $getData = $_GET;
        $id = isset($getData['id']) ? $getData['id'] : null;
        $path = isset($getData['path']) ? urldecode($getData['path']) : null;

        if (!$id || !$path) {
            http_response_code(400);
            echo json_encode(['error' => 'Block ID and path are required']);
            exit;
        }

        updateJsonData();
        exit;
    }



    ////////////////////////////////////////server------------------------
    if ($_SERVER['REQUEST_METHOD'] === "POST" && $cleanEndpoint === "/credentials/send/files") {
        sendFiles(json_decode(file_get_contents("php://input"), true));
        exit;
    }
    if($_SERVER['REQUEST_METHOD'] === "GET" && $cleanEndpoint === "/credentials/get/server_list"){
        sendServerList();
        exit;
    }
    if($_SERVER['REQUEST_METHOD'] === "POST" && $cleanEndpoint === "/credentials/send/partialFilesServer"){
        sendPartialFilesServer(json_decode(file_get_contents("php://input"), true));
        exit;
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