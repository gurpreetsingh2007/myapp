<?php
// server.php

require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

class ConfigPusher implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface> */
    private \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "[WS] New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // We don't expect clients to send messages in this example,
        // but you could handle them here if needed.
        echo "[WS] Received from {$from->resourceId}: $msg\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "[WS] Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "[WS] Error: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcast(string $message): void
    {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
        echo "[HTTP?WS] Broadcasted to " . count($this->clients) . " client(s).\n";
    }
}

// Create ReactPHP event loop
$loop = Factory::create();
$configPusher = new ConfigPusher();

/*
 * 1) SET UP WEBSOCKET SERVER on ws://0.0.0.0:8080
 */
$wsApp = new RatchetHttpServer(new WsServer($configPusher));
$wsSock = new SocketServer('0.0.0.0:8080', [], $loop);
$wsServer = new IoServer($wsApp, $wsSock, $loop);

/*
 * 2) SET UP HTTP SERVER on http://0.0.0.0:8081
 *
 *    - Only route: POST /push
 *    - Reads raw JSON body, then calls $configPusher->broadcast(...)
 */
$httpApp = new ReactHttpServer(function (ServerRequestInterface $request) use ($configPusher) {
    // Only accept POST to /push
    if ($request->getMethod() === 'POST' && $request->getUri()->getPath() === '/push') {
        $body = $request->getBody()->getContents();

        // OPTIONAL: Validate that $body is valid JSON
        // If invalid JSON, return 400:
        if (json_decode($body, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            return new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Invalid JSON'])
            );
        }

        // Broadcast raw JSON string to all WS clients
        $configPusher->broadcast($body);

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'status' => 'broadcasted',
                'message' => json_decode($body, true),
            ])
        );
    }

    // Anything else: 404
    return new Response(
        404,
        ['Content-Type' => 'application/json'],
        json_encode(['error' => 'Not Found'])
    );
});

$httpSock = new SocketServer('0.0.0.0:8081', [], $loop);
$httpServer = $httpApp->listen($httpSock);

// Print to console for verification
echo "? WebSocket server listening on ws://localhost:8080\n";
echo "? HTTP endpoint listening on http://localhost:8081/push\n";

$loop->run();