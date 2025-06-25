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

function modifiedFiles()
{
    try {
        $pdo = getDbConnection();

        // Drop the table if it exists
        $pdo->exec("DROP TABLE IF EXISTS modifiedFiles");

        // Create the table
        $sql = "CREATE TABLE modifiedFiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            path TEXT NOT NULL,
            service VARCHAR(100) NOT NULL
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        $pdo->exec($sql);
        //echo "Table 'modifiedFiles' dropped and recreated.";
    } catch (PDOException $e) {
        die("Failed to reset table: " . $e->getMessage());
    }
}

function giveModifiedFiles(): void
{
    try {
        $pdo = getDbConnection();

        $stmt = $pdo->query("SELECT id, path, service FROM modifiedFiles ORDER BY id ASC");
        $rows = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $rows
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
function addModifiedFile(string $path, string $service): void
{
    try {
        $path = trim(filter_var($path, FILTER_SANITIZE_STRING));
        $service = trim(filter_var($service, FILTER_SANITIZE_STRING));

        if ($path === '' || $service === '') {
            throw new InvalidArgumentException('Path and service cannot be empty.');
        }

        $pdo = getDbConnection();

        // Check if the path-service pair already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM modifiedFiles WHERE path = :path AND service = :service");
        $checkStmt->execute([
            ':path' => $path,
            ':service' => $service
        ]);

        if ($checkStmt->fetchColumn() > 0) {
            return; // Skip insert if already exists
        }

        // Insert new entry
        $insertStmt = $pdo->prepare("INSERT INTO modifiedFiles (path, service) VALUES (:path, :service)");
        $insertStmt->execute([
            ':path' => $path,
            ':service' => $service
        ]);
    } catch (Exception $e) {
        die("Failed to add modified file: " . $e->getMessage());
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
                last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deployed VARCHAR(1)
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
        INSERT INTO config_file_index (file_name, status, errors, deployed)
        VALUES (:file, :status, :errors, :deployed)
    ");
    $stmt->execute([
        ':file' => $file,
        ':status' => $status,
        ':errors' => json_encode($errors),
        ':deployed' => 'y'
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


function createHistoryTable()
{
    $conn = getDbConnection();
    $conn->exec("DROP TABLE IF EXISTS history_Nginx");

    $conn->exec("
        CREATE TABLE history_Nginx (
            id INT AUTO_INCREMENT PRIMARY KEY,
            editor_name VARCHAR(255),
            editor_gmail VARCHAR(255),
            edit_datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
            old_text TEXT,
            comment TEXT,
            action VARCHAR(255),
            table_edited VARCHAR(255),
            table_row_id INT,
            column_title TEXT
        )
    ");
}
function addHistoryRow($editorName, $editorGmail, $oldText, $comment, $action, $tableEdited, $tableRowId, $columnTitle)
{
    try {
        $conn = getDbConnection();

        // Step 1: Check for a recent similar row (within 5 seconds)
        $stmt = $conn->prepare("
            SELECT id FROM history_Nginx
            WHERE 
                editor_name = :editor_name AND
                editor_gmail = :editor_gmail AND
                table_edited = :table_edited AND
                table_row_id = :table_row_id AND
                column_title = :column_title AND
                edit_datetime >= NOW() - INTERVAL 10 SECOND
            ORDER BY edit_datetime DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':editor_name' => $editorName,
            ':editor_gmail' => $editorGmail,
            ':table_edited' => $tableEdited,
            ':table_row_id' => $tableRowId,
            ':column_title' => $columnTitle
        ]);

        $lastId = $stmt->fetchColumn();

        // Step 2: If a matching recent row is found, delete it
        if ($lastId) {
            // Delete the recent matching row
            $deleteStmt = $conn->prepare("DELETE FROM history_Nginx WHERE id = :id");
            $deleteStmt->execute([':id' => $lastId]);

            // Check if the deleted row had the highest ID
            $checkStmt = $conn->query("SELECT MAX(id) AS max_id FROM history_Nginx");
            $maxId = $checkStmt->fetchColumn();

            if ($maxId !== false && $lastId > $maxId) {
                // Reset AUTO_INCREMENT to reuse the ID
                $conn->exec("ALTER TABLE history_Nginx AUTO_INCREMENT = $lastId");
            }
        }

        // Step 3: Insert the new row
        $insertStmt = $conn->prepare("
            INSERT INTO history_Nginx (
                editor_name,
                editor_gmail,
                old_text,
                comment,
                action,
                table_edited,
                table_row_id,
                column_title
            ) VALUES (
                :editor_name,
                :editor_gmail,
                :old_text,
                :comment,
                :action,
                :table_edited,
                :table_row_id,
                :column_title
            )
        ");

        $insertStmt->execute([
            ':editor_name' => $editorName,
            ':editor_gmail' => $editorGmail,
            ':old_text' => $oldText,
            ':comment' => $comment,
            ':action' => $action,
            ':table_edited' => $tableEdited,
            ':table_row_id' => $tableRowId,
            ':column_title' => $columnTitle
        ]);
    } catch (PDOException $e) {
        error_log("History insert failed: " . $e->getMessage());
        throw $e;
    }
}


function createLogTable()
{
    $conn = getDbConnection();
    $conn->exec("DROP TABLE IF EXISTS log_site");

    $conn->exec("
        CREATE TABLE log_site (
            id INT AUTO_INCREMENT PRIMARY KEY,
            editor_name VARCHAR(255),
            editor_gmail VARCHAR(255),
            action VARCHAR(10),
            datetime DATETIME DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN
        )
    ");
}
function addLogRow($editorName, $editorGmail, $action, $success)
{
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        INSERT INTO log_site (
            editor_name,
            editor_gmail,
            action,
            success
        ) VALUES (
            :editor_name,
            :editor_gmail,
            :action,
            :success
        )
    ");

    $stmt->execute([
        ':editor_name' => $editorName,
        ':editor_gmail' => $editorGmail,
        ':action' => $action,
        ':success' => $success ? 1 : 0,
    ]);
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
        $jsonData = json_encode(parseNginxBlock($data['json_data']), JSON_UNESCAPED_UNICODE);

        // Insert new block
        $insertStmt = $conn->prepare("INSERT INTO `$tableName` (title, json_data) VALUES (?, ?)");
        $insertStmt->execute([$data['title'], $jsonData]);

        $newId = $conn->lastInsertId();
        addHistoryRow(
            $_SESSION['email'] ?? 'Unknown',
            $_SESSION['username'] ?? 'unknown@example.com',
            '', // oldText is empty for creation
            $data['comment'] ?? 'Created new block',
            'create',
            $tableName,
            $newId,
            $data['title']
        );
        addModifiedFile($path, 'nginx');
        // ? Mark config as not deployed
        $resetDeployStmt = $conn->prepare("UPDATE config_file_index SET deployed = 'n' WHERE file_name = ?");
        $resetDeployStmt->execute([$fileName]);

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
        $jsonDataRaw = file_get_contents('php://input');
        $data = json_decode($jsonDataRaw, true);

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

        // Check if the record exists and fetch the old values
        $checkRecordStmt = $conn->prepare("SELECT title, json_data FROM `$tableName` WHERE id = ?");
        $checkRecordStmt->execute([$id]);
        $existing = $checkRecordStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Block not found']);
            return;
        }

        // Prepare fields
        $updateFields = [];
        $params = [];

        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
        }

        if (isset($data['json_data'])) {
            $updateFields[] = "json_data = ?";
            $params[] = json_encode($data['json_data'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        // Add id to params
        $params[] = $id;
        $updateStmt = $conn->prepare("UPDATE `$tableName` SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $updateStmt->execute($params);

        // ? Mark config as not deployed
        $resetDeployStmt = $conn->prepare("UPDATE config_file_index SET deployed = 'n' WHERE file_name = ?");
        $resetDeployStmt->execute([$fileName]);


        // Update block
        $updateStmt = $conn->prepare("UPDATE `$tableName` SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $updateStmt->execute($params);

        // Log history

        $oldText = '{ "directive" : "' . $existing['title'] . '"}';
        addHistoryRow(
            $_SESSION['username'] ?? 'Unknown',
            $_SESSION['email'] ?? 'unknown@example.com',
            $oldText,
            $data['comment'] ?? 'Updated block title',
            'update',
            $tableName,
            $id,
            implode(',', array_keys($data)),
        );
        addModifiedFile($path, 'nginx');
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

        // Get optional comment from request body (if any)
        $jsonRaw = file_get_contents('php://input');
        $data = json_decode($jsonRaw, true) ?? [];
        $comment = $data['comment'] ?? ($_GET['comment'] ?? 'Deleted block');

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

        // Fetch block to preserve data for history
        $fetchStmt = $conn->prepare("SELECT title, json_data FROM `$tableName` WHERE id = ?");
        $fetchStmt->execute([$id]);
        $block = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        if (!$block) {
            http_response_code(404);
            echo json_encode(['error' => 'Block not found']);
            return;
        }

        // Delete block
        $deleteStmt = $conn->prepare("DELETE FROM `$tableName` WHERE id = ?");
        $deleteStmt->execute([$id]);

        // Build oldText
        $oldText = $block['json_data'];

        // Add history (using keys from existing block)
        addHistoryRow(
            $_SESSION['username'] ?? 'Unknown',
            $_SESSION['email'] ?? 'unknown@example.com',
            $oldText,
            $comment,
            'delete',
            $tableName,
            $id,
            'title,json_data'
        );
        addModifiedFile($path, 'nginx');
        // ? Mark config as not deployed
        $resetDeployStmt = $conn->prepare("UPDATE config_file_index SET deployed = 'n' WHERE file_name = ?");
        $resetDeployStmt->execute([$fileName]);

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
        $jsonContent = json_encode(parseNginxBlock($data['data']), JSON_UNESCAPED_UNICODE);

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

        // Check if record exists and fetch old json_data
        $checkRecordStmt = $conn->prepare("SELECT json_data FROM `$tableName` WHERE id = ?");
        $checkRecordStmt->execute([$id]);
        $existing = $checkRecordStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Block not found']);
            return;
        }

        $oldText = $existing['json_data'];

        // Update json_data
        $updateStmt = $conn->prepare("UPDATE `$tableName` SET json_data = ? WHERE id = ?");
        $updateStmt->execute([$jsonContent, $id]);

        // Add history row
        addHistoryRow(
            $_SESSION['username'] ?? 'Unknown',
            $_SESSION['email'] ?? 'unknown@example.com',
            $oldText,
            $data['comment'] ?? 'Updated JSON data',
            'update',
            $tableName,
            $id,
            'json_data'
        );
        addModifiedFile($path, 'nginx');
        // ? Mark config as not deployed
        $resetDeployStmt = $conn->prepare("UPDATE config_file_index SET deployed = 'n' WHERE file_name = ?");
        $resetDeployStmt->execute([$fileName]);

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

function giveHistory()
{
    try {
        $conn = getDbConnection();

        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        $stmt = $conn->prepare("SELECT * FROM history_Nginx ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
function searchHistory($searchQuery)
{
    try {
        $conn = getDbConnection();

        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        // Sanitize input
        $cleanQuery = trim(preg_replace('/[^a-zA-Z0-9\s\-_:@.]/', ' ', $searchQuery));
        $cleanQuery = preg_replace('/\s+/', ' ', $cleanQuery);
        $keywords = array_slice(explode(' ', $cleanQuery), 0, 10);

        $conditions = [];
        $params = [];

        foreach ($keywords as $index => $word) {
            $kwParam = ":kw$index";
            $likePattern = '%' . $word . '%';

            $conditions[] = "(editor_name LIKE $kwParam
                OR editor_gmail LIKE $kwParam
                OR old_text LIKE $kwParam
                OR comment LIKE $kwParam
                OR action LIKE $kwParam
                OR table_edited LIKE $kwParam
                OR column_title LIKE $kwParam)";
            $params[$kwParam] = $likePattern;

            if (ctype_digit($word)) {
                $numParam = ":num$index";
                $conditions[] = "(id = $numParam OR table_row_id = $numParam)";
                $params[$numParam] = intval($word);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $word)) {
                $dtParam = ":dt$index";
                $conditions[] = "DATE(edit_datetime) = $dtParam";
                $params[$dtParam] = $word;
            }
        }

        $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT * FROM history_Nginx
            $whereClause
            ORDER BY edit_datetime DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $conn->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}


// nigger lavora
function searchKeyword($searchQuery) {
    try {
        $conn = getDbConnection();
        $fileName = isset($_GET['path']) ? $_GET['path'] : '';

        $tableName = 'cfg_' . strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        if (!preg_match('/^cfg_[a-z0-9_]+$/', $tableName)) {
            throw new Exception('Invalid table name format');
        }

        $searchTerm = trim($searchQuery);
        $searchTermWildcard = '%' . $searchTerm . '%';

        $sql = "
            SELECT 
                id,
                title
            FROM `$tableName`
            WHERE 
                title LIKE :searchTermWildcard
                OR
                json_data LIKE :searchTermWildcard
            ORDER BY id DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':searchTermWildcard', $searchTermWildcard);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}


function parseDirective(array $data, int $indent = 0): string
{
    $indentStr = str_repeat('  ', $indent);
    $result = '';

    if (empty($data))
        return '';

    if (isset($data['directive'])) {
        $result .= $indentStr . $data['directive'];
        if (!empty($data['args'])) {
            $result .= ' ' . implode(' ', $data['args']);
        }
    }

    if (!empty($data['block'])) {
        if (isset($data['directive']))
            $result .= ' ';
        $result .= "{\n";
        foreach ($data['block'] as $item) {
            $child = parseDirective($item, $indent + 1);
            if (trim($child) !== '')
                $result .= $child . "\n";
        }
        $result .= $indentStr . '}';
    } elseif (isset($data['directive'])) {
        $result .= ';';
    }

    return $result;
}

function sendFiles($input)
{
    if (!isset($input['files']) || !is_array($input['files'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing or invalid 'files' key"]);
        exit;
    }

    $filenames = array_map('trim', $input['files']);
    $conn = getDbConnection();
    $results = [];

    foreach ($filenames as $file) {
        $tableName = 'cfg_' . strtolower(pathinfo($file, PATHINFO_FILENAME));
        try {
            $sql = "SELECT * FROM `$tableName`";
            $stmt = $conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $parsedConfigs = [];
            foreach ($rows as $row) {
                if (!empty($row['json_data'])) {
                    $json = json_decode($row['json_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $parsed = parseDirective($json);
                        if (!empty($parsed)) {
                            $parsedConfigs[] = $parsed;
                        }
                    }
                }
            }

            if (!empty($parsedConfigs)) {
                $results[] = [
                    'filename' => $file,
                    'config' => implode("\n\n", $parsedConfigs)
                ];
            }
            //echo json_encode($parsedConfigs, true);
        } catch (Exception $e) {
            // Skip table if it doesn't exist or has error
            echo "file error";
            continue;
        }


    }

    // Send parsed data to backend
    $ch = curl_init("http://127.0.0.1:8081/push");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["data" => $results, "command" => $input['command']]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo json_encode([
        "success" => true,
        "httpCode" => $httpCode,
        "response" => $response
    ]);
}

function sendServerList()
{
    $url = 'http://127.0.0.1:8081/serverList';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Request failed with HTTP code $httpCode\n";
        return;
    }

    echo $response;
}

function sendPartialFilesServer($input)
{
    if (
        !isset($input['files']) || !is_array($input['files']) ||
        !isset($input['servers']) || !is_array($input['servers']) ||
        !isset($input['command'])
    ) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'files', 'servers', or 'command'"]);
        exit;
    }

    $filenames = array_map('trim', $input['files']);
    $targetServers = array_map('trim', $input['servers']);
    $command = $input['command'];

    $conn = getDbConnection();
    $results = [];

    foreach ($filenames as $file) {
        $tableName = 'cfg_' . strtolower(pathinfo($file, PATHINFO_FILENAME));
        try {
            $sql = "SELECT * FROM `$tableName`";
            $stmt = $conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $parsedConfigs = [];
            foreach ($rows as $row) {
                if (!empty($row['json_data'])) {
                    $json = json_decode($row['json_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $parsed = parseDirective($json);
                        if (!empty($parsed)) {
                            $parsedConfigs[] = $parsed;
                        }
                    }
                }
            }

            if (!empty($parsedConfigs)) {
                $results[] = [
                    'filename' => $file,
                    'config' => implode("\n\n", $parsedConfigs)
                ];
            }
        } catch (Exception $e) {
            // Skip on error (e.g. missing table)
            continue;
        }
    }

    $payload = [
        'data' => $results,
        'command' => $command,
        'targets' => $targetServers
    ];

    $ch = curl_init("http://127.0.0.1:8081/push");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo json_encode([
        "success" => true,
        "httpCode" => $httpCode,
        "response" => $response
    ]);
}

#rsnapshot; 

function createRsnapshotTable(): void
{
    $pdo = getDbConnection();
    $sql = "CREATE TABLE IF NOT EXISTS cfg_rsnapshot (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('general', 'backup') NOT NULL,
        directive VARCHAR(50) NOT NULL,
        args JSON NULL,
        source TEXT NULL,
        dest TEXT NULL,
        parameters JSON NULL
    )";
    $pdo->exec($sql);
}

function loadDataJsonRsnapshot(): void
{
    $jsonPath = __DIR__ . '/../data/rsnapshot.json';
    $pdo = getDbConnection();
    // Read and decode JSON file
    $jsonData = file_get_contents($jsonPath);
    $data = json_decode($jsonData, true);

    // Create table if not exists
    createRsnapshotTable();

    // Prepare insert statement
    $stmt = $pdo->prepare("
        INSERT INTO cfg_rsnapshot (type, directive, args, source, dest, parameters)
        VALUES (:type, :directive, :args, :source, :dest, :parameters)
    ");

    // Process general block
    foreach ($data['general'] as $item) {
        $stmt->execute([
            ':type' => 'general',
            ':directive' => $item['directive'],
            ':args' => json_encode($item['args']),
            ':source' => null,
            ':dest' => null,
            ':parameters' => null
        ]);
    }

    // Process backup block
    foreach ($data['backup'] as $item) {
        $stmt->execute([
            ':type' => 'backup',
            ':directive' => $item['directive'],
            ':args' => null,
            ':source' => $item['source'],
            ':dest' => $item['dest'],
            ':parameters' => json_encode($item['parameters'])
        ]);
    }
}


/**
 * Send the entire rsnapshot configuration as JSON, grouped by type.
 */
function sendRsnapshot()
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM cfg_rsnapshot ORDER BY type, id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $output = [
            'general' => [],
            'backup' => []
        ];

        foreach ($rows as $row) {
            $item = [
                'id' => (int) $row['id'],
                'directive' => $row['directive'],
            ];
            if ($row['type'] === 'general') {
                $item['args'] = $row['args'] ? json_decode($row['args'], true) : [];
                $output['general'][] = $item;
            } else {
                // backup
                $item['source'] = $row['source'];
                $item['dest'] = $row['dest'];
                $item['parameters'] = $row['parameters'] ? json_decode($row['parameters'], true) : [];
                $output['backup'][] = $item;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($output, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}


/**
 * Update a single rsnapshot entry.
 * Expected JSON payload:
 * {
 *   "id": 123,
 *   "directive": "backup",
 *   "args": ["..."],            // for general entries
 *   "source": "rsync://...",
 *   "dest": "lxmail/",
 *   "parameters": { ... },      // assoc array of extra params
 *   "comment": "why you changed"
 * }
 */

function updateRsnapshot()
{
    try {
        $pdo = getDbConnection();

        // Read and parse incoming JSON
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: id']);
            return;
        }
        $id = (int) $data['id'];

        // Fetch existing row
        $fetch = $pdo->prepare("SELECT * FROM cfg_rsnapshot WHERE id = ?");
        $fetch->execute([$id]);
        $old = $fetch->fetch(PDO::FETCH_ASSOC);
        if (!$old) {
            http_response_code(404);
            echo json_encode(['error' => 'Record not found']);
            return;
        }

        // Build update clause dynamically  
        $fields = [];
        $params = [];

        if (isset($data['directive'])) {
            $fields[] = 'directive = :directive';
            $params[':directive'] = $data['directive'];
        }
        if (isset($data['args'])) {
            $fields[] = 'args = :args';
            $params[':args'] = json_encode($data['args'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($data['source'])) {
            $fields[] = 'source = :source';
            $params[':source'] = $data['source'];
        }
        if (isset($data['dest'])) {
            $fields[] = 'dest = :dest';
            $params[':dest'] = $data['dest'];
        }
        if (isset($data['parameters'])) {
            $fields[] = 'parameters = :parameters';
            $params[':parameters'] = json_encode($data['parameters'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No updatable fields provided']);
            return;
        }

        $params[':id'] = $id;
        $sql = "UPDATE cfg_rsnapshot SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Record history
        addHistoryRow(
            $_SESSION['username'] ?? 'Unknown',
            $_SESSION['email'] ?? 'unknown@example.com',
            json_encode($old, JSON_UNESCAPED_UNICODE),
            $data['comment'] ?? 'Updated rsnapshot entry',
            'update',
            'cfg_rsnapshot',
            $id,
            implode(', ', array_map(function ($f) {
                return trim(explode('=', $f)[0]);
            }, $fields))
        );
        addModifiedFile('rsnapshot.conf', 'rsnapshot');
        // Mark config as not deployed
        $reset = $pdo->prepare("UPDATE config_file_index SET deployed = 'n' WHERE file_name = 'rsnapshot.conf'");
        $reset->execute();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Record updated']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Insert a new rsnapshot entry.
 * Expected JSON payload:
 * {
 *   "type": "general"|"backup",
 *   "directive": "snapshot_root"|"backup"|…,
 *   // if type="general":
 *   "args": ["val1", "val2", …],
 *   // if type="backup":
 *   "source": "rsync://…",
 *   "dest": "path/",
 *   "parameters": { "param1": "value1", … },
 *   "comment": "why you created this"
 * }
 */
function addRsnapshot()
{
    try {
        $pdo = getDbConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (empty($data['type']) || empty($data['directive'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: type or directive']);
            return;
        }

        // Prepare columns and values
        $columns = ['type', 'directive'];
        $placeholders = [':type', ':directive'];
        $params = [
            ':type' => $data['type'],
            ':directive' => $data['directive']
        ];

        // Handle general vs. backup specifics
        if ($data['type'] === 'general') {
            $columns[] = 'args';
            $placeholders[] = ':args';
            $params[':args'] = isset($data['args'])
                ? json_encode($data['args'], JSON_UNESCAPED_UNICODE)
                : json_encode([], JSON_UNESCAPED_UNICODE);
        } else {
            // backup
            $columns = array_merge($columns, ['source', 'dest', 'parameters']);
            $placeholders = array_merge($placeholders, [':source', ':dest', ':parameters']);
            $params[':source'] = $data['source'] ?? null;
            $params[':dest'] = $data['dest'] ?? null;
            $params[':parameters'] = isset($data['parameters'])
                ? json_encode($data['parameters'], JSON_UNESCAPED_UNICODE)
                : json_encode([], JSON_UNESCAPED_UNICODE);
        }

        // Build and execute INSERT
        $sql = sprintf(
            "INSERT INTO cfg_rsnapshot (%s) VALUES (%s)",
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Get new ID
        $newId = (int) $pdo->lastInsertId();

        // Record creation in history (old_text empty)
        addHistoryRow(
            $_SESSION['username'] ?? 'Unknown',
            $_SESSION['email'] ?? 'unknown@example.com',
            '',                              // no old data
            $data['comment'] ?? 'Created new rsnapshot entry',
            'create',
            'cfg_rsnapshot',
            $newId,
            implode(', ', $columns)
        );

        addModifiedFile('rsnapshot.conf', 'rsnapshot');
        // Mark config as not deployed
        $reset = $pdo->prepare("
            UPDATE config_file_index 
               SET deployed = 'n' 
             WHERE file_name = 'rsnapshot.conf'
        ");
        $reset->execute();

        // Return success
        header('Content-Type: application/json');
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'New rsnapshot entry created'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteRsnapshot()
{
    try {
        $pdo = getDbConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required ID
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required field: id']);
            return;
        }
        $id = (int) $data['id'];

        // Fetch existing row
        $stmt = $pdo->prepare("SELECT * FROM cfg_rsnapshot WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$old) {
            http_response_code(404);
            echo json_encode(['error' => 'Record not found']);
            return;
        }

        // Delete the row
        $del = $pdo->prepare("DELETE FROM cfg_rsnapshot WHERE id = ?");
        $del->execute([$id]);

        // Record deletion in history (old text is JSON of the old row)
        addHistoryRow(
            $_SESSION['username'] ?? 'Unknown',
            $_SESSION['email'] ?? 'unknown@example.com',
            json_encode($old, JSON_UNESCAPED_UNICODE),
            $data['comment'] ?? 'Deleted rsnapshot entry',
            'delete',
            'cfg_rsnapshot',
            $id,
            implode(', ', array_keys($old))
        );

        addModifiedFile('rsnapshot.conf', 'rsnapshot');
        // Mark config as not deployed
        $reset = $pdo->prepare("
            UPDATE config_file_index 
               SET deployed = 'n' 
             WHERE file_name = 'rsnapshot.conf'
        ");
        $reset->execute();

        // Return success
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Record deleted'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}