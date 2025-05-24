<?php

define('DB_HOST', '172.21.229.219');
define('DB_USER', 'gurpreet');
define('DB_PASS', 'gurpreet');
define('DB_NAME', 'testdb');
define('DB_PORT', 3306);
function getDbConnection()
{
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
function resetDatabase()
{
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("DROP DATABASE IF EXISTS " . DB_NAME);
        $conn->exec("CREATE DATABASE " . DB_NAME);
        $conn->exec("USE " . DB_NAME);

        // Create table to index config files
        $conn->exec("
            CREATE TABLE config_file_index (
                id INT AUTO_INCREMENT PRIMARY KEY,
                file_name VARCHAR(255) NOT NULL,
                status VARCHAR(20),
                errors TEXT,
                last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        return $conn;
    } catch (PDOException $e) {
        die("DB Reset Failed: " . $e->getMessage());
    }
}

function createDirectiveTable($conn, $tableName)
{
    $conn->exec("DROP TABLE IF EXISTS `$tableName`");
    $conn->exec("
        CREATE TABLE `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title TEXT,
            json_data LONGTEXT
        );
    ");
}

function insertFileMetadata($conn, $file, $status, $errors)
{
    $stmt = $conn->prepare("
        INSERT INTO config_file_index (file_name, status, errors)
        VALUES (:file, :status, :errors)
    ");
    $stmt->execute([
        ':file' => $file,
        ':status' => $status,
        ':errors' => json_encode($errors)
    ]);
}

function insertParsedDirectives($conn, $tableName, $parsedDirectives)
{
    $stmt = $conn->prepare("
        INSERT INTO `$tableName` (title, json_data)
        VALUES (:title, :json_data)
    ");
    $index = 1;
    foreach ($parsedDirectives as $directive) {
        $stmt->execute([
            ':title' => 'default' . $index,
            ':json_data' => json_encode($directive)
        ]);
        $index++;
    }
}

function loadDataJson()
{
    $jsonPath = __DIR__ . '/../data/config.json';  // Replace with actual file path
    $jsonContent = file_get_contents($jsonPath);
    $data = json_decode($jsonContent, true);
    if (!$data || !isset($data['config'])) {
        die("Invalid JSON format.");
    }

    $conn = resetDatabase();
    $conn->exec("USE " . DB_NAME);

    foreach ($data['config'] as $configItem) {
        $file = $configItem['file'];
        $status = $configItem['status'] ?? 'unknown';
        $errors = $configItem['errors'] ?? [];

        insertFileMetadata($conn, $file, $status, $errors);

        $tableName = 'cfg_' . strtolower(pathinfo($file, PATHINFO_FILENAME));
        createDirectiveTable($conn, $tableName);

        insertParsedDirectives($conn, $tableName, $configItem['parsed']);
    }
}
function TypeOfDataForDragAndDrop()
{
    $jsonPath = __DIR__ . '/../data/config.json'; // same path as loadDataJson()
    $jsonContent = file_get_contents($jsonPath);
    $data = json_decode($jsonContent, true);
    if (!$data || !isset($data['config'])) {
        die("Invalid JSON format.");
    }

    $conn = getDbConnection();
    createDragDropTable($conn);

    foreach ($data['config'] as $configItem) {
        $file = $configItem['file'];
        if (!isset($configItem['parsed']))
            continue;

        foreach ($configItem['parsed'] as $directive) {
            insertDirectiveRecursively($conn, $directive, $file, null);
        }
    }
}

function createDragDropTable($conn)
{
    $conn->exec("DROP TABLE IF EXISTS dragdrop_directives");
    $conn->exec("
        CREATE TABLE dragdrop_directives (
            id INT AUTO_INCREMENT PRIMARY KEY,
            directive VARCHAR(255),
            args TEXT,
            parent_id INT DEFAULT NULL,
            FOREIGN KEY (parent_id) REFERENCES dragdrop_directives(id) ON DELETE CASCADE
        )
    ");
}

function insertDirectiveRecursively(PDO $conn, array $directive, string $file, ?int $parentId)
{
    $stmt = $conn->prepare("
        INSERT INTO dragdrop_directives (directive, args, parent_id)
        VALUES ( :directive, :args, :parent_id)
    ");
    $stmt->execute([
        ':directive' => $directive['directive'],
        ':args' => json_encode($directive['args'] ?? []),
        ':parent_id' => $parentId
    ]);

    $newId = $conn->lastInsertId();

    if (!empty($directive['block']) && is_array($directive['block'])) {
        foreach ($directive['block'] as $child) {
            insertDirectiveRecursively($conn, $child, $file, $newId);
        }
    }
}

function getConfigFileIndex()
{
    $conn = getDbConnection();

    $stmt = $conn->query("SELECT * FROM config_file_index ORDER BY id ASC");
    $results = $stmt->fetchAll();

    echo json_encode(["success" => true, "message" => $results]);
}

function getBlock($path)
{
    $conn = getDbConnection();

    // Step 1: Get file from index
    $stmt = $conn->prepare('SELECT file_name FROM config_file_index WHERE file_name = ?');
    $stmt->execute([$path]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        return;
    }

    // Step 2: Build table name
    $fileName = $file['file_name'];
    $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

    // Step 3: Check table exists
    $checkStmt = $conn->prepare("SHOW TABLES LIKE ?");
    $checkStmt->execute([$tableName]);

    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => "Table '$tableName' does not exist"]);
        return;
    }

    // Step 4: Fetch only id and title (no json_data)
    $query = $conn->query("SELECT id, title FROM `$tableName`");
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'table' => $tableName,
        'rows' => $rows
    ]);
}
function createBlock($path)
{
    try {
        $conn = getDbConnection();

        // Get JSON data from request body
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (!$data || !isset($data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data. Title is required.']);
            return;
        }

        // Get file from index
        $stmt = $conn->prepare('SELECT file_name FROM config_file_index WHERE file_name = ?');
        $stmt->execute([$path]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Build table name
        $fileName = $file['file_name'];
        $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        // Check table exists
        $checkStmt = $conn->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$tableName]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => "Table '$tableName' does not exist"]);
            return;
        }

        // Create empty JSON object for json_data if not provided
        $jsonData = isset($data['json_data']) ? json_encode($data['json_data']) : '{}';

        // Insert new block
        $insertStmt = $conn->prepare("INSERT INTO `$tableName` (title, json_data) VALUES (?, ?)");
        $insertStmt->execute([$data['title'], $jsonData]);

        $newId = $conn->lastInsertId();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'Block created successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update an existing block
function updateBlock($id, $path)
{
    try {
        $conn = getDbConnection();

        // Get JSON data from request body
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data']);
            return;
        }

        // Get file from index
        $stmt = $conn->prepare('SELECT file_name FROM config_file_index WHERE file_name = ?');
        $stmt->execute([$path]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Build table name
        $fileName = $file['file_name'];
        $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        // Check table exists
        $checkStmt = $conn->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$tableName]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => "Table '$tableName' does not exist"]);
            return;
        }

        // Check if the record exists
        $checkRecordStmt = $conn->prepare("SELECT id FROM `$tableName` WHERE id = ?");
        $checkRecordStmt->execute([$id]);

        if ($checkRecordStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Block not found']);
            return;
        }

        // Build the update query dynamically based on provided fields
        $updateFields = [];
        $params = [];

        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
        }

        if (isset($data['json_data'])) {
            $updateFields[] = "json_data = ?";
            $params[] = json_encode($data['json_data']);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        // Add id to params
        $params[] = $id;

        // Update block
        $updateStmt = $conn->prepare("UPDATE `$tableName` SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $updateStmt->execute($params);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Block updated successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete a block
function deleteBlock($id, $path)
{
    try {
        $conn = getDbConnection();

        // Get file from index
        $stmt = $conn->prepare('SELECT file_name FROM config_file_index WHERE file_name = ?');
        $stmt->execute([$path]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Build table name
        $fileName = $file['file_name'];
        $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        // Check table exists
        $checkStmt = $conn->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$tableName]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => "Table '$tableName' does not exist"]);
            return;
        }

        // Check if the record exists
        $checkRecordStmt = $conn->prepare("SELECT id FROM `$tableName` WHERE id = ?");
        $checkRecordStmt->execute([$id]);

        if ($checkRecordStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Block not found']);
            return;
        }

        // Delete block
        $deleteStmt = $conn->prepare("DELETE FROM `$tableName` WHERE id = ?");
        $deleteStmt->execute([$id]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Block deleted successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getJsonData($path, $id = null)
{
    try {
        $conn = getDbConnection();

        // Step 1: Get file from index
        $stmt = $conn->prepare('SELECT file_name FROM config_file_index WHERE file_name = ?');
        $stmt->execute([$path]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            return;
        }

        // Step 2: Build table name
        $fileName = $file['file_name'];
        $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        // Step 3: Check table exists
        $checkStmt = $conn->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$tableName]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => "Table '$tableName' does not exist"]);
            return;
        }

        // Step 4: Fetch data based on whether ID is provided
        if ($id) {
            // Get specific block with its JSON data
            $query = $conn->prepare("SELECT id, title, json_data FROM `$tableName` WHERE id = ?");
            $query->execute([$id]);
            $row = $query->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                http_response_code(404);
                echo json_encode(['error' => "Block with ID '$id' not found"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode(["success" => true, "id" => $id, "json_data" => $row['json_data']]);
            json_encode(["success" => false, "message" => "id not found or deleated"]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(["success" => false, "message" => "id not found or deleated"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update JSON data for a specific block
 * 
 * @param string $id Block ID
 * @param string $path File path
 * @return void
 */
function updateJsonData()
{
    try {
        $conn = getDbConnection();

        // Read and parse incoming JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (!isset($data['path'], $data['id'], $data['data'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: path, id, or json_data']);
            return;
        }

        $path = $data['path'];
        $id = $data['id'];
        //$jsonContent = $data['data'];
        $jsonContent = json_encode(parseNginxBlock($data['data']));

        // Extract filename and derive table name   
        $fileName = basename($path);
        $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        // Check if table exists
        $checkStmt = $conn->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$tableName]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => "Table '$tableName' does not exist"]);
            return;
        }

        // Check if record exists
        $checkRecordStmt = $conn->prepare("SELECT id FROM `$tableName` WHERE id = ?");
        $checkRecordStmt->execute([$id]);

        if ($checkRecordStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Block not found']);
            return;
        }

        // Update json_data
        $updateStmt = $conn->prepare("UPDATE `$tableName` SET json_data = ? WHERE id = ?");
        $updateStmt->execute([$jsonContent, $id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'JSON data updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function parseNginxBlock($configText)
{
    $lines = preg_split('/\R/', $configText);
    $lineNumber = 0;
    $index = 0;

    function parseBlock(&$lines, &$index, &$lineNumber)
    {
        $block = [];

        while ($index < count($lines)) {
            $line = trim($lines[$index]);
            $lineNumber++;

            if ($line === '') {
                $index++;
                continue;
            }

            if ($line === '}') {
                $index++;
                break;
            }

            // Match directive with optional block start or terminal semicolon
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_~]*)\s*(.*?)\s*(\{?|;)?$/', $line, $matches)) {
                $directive = $matches[1];
                $argString = trim($matches[2]);
                $args = preg_split('/\s+/', rtrim($argString, ';'), -1, PREG_SPLIT_NO_EMPTY);

                $entry = [
                    'directive' => $directive,
                    'line' => $lineNumber,
                    'args' => $args,
                ];

                // Handle multiline arguments (e.g. server_name spanning lines)
                while (
                    $index + 1 < count($lines) &&
                    substr(trim($lines[$index]), -1) !== ';' &&
                    substr(trim($lines[$index]), -1) !== '{' &&
                    !isset($matches[3])
                ) {
                    $index++;
                    $extra = trim($lines[$index]);
                    if ($extra === '') {
                        continue;
                    }
                    $args = array_merge($args, preg_split('/\s+/', rtrim($extra, ';'), -1, PREG_SPLIT_NO_EMPTY));
                    $entry['args'] = $args;
                    $lineNumber++;
                }

                // Handle nested block
                if ($matches[3] === '{') {
                    $index++;
                    $entry['block'] = parseBlock($lines, $index, $lineNumber);
                } else {
                    // Directive ends here (with semicolon or nothing)
                    $index++;
                }

                $block[] = $entry;
            } else {
                // Line didn't match directive pattern, skip it
                $index++;
            }
        }

        return $block;
    }

    // Start parsing from the top-level blocks
    while ($index < count($lines)) {
        $line = trim($lines[$index]);
        $lineNumber++;

        if ($line === '' || $line[0] === '#') {
            $index++;
            continue;
        }

        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_~]*)\s*\{$/', $line, $matches)) {
            $index++;
            return [
                'directive' => $matches[1],
                'line' => $lineNumber,
                'args' => [],
                'block' => parseBlock($lines, $index, $lineNumber),
            ];
        }

        // Handle single-line directives at the top-level (e.g., include ...)
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_~]*)\s+(.*?)\s*;$/', $line, $matches)) {
            $args = preg_split('/\s+/', rtrim($matches[2], ';'), -1, PREG_SPLIT_NO_EMPTY);
            return [
                'directive' => $matches[1],
                'line' => $lineNumber,
                'args' => $args,
            ];
        }

        $index++;
    }

    return null;
}