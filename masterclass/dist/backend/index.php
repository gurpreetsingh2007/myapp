<?php
// Set the headers for SSE
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");
header("Access-Control-Allow-Origin: *");

// Disable execution time limit
set_time_limit(0);

// Handle GET /backend/test
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/backend/test') {
    // Ensure output is not buffered
    if (ob_get_level()) {
        ob_end_flush();
    }
    $count = 0;
    while (true) {
        $message = "Hello from PHP API! " . $count++;
        
        // Format the message according to SSE specifications
        echo "data: " . json_encode(["success" => true, "message" => $message]) . "\n\n";
        
        // Flush buffers to send the data immediately
        ob_flush();
        flush();
        
        // Check if the client has disconnected
        if (connection_aborted()) {
            break;
        }

        sleep(1);
    }
    exit; // Exit after loop to prevent 404 response
}

// 404 for unknown routes
http_response_code(404);
echo json_encode(["error" => "Not Found"]);