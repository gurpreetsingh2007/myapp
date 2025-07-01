<?php

define('DB_HOST', '172.21.229.219');
define('DB_USER', 'gurpreet');
define('DB_PASS', 'gurpreet');
define('DB_NAME', 'nginx');
define('DB_PORT', 3306);

function getDbConnectionNginx()
{
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function convertDateTime($datetime)
{
    if (empty($datetime) || $datetime === 'unknown') {
        return null;
    }
    try {
        return (new DateTime($datetime))->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

function normalizeFileType($fileType) {
    // Handle empty/unknown cases first
    if (empty($fileType)) {
        return 'config';
    }

    // Convert to lowercase and remove special characters
    $cleanType = strtolower(trim(preg_replace('/[^a-z]/', '', $fileType)) ?: 'config');

    // Define all possible mappings
    $typeMappings = [
        'main' => 'main',
        'http' => 'http',
        'server' => 'server',
        'location' => 'location',
        'upstream' => 'upstream',
        'include' => 'include',
        'snippet' => 'snippet',
        'conf' => 'config',
        'config' => 'config',
        // Add any other mappings you encounter
    ];

    // Check direct matches first
    if (isset($typeMappings[$cleanType])) {
        return $typeMappings[$cleanType];
    }

    // Check partial matches (e.g., 'serverconfig' contains 'server')
    foreach ($typeMappings as $key => $value) {
        if (strpos($cleanType, $key) !== false) {
            return $value;
        }
    }

    // Default fallback
    return 'config';
}
function importNginxConfig($jsonFile)
{
    $conn = getDbConnectionNginx();

    // Read and validate JSON
    $jsonData = file_get_contents($jsonFile);
    if ($jsonData === false) {
        throw new Exception("Failed to read JSON file");
    }

    $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
    if ($data === null) {
        throw new Exception("Failed to decode JSON data");
    }

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Insert scan record
        $scanStmt = $conn->prepare("
            INSERT INTO nginx_scans (
                scan_date, scan_path, total_files, total_servers, 
                total_certificates, scan_status, notes
            ) VALUES (
                :scan_date, :scan_path, :total_files, :total_servers, 
                :total_certificates, :scan_status, :notes
            )
        ");
        $scanStmt->execute([
            ':scan_date' => convertDateTime($data['scan']['scan_date']),
            ':scan_path' => $data['scan']['scan_path'],
            ':total_files' => $data['scan']['total_files'] ?? 0,
            ':total_servers' => $data['scan']['total_servers'] ?? 0,
            ':total_certificates' => $data['scan']['total_certificates'] ?? 0,
            ':scan_status' => $data['scan']['scan_status'] ?? 'completed',
            ':notes' => 'Automated import from scan'
        ]);
        $scanId = $conn->lastInsertId();

        // Insert SSL certificates
        $certMap = [];
        foreach ($data['ssl_certificates'] ?? [] as $certData) {
            $certStmt = $conn->prepare("
                INSERT INTO ssl_certificates (
                    certificate_name, certificate_path, private_key_path, 
                    issuer, subject, fingerprint_sha1, scan_id
                ) VALUES (
                    :certificate_name, :certificate_path, :private_key_path, 
                    :issuer, :subject, SUBSTRING(:fingerprint_sha1, 1, 40), :scan_id
                )
            ");
            $certStmt->execute([
                ':certificate_name' => substr($certData['certificate_name'] ?? basename($certData['certificate_path'] ?? 'unknown'), 0, 255),
                ':certificate_path' => substr($certData['certificate_path'] ?? '', 0, 500),
                ':private_key_path' => isset($certData['private_key_path']) ? substr($certData['private_key_path'], 0, 500) : null,
                ':issuer' => isset($certData['issuer']) ? substr($certData['issuer'], 0, 255) : null,
                ':subject' => isset($certData['subject']) ? substr($certData['subject'], 0, 255) : null,
                ':fingerprint_sha1' => $certData['fingerprint_sha1'] ?? '',
                ':scan_id' => $scanId
            ]);
            $certId = $conn->lastInsertId();
            $certMap[$certData['certificate_path'] ?? ''] = $certId;
        }

        // Insert config files with strict type checking
        $fileMap = [];
        foreach ($data['config_files'] ?? [] as $fileData) {
            $normalizedType = normalizeFileType($fileData['file_type'] ?? 'config');

            $fileStmt = $conn->prepare("
                INSERT INTO nginx_config_files (
                    file_path, original_path, filename, file_size, file_hash, 
                    file_content, file_type, is_enabled, is_symlink, 
                    syntax_valid, syntax_errors, last_modified, scan_id
                ) VALUES (
                    :file_path, :original_path, :filename, :file_size, :file_hash, 
                    :file_content, :file_type, :is_enabled, :is_symlink, 
                    :syntax_valid, :syntax_errors, :last_modified, :scan_id
                )
            ");
            $fileStmt->execute([
                ':file_path' => substr($fileData['file_path'], 0, 500),
                ':original_path' => substr($fileData['original_path'] ?? $fileData['file_path'], 0, 500),
                ':filename' => substr($fileData['filename'], 0, 255),
                ':file_size' => $fileData['file_size'] ?? 0,
                ':file_hash' => substr($fileData['file_hash'] ?? '', 0, 64),
                ':file_content' => $fileData['file_content'] ?? '',
                ':file_type' => $normalizedType,
                ':is_enabled' => $fileData['is_enabled'] ?? true ? 1 : 0,
                ':is_symlink' => $fileData['is_symlink'] ?? false ? 1 : 0,
                ':syntax_valid' => $fileData['syntax_valid'] === null ? null : ($fileData['syntax_valid'] ?? false ? 1 : 0),
                ':syntax_errors' => isset($fileData['syntax_errors']) ? substr($fileData['syntax_errors'], 0, 65535) : null,
                ':last_modified' => convertDateTime($fileData['last_modified']),
                ':scan_id' => $scanId
            ]);
            $fileId = $conn->lastInsertId();
            $fileMap[$fileData['file_path']] = $fileId;

            // Insert servers
            foreach ($fileData['servers'] ?? [] as $serverData) {
                $serverStmt = $conn->prepare("
                    INSERT INTO nginx_servers (
                        config_file_id, server_name, listen_ports, 
                        is_default_server, is_ssl_enabled, root_directory, is_active
                    ) VALUES (
                        :config_file_id, :server_name, :listen_ports, 
                        :is_default_server, :is_ssl_enabled, :root_directory, :is_active
                    )
                ");
                $serverStmt->execute([
                    ':config_file_id' => $fileId,
                    ':server_name' => isset($serverData['server_name']) ? substr($serverData['server_name'], 0, 255) : '',
                    ':listen_ports' => isset($serverData['listen_ports']) ? substr($serverData['listen_ports'], 0, 255) : '',
                    ':is_default_server' => strpos($serverData['listen_ports'] ?? '', 'default_server') !== false ? 1 : 0,
                    ':is_ssl_enabled' => !empty($serverData['ssl_certificate']) ? 1 : 0,
                    ':root_directory' => isset($serverData['root_directory']) ? substr($serverData['root_directory'], 0, 500) : null,
                    ':is_active' => 1
                ]);
                $serverId = $conn->lastInsertId();

                // Insert SSL certificate mapping if exists
                if (!empty($serverData['ssl_certificate']) && isset($certMap[$serverData['ssl_certificate']])) {
                    $sslCertStmt = $conn->prepare("
                        INSERT INTO nginx_ssl_certificates (
                            server_id, certificate_id, ssl_certificate_directive, 
                            ssl_certificate_key_directive
                        ) VALUES (
                            :server_id, :certificate_id, :ssl_certificate_directive, 
                            :ssl_certificate_key_directive
                        )
                    ");
                    $sslCertStmt->execute([
                        ':server_id' => $serverId,
                        ':certificate_id' => $certMap[$serverData['ssl_certificate']],
                        ':ssl_certificate_directive' => isset($serverData['ssl_certificate']) ? substr($serverData['ssl_certificate'], 0, 500) : null,
                        ':ssl_certificate_key_directive' => isset($serverData['ssl_certificate_key']) ? substr($serverData['ssl_certificate_key'], 0, 500) : null
                    ]);
                }
            }
        }

        // Commit transaction
        $conn->commit();
        return [
            'success' => true,
            'scan_id' => $scanId,
            'message' => "Import completed successfully. Scan ID: $scanId"
        ];

    } catch (PDOException $e) {
        // Roll back on error
        $conn->rollBack();
        return [
            'success' => false,
            'error' => "Database error: " . $e->getMessage(),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString()
        ];
    } catch (Exception $e) {
        // Roll back on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return [
            'success' => false,
            'error' => "Error: " . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// CLI execution
if (php_sapi_name() === 'cli' && isset($argv)) {
    if ($argc < 2) {
        echo "Usage: php " . basename($argv[0]) . " <path_to_json_file>\n";
        exit(1);
    }

    $jsonFile = $argv[1];
    if (!file_exists($jsonFile)) {
        echo "Error: JSON file not found: $jsonFile\n";
        exit(1);
    }

    $result = importNginxConfig($jsonFile);

    if ($result['success']) {
        echo $result['message'] . "\n";
        exit(0);
    } else {
        echo "ERROR: " . $result['error'] . "\n";
        if (isset($result['trace']) && strpos($result['error'], 'Data truncated') !== false) {
            echo "\nDEBUG INFO:\n" . $result['trace'] . "\n";
        }
        exit(1);
    }
}