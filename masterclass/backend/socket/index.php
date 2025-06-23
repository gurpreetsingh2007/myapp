<?php
require __DIR__ . '/../vendor/autoload.php';

use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\WebSocket\WsServer as RatchetWsServer;
use React\EventLoop\Loop;

class ConfigPusher
{
    private array $clients = [];
    private array $pendingRequests = [];
    private $loop;

    public function __construct()
    {
        $this->loop = Loop::get();
    }

    public function addClient(ConnectionInterface $conn, string $clientId): void
    {
        $this->clients[$clientId] = [
            'conn' => $conn,
            'remoteAddress' => $conn->remoteAddress ?? 'unknown'
        ];
        echo "[WS] Client $clientId added. Total clients: " . count($this->clients) . "\n";
    }

    public function removeClient(string $clientId): void
    {
        if (isset($this->clients[$clientId])) {
            unset($this->clients[$clientId]);
            echo "[WS] Client $clientId removed. Total clients: " . count($this->clients) . "\n";
        }

        foreach ($this->pendingRequests as $requestId => $request) {
            $expectedClients = $request['expectedClients'];
            $responses = $request['responses'];

            $stillConnected = array_intersect($expectedClients, array_keys($this->clients));
            $receivedFromConnected = array_intersect(array_keys($responses), $stillConnected);

            if (count($receivedFromConnected) === count($stillConnected) && count($stillConnected) > 0) {
                echo "[WS] Resolving request $requestId after client disconnection\n";
                $request['deferred']->resolve($responses);
                $this->loop->cancelTimer($request['timer']);
                unset($this->pendingRequests[$requestId]);
            } elseif (count($stillConnected) === 0) {
                echo "[WS] Rejecting request $requestId - no clients remaining\n";
                $request['deferred']->reject(new \Exception("All clients disconnected"));
                $this->loop->cancelTimer($request['timer']);
                unset($this->pendingRequests[$requestId]);
            }
        }
    }

    public function handleClientResponse(string $clientId, string $message): void
    {
        echo "[WS] Raw message from $clientId: $message\n";

        $response = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "[WS] Invalid JSON from client $clientId: " . json_last_error_msg() . "\n";
            return;
        }

        if (!isset($response['requestId'])) {
            echo "[WS] Message without requestId from client $clientId\n";
            return;
        }

        $requestId = $response['requestId'];
        if (!isset($this->pendingRequests[$requestId])) {
            echo "[WS] Unknown requestId $requestId from client $clientId\n";
            return;
        }

        $this->pendingRequests[$requestId]['responses'][$clientId] = $response['data'] ?? $response;
        echo "[WS] Stored response from $clientId for request $requestId\n";

        $expectedClients = $this->pendingRequests[$requestId]['expectedClients'];
        $responses = $this->pendingRequests[$requestId]['responses'];
        $stillConnected = array_intersect($expectedClients, array_keys($this->clients));
        $receivedFromConnected = array_intersect(array_keys($responses), $stillConnected);

        echo "[WS] Request $requestId status: " . count($receivedFromConnected) . "/" . count($stillConnected) . " responses\n";

        if (count($receivedFromConnected) === count($stillConnected)) {
            echo "[WS] All clients responded for request $requestId - resolving\n";
            $this->pendingRequests[$requestId]['deferred']->resolve($responses);
            $this->loop->cancelTimer($this->pendingRequests[$requestId]['timer']);
            unset($this->pendingRequests[$requestId]);
        }
    }

    public function broadcastAndWaitForResponses(string $message, int $timeoutSeconds = 30, ?array $targets = null): PromiseInterface
    {
        echo "[HTTP?WS] Starting broadcast...\n";

        if (empty($this->clients)) {
            echo "[HTTP?WS] No clients connected - returning empty response\n";
            $deferred = new Deferred();
            $this->loop->futureTick(fn() => $deferred->resolve([]));
            return $deferred->promise();
        }

        $decoded = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $deferred = new Deferred();
            $deferred->reject(new \Exception("Invalid JSON: " . json_last_error_msg()));
            return $deferred->promise();
        }

        $requestId = uniqid('req_', true);
        $deferred = new Deferred();

        $allClientIds = array_keys($this->clients);
        $expectedClients = is_array($targets)
            ? array_values(array_intersect($targets, $allClientIds))
            : $allClientIds;

        if (empty($expectedClients)) {
            echo "[HTTP?WS] No valid target clients\n";
            $deferred->resolve([]);
            return $deferred->promise();
        }

        echo "[HTTP?WS] Created request $requestId for " . count($expectedClients) . " clients\n";

        $timer = $this->loop->addTimer($timeoutSeconds, function () use ($requestId, $timeoutSeconds) {
            if (isset($this->pendingRequests[$requestId])) {
                $responses = $this->pendingRequests[$requestId]['responses'];
                $expected = count($this->pendingRequests[$requestId]['expectedClients']);
                $received = count($responses);

                echo "[HTTP?WS] Request $requestId timed out ($received/$expected responses after {$timeoutSeconds}s)\n";

                $this->pendingRequests[$requestId]['deferred']->reject(
                    new \Exception("Timeout: received $received/$expected responses after {$timeoutSeconds}s")
                );
                unset($this->pendingRequests[$requestId]);
            }
        });

        $this->pendingRequests[$requestId] = [
            'deferred' => $deferred,
            'responses' => [],
            'expectedClients' => $expectedClients,
            'timer' => $timer,
            'startTime' => time(),
            'originalMessage' => $message
        ];

        $payload = json_encode([
            'requestId' => $requestId,
            'data' => $decoded['data'] ?? $decoded,
            'command' => $decoded['command'] ?? null
        ]);

        echo "[HTTP?WS] Payload to send: $payload\n";

        $successfulSends = 0;
        foreach ($expectedClients as $clientId) {
            if (!isset($this->clients[$clientId]))
                continue;
            try {
                $this->clients[$clientId]['conn']->send($payload);
                $successfulSends++;
                echo "[HTTP?WS] Sent to client $clientId\n";
            } catch (\Exception $e) {
                echo "[HTTP?WS] Failed to send to client $clientId: " . $e->getMessage() . "\n";
                unset($this->clients[$clientId]);
            }
        }

        echo "[HTTP?WS] Successfully sent to $successfulSends/" . count($expectedClients) . " clients\n";

        if ($successfulSends === 0) {
            $this->loop->cancelTimer($timer);
            unset($this->pendingRequests[$requestId]);
            $deferred->reject(new \Exception("Failed to send to any clients"));
        }

        return $deferred->promise();
    }

    public function getStatus(): array
    {
        return [
            'connectedClients' => count($this->clients),
            'clientIds' => array_keys($this->clients),
            'pendingRequests' => count($this->pendingRequests),
            'pendingRequestDetails' => array_map(fn($req) => [
                'expectedClients' => $req['expectedClients'],
                'receivedResponses' => count($req['responses']),
                'startTime' => $req['startTime']
            ], $this->pendingRequests)
        ];
    }

    public function getRawClients(): array
    {
        return $this->clients;
    }
}

$configPusher = new ConfigPusher();

class WebSocketComponent implements MessageComponentInterface
{
    private ConfigPusher $configPusher;

    public function __construct(ConfigPusher $pusher)
    {
        $this->configPusher = $pusher;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $clientId = uniqid('client_', true);
        $conn->clientId = $clientId;
        $this->configPusher->addClient($conn, $clientId);
        echo "[WS] Client $clientId connected from {$conn->remoteAddress}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $clientId = $from->clientId ?? 'unknown';
        $this->configPusher->handleClientResponse($clientId, $msg);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->configPusher->removeClient($conn->clientId ?? 'unknown');
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "[WS] Error from client: " . $e->getMessage() . "\n";
        $conn->close();
    }
}

// Start WebSocket server
$webSocketApp = new RatchetHttpServer(new RatchetWsServer(new WebSocketComponent($configPusher)));
$wsSocket = new SocketServer('0.0.0.0:8080');
$wsServer = new IoServer($webSocketApp, $wsSocket);

// Start HTTP server
$httpServer = new ReactHttpServer(function (ServerRequestInterface $request) use ($configPusher) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();
    echo "[HTTP] $method $path\n";

    $body = $request->getBody()->getContents();

    if ($method === 'POST' && $path === '/push') {
        return $configPusher->broadcastAndWaitForResponses($body)
            ->then(fn($responses) => new Response(
                200,
                ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
                json_encode(['success' => true, 'responses' => $responses, 'clientCount' => count($responses), 'timestamp' => date('Y-m-d H:i:s')])
            ))->otherwise(fn($e) => new Response(
                408,
                ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
                json_encode(['error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')])
            ));
    }

    if ($method === 'POST' && $path === '/pushPartial') {
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['targets']) || !is_array($decoded['targets'])) {
            return new Response(
                400,
                ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
                json_encode(['error' => "Missing or invalid 'targets' array"])
            );
        }

        return $configPusher->broadcastAndWaitForResponses($body, 30, $decoded['targets'])
            ->then(fn($responses) => new Response(
                200,
                ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
                json_encode(['success' => true, 'responses' => $responses, 'clientCount' => count($responses), 'timestamp' => date('Y-m-d H:i:s')])
            ))->otherwise(fn($e) => new Response(
                408,
                ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
                json_encode(['error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')])
            ));
    }

    if ($method === 'GET' && $path === '/status') {
        return new Response(
            200,
            ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
            json_encode(['status' => $configPusher->getStatus(), 'timestamp' => date('Y-m-d H:i:s')])
        );
    }

    if ($method === 'GET' && $path === '/serverList') {
        $clients = array_map(fn($cid, $info) => ['clientId' => $cid, 'remoteAddress' => $info['remoteAddress']], array_keys($configPusher->getRawClients()), $configPusher->getRawClients());
        return new Response(
            200,
            ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
            json_encode(['clients' => $clients, 'total' => count($clients), 'timestamp' => date('Y-m-d H:i:s')])
        );
    }

    if ($method === 'OPTIONS') {
        return new Response(200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ]);
    }

    return new Response(
        404,
        ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],
        json_encode(['error' => 'Not Found', 'availableEndpoints' => ['/push', '/pushPartial', '/status', '/serverList']])
    );
});

$httpSocket = new SocketServer('0.0.0.0:8081');
$httpServer->listen($httpSocket);

echo "[INIT] WebSocket server running on ws://localhost:8080\n";
echo "[INIT] HTTP server running on http://localhost:8081\n";
//echo "[INIT] POST /pushPartial example: curl -X POST http://localhost:8081/pushPartial -d '{\"targets\":[\"client_xxx\"], \"data\":[], \"command\":\"deploy\"}' -H 'Content-Type: application/json'\n";

Loop::get()->run();