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
require_once 'services/nginx/db/utility.php';
require_once 'services/80/server.php';
$loader = new NginxConfigLoader();

// Helper function to get JSON input
function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true);
}

// Helper function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

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

    //----------- NGINX ENDPOINTS --------------
    // =============================================
    // Server Block Endpoints
    // =============================================

    // GET /nginx/servers - Get all servers
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/servers") {
        try {
            $servers = $loader->getAllServersForAPI();
            sendJsonResponse([
                'success' => true,
                'data' => $servers,
                'count' => count($servers)
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /nginx/servers - Add new server
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/nginx/servers") {
        try {
            $serverData = getJsonInput();
            if (!$serverData) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $result = $loader->addServerFromAPI($serverData);
            $statusCode = $result['success'] ? 201 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PUT /nginx/servers/{id} - Update server
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('/^\/nginx\/servers\/(\d+)$/', $cleanEndpoint, $matches)) {
        try {
            $serverId = (int) $matches[1];
            $serverData = getJsonInput();
            if (!$serverData) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $result = $loader->updateServerFromAPI($serverId, $serverData);
            $statusCode = $result['success'] ? 200 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /nginx/servers/{id} - Delete server
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('/^\/nginx\/servers\/(\d+)$/', $cleanEndpoint, $matches)) {
        try {
            $serverId = (int) $matches[1];
            $result = $loader->deleteServerFromAPI($serverId);
            $statusCode = $result['success'] ? 200 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // Certificate Endpoints
    // =============================================

    // GET /nginx/certificates - Get all certificates
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/certificates") {
        try {
            $certificates = $loader->getAllCertificatesForAPI();
            sendJsonResponse([
                'success' => true,
                'data' => $certificates,
                'count' => count($certificates)
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /nginx/certificates - Add new certificate
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/nginx/certificates") {
        try {
            $certData = getJsonInput();
            if (!$certData) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $result = $loader->addCertificateFromAPI($certData);
            $statusCode = $result['success'] ? 201 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PUT /nginx/certificates/{id} - Update certificate
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('/^\/nginx\/certificates\/(\d+)$/', $cleanEndpoint, $matches)) {
        try {
            $certId = (int) $matches[1];
            $certData = getJsonInput();
            if (!$certData) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $result = $loader->updateCertificateFromAPI($certId, $certData);
            $statusCode = $result['success'] ? 200 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /nginx/certificates/{id} - Delete certificate
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('/^\/nginx\/certificates\/(\d+)$/', $cleanEndpoint, $matches)) {
        try {
            $certId = (int) $matches[1];
            $result = $loader->deleteCertificateFromAPI($certId);
            $statusCode = $result['success'] ? 200 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // Parameter Endpoints
    // =============================================

    // GET /nginx/parameters - Get all parameters
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/parameters") {
        try {
            $parameters = $loader->getAllParametersForAPI();
            sendJsonResponse([
                'success' => true,
                'data' => $parameters,
                'count' => count($parameters)
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /nginx/parameters - Add new parameter
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/nginx/parameters") {
        try {
            $paramData = getJsonInput();
            if (!$paramData) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $result = $loader->addParameterFromAPI($paramData);
            $statusCode = $result['success'] ? 201 : 400;
            sendJsonResponse($result, $statusCode);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /nginx/parameters/locations - Get location-specific parameters
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/parameters/locations") {
        try {
            $parameters = $loader->getLocationParametersForAPI();
            sendJsonResponse([
                'success' => true,
                'data' => $parameters,
                'count' => count($parameters)
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // get history
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/history") {
        $searchQuery = $_GET['q'] ?? '';
        $offset = $_GET['offset'] ?? 0;
        $limit = $_GET['limit'] ?? 10;
        $response = $loader->searchHistory($searchQuery, $offset, $limit);
        header('Content-Type: application/json');
        echo json_encode($response);
        return;
    }


    // =============================================
    // Configuration Management Endpoints
    // =============================================

    // POST /nginx/config/load-certificates - Load certificates from JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/nginx/config/load-certificates") {
        try {
            $input = getJsonInput();
            if (!isset($input['json_file'])) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'json_file parameter required'
                ], 400);
            }

            $loader->loadCertificatesFromJson($input['json_file']);
            sendJsonResponse([
                'success' => true,
                'message' => 'Certificates loaded successfully'
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /nginx/config/load-servers - Load servers from JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cleanEndpoint === "/nginx/config/load-servers") {
        try {
            $input = getJsonInput();
            if (!isset($input['json_file'])) {
                sendJsonResponse([
                    'success' => false,
                    'error' => 'json_file parameter required'
                ], 400);
            }

            $loader->loadServersFromJson($input['json_file']);
            sendJsonResponse([
                'success' => true,
                'message' => 'Server configurations loaded successfully'
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /nginx/config/export - Export all configuration as JSON
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/config/export") {
        try {
            $servers = $loader->getAllServersForAPI();
            $certificates = $loader->getAllCertificatesForAPI();
            $parameters = $loader->getAllParametersForAPI();

            $exportData = [
                'export_date' => date('Y-m-d H:i:s'),
                'servers' => $servers,
                'certificates' => $certificates,
                'parameters' => $parameters
            ];

            sendJsonResponse([
                'success' => true,
                'data' => $exportData
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // Health Check and Status Endpoints
    // =============================================

    // GET /nginx/health - Health check endpoint
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/health") {
        try {
            $servers = $loader->getAllServersForAPI();
            $certificates = $loader->getAllCertificatesForAPI();
            
            sendJsonResponse([
                'success' => true,
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'stats' => [
                    'total_servers' => count($servers),
                    'total_certificates' => count($certificates),
                    'ssl_enabled_servers' => count(array_filter($servers, function($s) { return $s['ssl_enabled']; }))
                ]
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /nginx/stats - Get statistics
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $cleanEndpoint === "/nginx/stats") {
        try {
            $servers = $loader->getAllServersForAPI();
            $certificates = $loader->getAllCertificatesForAPI();
            $parameters = $loader->getAllParametersForAPI();

            $stats = [
                'servers' => [
                    'total' => count($servers),
                    'ssl_enabled' => count(array_filter($servers, function($s) { return $s['ssl_enabled']; })),
                    'http2_enabled' => count(array_filter($servers, function($s) { return $s['is_http2']; })),
                    'websocket_enabled' => count(array_filter($servers, function($s) { return $s['is_websocket_enabled']; }))
                ],
                'certificates' => [
                    'total' => count($certificates),
                    'self_signed' => count(array_filter($certificates, function($c) { return $c['is_self_signed']; })),
                    'expiring_soon' => count(array_filter($certificates, function($c) { 
                        return $c['valid_to'] && strtotime($c['valid_to']) < strtotime('+30 days');
                    }))
                ],
                'parameters' => [
                    'total' => count($parameters),
                    'common' => count(array_filter($parameters, function($p) { return $p['is_common']; }))
                ]
            ];

            sendJsonResponse([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            sendJsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /////server 81 comunication
    
    if ($_SERVER['REQUEST_METHOD'] === "GET" && $cleanEndpoint === "/credentials/get/server_list") {
        sendServerList();
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
?>