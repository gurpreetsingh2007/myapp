<?php
require_once __DIR__ . '/../keys/keys.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function mainLogin($data)
{
    if (!isset($data['username'], $data['password'])) {
        echo json_encode(["success" => false, "message" => "Username and password are required."]);
        exit;
    }

    // Decode base64-encoded encrypted values
    $encryptedUsername = base64_decode($data['username']);
    $encryptedPassword = base64_decode($data['password']);

    // Decrypt credentials
    $rsa = loadPrivateKeyFromFile();
    $username = $rsa->decrypt($encryptedUsername);
    $password = $rsa->decrypt($encryptedPassword);

    if ($username === false || $password === false) {
        echo json_encode(["success" => false, "message" => "Decryption failed"]);
        exit;
    }

    // Brute force protection
    if (checkFailedAttempts($username)) {
        //http_response_code(429);
        echo json_encode(["success" => false, "message" => "Too many failed attempts. Try again later."]);
        exit;
    }

    // TODO: Replace with real authentication (e.g., LDAP, DB check)
// $authResult = ldap_authenticate($username, $password);
    $authResult = true; // Placeholder for demo

    if ($authResult) {
        session_regenerate_id(true); // Prevent session fixation

        $_SESSION['username'] = $username;
        $_SESSION['loggedin'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Create JWT
        $issuedAt = time();
        $expiration = $issuedAt + 3600; // 1 hour

        $payload = [
            "iss" => "https://myapp.local",
            "iat" => $issuedAt,
            "exp" => $expiration,
            "username" => $username,
        ];
        $privateKey = file_get_contents(__DIR__ . '/../keys/private.key');
        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        // Set JWT cookie
        setcookie("authToken", $jwt, [
            "expires" => $expiration,
            "httponly" => true,
            "secure" => true,
            "samesite" => "Strict"
        ]);

        header('Content-Type: application/json');

        echo json_encode([
            "username" => $_SESSION['username'],
            "success" => true,
            "csrf_token" => $_SESSION['csrf_token'],
            "expires_in" => 3600
        ]);
    } else {
        logFailedAttempt($username);
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    }
}
function pageLogin($data)
{

    // 1. Check session variables
    if (empty($_SESSION['loggedin']) || empty($_SESSION['username']) || !isset($_SESSION['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Session invalid or expired']);
        return;
    }

    // 2. Check CSRF token
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        return;
    }

    // 3. Check JWT auth token
    if (empty($_COOKIE['authToken'])) {
        echo json_encode(['success' => false, 'message' => 'Auth token missing']);
        return;
    }

    $jwt = $_COOKIE['authToken'];
    $publicKeyPath = __DIR__ . '/../keys/public.key';
    if (!file_exists($publicKeyPath)) {
        echo json_encode(['success' => false, 'message' => 'Public key not found']);
        return;
    }

    $publicKey = file_get_contents($publicKeyPath);

    try {
        $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
        if ($decoded->username !== $_SESSION['username']) {
            echo json_encode(['success' => false, 'message' => 'Token username mismatch']);
            return;
        }

        // All checks passed
        echo json_encode(['success' => true, 'username' => $_SESSION['username']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
    }
}
function serverLogin()
{
    if (empty($_SESSION['loggedin']) || empty($_SESSION['username'])) {
        http_response_code(401);
        exit;
    }

    // 2. Check authToken cookie
    if (empty($_COOKIE['authToken'])) {
        exit;
    }

    // 3. Load public key for token verification
    $publicKeyPath = __DIR__ . '/../keys/public.key';
    if (!file_exists($publicKeyPath)) {
        exit;
    }

    $publicKey = file_get_contents($publicKeyPath);

    try {
        $decoded = JWT::decode($_COOKIE['authToken'], new Key($publicKey, 'RS256'));

        // 4. Optional check: username match
        if ($decoded->username !== $_SESSION['username']) {
            exit;
        }

        // ? Everything is valid, allow request
    } catch (Exception $e) {
        exit;
    }
}
function checkFailedAttempts($username)
{
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes

    if (!isset($_SESSION['failed_attempts'])) {
        return false;
    }

    if (isset($_SESSION['failed_attempts'][$username])) {
        $attempts = $_SESSION['failed_attempts'][$username];

        // If lockout period has passed, reset attempts
        if ((time() - $attempts['time']) > $lockout_time) {
            unset($_SESSION['failed_attempts'][$username]);
            return false;
        }

        if ($attempts['count'] >= $max_attempts) {
            return true; // User is locked out
        }
    }

    return false;
}

function logFailedAttempt($username)
{
    if (!isset($_SESSION['failed_attempts'])) {
        $_SESSION['failed_attempts'] = [];
    }

    if (!isset($_SESSION['failed_attempts'][$username])) {
        $_SESSION['failed_attempts'][$username] = ['count' => 1, 'time' => time()];
    } else {
        $_SESSION['failed_attempts'][$username]['count']++;
        $_SESSION['failed_attempts'][$username]['time'] = time();
    }
}