<?php
session_start();
// Database configuration
define('DB_HOST', '172.21.229.219');
define('DB_USER', 'gurpreet');
define('DB_PASS', 'gurpreet');
define('DB_NAME', 'nginx_reverse_proxy');
define('DB_PORT', 3306);

class NginxReverseProxyDB
{
    private $conn;
    private $minTimeBetweenLogs = 300;
    public function __construct()
    {
        $this->conn = $this->getDbConnection();
    }

    private function getDbConnection()
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
    /**
     * Log a change to the history table
     * 
     * @param string $tableName Name of the table being modified
     * @param int $recordId ID of the record being modified
     * @param string $actionType One of: INSERT, UPDATE, DELETE
     * @param mixed $oldData Data before change (for UPDATE/DELETE)
     * @param mixed $newData Data after change (for INSERT/UPDATE)
     * @param string|null $changedBy User making the change
     * @param string|null $changeReason Reason for the change
     * @return bool Whether logging was successful
     */
    private function logChange(
        string $tableName,
        int $recordId,
        string $actionType,
        $oldData = null,
        $newData = null,
        ?string $changedBy = null,
        ?string $changeReason = null
    ): bool {
        // Check if a similar log entry exists within the time threshold
        $this->removeRecentDuplicate($tableName, $recordId, $actionType);

        // Prepare data for insertion
        $logData = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action_type' => $actionType,
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'changed_by' => $changedBy,
            'change_reason' => $changeReason
        ];

        // Insert the new history record
        $sql = "INSERT INTO configuration_history 
                (table_name, record_id, action_type, old_data, new_data, changed_by, change_reason)
                VALUES 
                (:table_name, :record_id, :action_type, :old_data, :new_data, :changed_by, :change_reason)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($logData);
    }

    /**
     * Remove recent duplicate log entries for the same record
     * 
     * @param string $tableName
     * @param int $recordId
     * @param string $actionType
     * @return int Number of rows deleted
     */
    private function removeRecentDuplicate(
        string $tableName,
        int $recordId,
        string $actionType
    ): int {
        $sql = "DELETE FROM configuration_history 
                WHERE table_name = :table_name 
                AND record_id = :record_id 
                AND action_type = :action_type
                AND changed_at >= DATE_SUB(NOW(), INTERVAL :seconds SECOND)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':action_type' => $actionType,
            ':seconds' => $this->minTimeBetweenLogs
        ]);

        return $stmt->rowCount();
    }

    /**
     * Get history for a specific record
     * 
     * @param string $tableName
     * @param int $recordId
     * @param int $limit Maximum number of records to return
     * @return array Array of history records
     */
    public function getHistoryForRecord(
        string $tableName,
        int $recordId,
        int $limit = 100
    ): array {
        $sql = "SELECT * FROM configuration_history
                WHERE table_name = :table_name
                AND record_id = :record_id
                ORDER BY changed_at DESC
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':limit' => $limit
        ]);

        $results = $stmt->fetchAll();

        // Decode JSON data
        foreach ($results as &$row) {
            if ($row['old_data']) {
                $row['old_data'] = json_decode($row['old_data'], true);
            }
            if ($row['new_data']) {
                $row['new_data'] = json_decode($row['new_data'], true);
            }
        }

        return $results;
    }
    public function getServerList()
    {
        $sql = "SELECT 
                    r.server_id,
                    r.server_title,
                    r.port,
                    r.server_name,
                    r.is_http2,
                    r.is_websocket_enabled,
                    CASE 
                        WHEN r.ssl_cert_id IS NOT NULL THEN 'Yes'
                        ELSE 'No'
                    END AS has_ssl,
                    COUNT(DISTINCT sl.location_id) AS location_count,
                    c.cert_name
                FROM reverse_proxy r
                LEFT JOIN server_locations sl ON r.server_id = sl.server_id
                LEFT JOIN certificates c ON r.ssl_cert_id = c.cert_id
                GROUP BY r.server_id";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Update server block configuration
     * @param int $serverId Server ID to update
     * @param array $data Update data (keys: server_title, port, etc.)
     * @return bool Update success
     */
    public function updateServer($serverId, array $data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // First get the current server data
        $currentData = $this->getServerConfig($serverId);

        $allowed = ['server_title', 'port', 'server_name', 'ssl_cert_id', 'is_http2', 'is_websocket_enabled'];
        $updates = [];
        $params = [':serverId' => $serverId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates))
            return false;

        $sql = "UPDATE reverse_proxy SET " . implode(', ', $updates) . " WHERE server_id = :serverId";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            // Get the updated data and log the change
            $newData = $this->getServerConfig($serverId);
            $this->logChange(
                'reverse_proxy',
                $serverId,
                'UPDATE',
                $currentData,
                $newData,
                $_SESSION['username'] ?? 'system',
                'Server configuration updated'
            );
        }

        return $result;
    }

    /**
     * Delete server block and related configurations
     * @param int $serverId Server ID to delete
     * @return bool Delete success
     */
    public function deleteServer($serverId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // First get the current server data
        $currentData = $this->getServerConfig($serverId);

        $sql = "DELETE FROM reverse_proxy WHERE server_id = ?";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([$serverId]);

        if ($result) {
            $this->logChange(
                'reverse_proxy',
                $serverId,
                'DELETE',
                $currentData,
                null,
                $_SESSION['username'] ?? 'system',
                'Server deleted'
            );
        }

        return $result;
    }

    // =============================================
    // Certificate Operations (Enhanced)
    // =============================================

    public function getCertificate($certId)
    {
        $sql = "SELECT * FROM certificates WHERE cert_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$certId]);
        return $stmt->fetch();
    }

    public function updateCertificate($certId, array $data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // First get the current certificate data
        $currentData = $this->getCertificate($certId);

        $allowed = [
            'cert_name',
            'cert_path',
            'key_path',
            'issuer',
            'subject',
            'valid_from',
            'valid_to',
            'serial_number',
            'fingerprint',
            'algorithm',
            'key_size',
            'is_self_signed',
            'notes'
        ];
        $updates = [];
        $params = [':certId' => $certId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates))
            return false;

        $sql = "UPDATE certificates SET " . implode(', ', $updates) . " WHERE cert_id = :certId";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            // Get the updated data and log the change
            $newData = $this->getCertificate($certId);
            $this->logChange(
                'certificates',
                $certId,
                'UPDATE',
                $currentData,
                $newData,
                $_SESSION['username'] ?? 'system',
                'Certificate updated'
            );
        }

        return $result;
    }

    public function deleteCertificate($certId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // First get the current certificate data
        $currentData = $this->getCertificate($certId);

        $sql = "DELETE FROM certificates WHERE cert_id = ?";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([$certId]);

        if ($result) {
            $this->logChange(
                'certificates',
                $certId,
                'DELETE',
                $currentData,
                null,
                $_SESSION['username'] ?? 'system',
                'Certificate deleted'
            );
        }

        return $result;
    }

    // =============================================
    // Parameter Operations (Enhanced)
    // =============================================

    public function updateParameter($paramId, array $data)
    {
        $allowed = ['param_name', 'param_value', 'description', 'is_common'];
        $updates = [];
        $params = [':paramId' => $paramId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates))
            return false;

        $sql = "UPDATE params SET " . implode(', ', $updates) . " WHERE param_id = :paramId";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteParameter($paramId)
    {
        $sql = "DELETE FROM params WHERE param_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$paramId]);
    }

    // =============================================
    // Location Operations (Enhanced)
    // =============================================

    public function getLocation($locationId)
    {
        // Get basic location info
        $sql = "SELECT * FROM locations WHERE location_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$locationId]);
        $location = $stmt->fetch();

        if (!$location) {
            return null;
        }

        // Get parameters for this location
        $sql = "SELECT p.* FROM params p
            JOIN location_params lp ON p.param_id = lp.param_id
            WHERE lp.location_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$locationId]);
        $location['parameters'] = $stmt->fetchAll();

        return $location;
    }

    public function updateLocation($locationId, array $data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // First get the current location data
        $currentData = $this->getLocation($locationId);

        $allowed = ['path_pattern', 'proxy_to'];
        $updates = [];
        $params = [':locationId' => $locationId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates))
            return false;

        $sql = "UPDATE locations SET " . implode(', ', $updates) . " WHERE location_id = :locationId";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            // Get the updated data and log the change
            $newData = $this->getLocation($locationId);
            $this->logChange(
                'locations',
                $locationId,
                'UPDATE',
                $currentData,
                $newData,
                $_SESSION['username'] ?? 'system',
                'Location updated'
            );
        }

        return $result;
    }

    public function deleteLocation($locationId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // First get the current location data
        $currentData = $this->getLocation($locationId);

        $sql = "DELETE FROM locations WHERE location_id = ?";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([$locationId]);

        if ($result) {
            $this->logChange(
                'locations',
                $locationId,
                'DELETE',
                $currentData,
                null,
                $_SESSION['username'] ?? 'system',
                'Location deleted'
            );
        }

        return $result;
    }

    // =============================================
    // Relationship Management
    // =============================================

    /**
     * Get all locations for a server
     * @param int $serverId Server ID
     * @return array Locations with order
     */
    public function getServerLocations($serverId)
    {
        $sql = "SELECT l.*, sl.location_order 
            FROM server_locations sl
            JOIN locations l ON sl.location_id = l.location_id
            WHERE sl.server_id = ?
            ORDER BY sl.location_order";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$serverId]);
        return $stmt->fetchAll();
    }

    /**
     * Update location order in server block
     * @param int $serverId Server ID
     * @param int $locationId Location ID
     * @param int $newOrder New order position
     * @return bool Update success
     */
    public function updateLocationOrder($serverId, $locationId, $newOrder)
    {
        $sql = "UPDATE server_locations 
                SET  = :newOrder 
                WHERE server_id = :serverId AND location_id = :locationId";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':serverId' => $serverId,
            ':locationId' => $locationId,
            ':newOrder' => $newOrder
        ]);
    }

    /**
     * Remove location from server configuration
     * @param int $serverId Server ID
     * @param int $locationId Location ID
     * @return bool Removal success
     */
    public function removeLocationFromServer($serverId, $locationId)
    {
        $sql = "DELETE FROM server_locations 
                WHERE server_id = :serverId AND location_id = :locationId";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':serverId' => $serverId,
            ':locationId' => $locationId
        ]);
    }

    // =============================================
    // Existing Methods (From Original Implementation)
    // =============================================

    // ... (All your existing methods like addCertificate(), getCertificates(), 
    // addParameter(), addLocation(), addReverseProxyServer(), getServerConfig(), etc.)
    // Make sure to keep them here unchanged

    public function getConnection()
    {
        return $this->conn;
    }

    // Certificate operations
    public function addCertificate($data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sql = "INSERT INTO certificates (cert_name, cert_path, key_path, issuer, subject, valid_from, valid_to, 
                    serial_number, fingerprint, algorithm, key_size, is_self_signed, notes) 
                    VALUES (:cert_name, :cert_path, :key_path, :issuer, :subject, :valid_from, :valid_to, 
                    :serial_number, :fingerprint, :algorithm, :key_size, :is_self_signed, :notes)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($data);

        if ($result) {
            $certId = $this->conn->lastInsertId();
            $this->logChange(
                'certificates',
                $certId,
                'INSERT',
                null,
                $data,
                $_SESSION['username'] ?? 'system',
                'Certificate added'
            );
            return $certId;
        }

        return $result;
    }

    public function getCertificates()
    {
        $sql = "SELECT * FROM certificates";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    // Parameter operations
    public function addParameter($name, $value, $description = '', $isCommon)
    {
        $sql = "INSERT INTO params (param_name, param_value, description, is_common) 
                    VALUES (:param_name, :param_value, :description, :is_common)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'param_name' => trim((string)$name),
            'param_value' =>  $value,
            'description' => $description,
            'is_common' => (int) $isCommon
        ]);
    }

    public function getParameters()
    {
        $sql = "SELECT * FROM params";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    // Location operations
    public function addLocation($path, $proxyTo)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if proxy_to already exists
    $sql = "SELECT location_id FROM locations WHERE proxy_to = :proxyTo";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['proxyTo' => $proxyTo]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Optionally return the existing ID, or false/null to indicate a duplicate
        return $existing['location_id'];
    }

    // Insert new location
    $sql = "INSERT INTO locations (path_pattern, proxy_to) 
            VALUES (:path, :proxyTo)";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        'path' => $path,
        'proxyTo' => $proxyTo
    ]);

    $locationId = $this->conn->lastInsertId();

    $this->logChange(
        'locations',
        $locationId,
        'INSERT',
        null,
        [
            'path_pattern' => $path,
            'proxy_to' => $proxyTo
        ],
        $_SESSION['username'] ?? 'system',
        'Location added'
    );

    return $locationId;
}


    public function getLocations()
    {
        $sql = "SELECT * FROM locations";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    // Server block operations
    public function addReverseProxyServer($title, $port, $serverName, $sslCertId = null, $options = [])
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $defaults = [
            'is_http2' => 0,
            'is_websocket_enabled' => 0,
        ];

        $options = array_merge($defaults, $options);

        $sql = "INSERT INTO reverse_proxy (server_title, port, server_name, ssl_cert_id, is_http2, 
                is_websocket_enabled) 
                VALUES (:title, :port, :serverName, :sslCertId, :isHttp2, :isWebsocketEnabled)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'port' => $port,
            'serverName' => $serverName,
            'sslCertId' => $sslCertId,
            'isHttp2' => (int) $options['is_http2'],
            'isWebsocketEnabled' => (int) $options['is_websocket_enabled'],
        ]);

        $serverId = $this->conn->lastInsertId();

        $this->logChange(
            'reverse_proxy',
            $serverId,
            'INSERT',
            null,
            [
                'server_title' => $title,
                'port' => $port,
                'server_name' => $serverName,
                'ssl_cert_id' => $sslCertId,
                'is_http2' => $options['is_http2'],
                'is_websocket_enabled' => $options['is_websocket_enabled']
            ],
            $_SESSION['username'] ?? 'system',
            'Server added'
        );

        return $serverId;
    }

    public function getReverseProxyServers()
    {
        $sql = "SELECT rp.*, c.cert_name, c.cert_path, c.key_path 
                    FROM reverse_proxy rp 
                    LEFT JOIN certificates c ON rp.ssl_cert_id = c.cert_id";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    // Relationship operations
    public function addLocationToServer($serverId, $locationId)
    {
        $sql = "INSERT INTO server_locations (server_id, location_id) 
                    VALUES (:serverId, :locationId)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'serverId' => $serverId,
            'locationId' => $locationId,
        ]);
    }

    public function addParameterToLocation($locationId, $paramId, $order = null)
    {
        $sql = "INSERT INTO location_params (location_id, param_id) 
                    VALUES (:locationId, :paramId)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'locationId' => $locationId,
            'paramId' => $paramId
        ]);
    }

    public function addParameterToServer($serverId, $paramId, $order = null)
    {
        $sql = "INSERT INTO server_params (server_id, param_id) 
                    VALUES (:serverId, :paramId)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'serverId' => $serverId,
            'paramId' => $paramId
        ]);
    }

    // Complex query
    public function getServerConfig($serverId)
{
    // Fetch server block
    $sql = "SELECT * FROM reverse_proxy WHERE server_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$serverId]);
    $server = $stmt->fetch();

    if (!$server) {
        return null;
    }

    // Fetch certificate details if SSL is used
    if (!empty($server['ssl_cert_id'])) {
        $sql = "SELECT * FROM certificates WHERE cert_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$server['ssl_cert_id']]);
        $server['certificate'] = $stmt->fetch();
    }

    // Fetch server parameters (excluding param_order since it's not in the schema)
    $sql = "SELECT p.*
            FROM server_params sp
            JOIN params p ON sp.param_id = p.param_id
            WHERE sp.server_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$serverId]);
    $server['parameters'] = $stmt->fetchAll();

    // Fetch server locations
    $sql = "SELECT l.*
            FROM server_locations sl
            JOIN locations l ON sl.location_id = l.location_id
            WHERE sl.server_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$serverId]);
    $locations = $stmt->fetchAll();

    // Fetch parameters for each location
    foreach ($locations as &$location) {
        $sql = "SELECT p.*
                FROM location_params lp
                JOIN params p ON lp.param_id = p.param_id
                WHERE lp.location_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$location['location_id']]);
        $location['parameters'] = $stmt->fetchAll();
    }

    $server['locations'] = $locations;

    return $server;
}
/**
 * Search configuration history with pagination
 * 
 * @param string $query Search query
 * @param int $offset Pagination offset
 * @param int $limit Number of results per page
 * @return array Search results with total count
 */
    /**
     * Search configuration history with pagination
     * 
     * @param string $query Search query
     * @param int $offset Pagination offset
     * @param int $limit Number of results per page
     * @return array Search results with total count
     */
    public function searchHistory($query, $offset = 0, $limit = 10)
    {
        $searchTerm = '%' . $query . '%';
        $offset = (int) $offset;
        $limit = (int) $limit;

        // Get all column names from the table
        $columns = $this->getTableColumns('configuration_history');

        // Build the WHERE clause for all searchable columns
        $whereClauses = [];
        $params = [];

        foreach ($columns as $column) {
            // Skip binary/large data columns if they exist
            if (in_array($column, ['old_data', 'new_data'])) {
                $whereClauses[] = "CAST($column AS CHAR) LIKE ?";
                $params[] = $searchTerm;
            } elseif (!in_array($column, ['binary_data_column'])) { // Skip any binary columns
                $whereClauses[] = "$column LIKE ?";
                $params[] = $searchTerm;
            }
        }

        if (empty($whereClauses)) {
            return ['total' => 0, 'results' => []];
        }

        $whereClause = implode(' OR ', $whereClauses);

        // Count query
        $countSql = "SELECT COUNT(*) AS total FROM configuration_history WHERE $whereClause";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Results query
        $resultSql = "SELECT * FROM configuration_history 
                 WHERE $whereClause
                 ORDER BY changed_at DESC LIMIT ?, ?";

        // Add pagination parameters
        $params[] = $offset;
        $params[] = $limit;

        $stmt = $this->conn->prepare($resultSql);

        // Bind all parameters with proper types
        foreach ($params as $index => $param) {
            $paramType = PDO::PARAM_STR;
            // Last two parameters are the pagination integers
            if ($index >= count($params) - 2) {
                $paramType = PDO::PARAM_INT;
            }
            $stmt->bindValue($index + 1, $param, $paramType);
        }

        $stmt->execute();
        $results = $stmt->fetchAll();

        // Process all columns in the results
        foreach ($results as &$row) {
            foreach ($row as $key => &$value) {
                if ($value === null) {
                    continue;
                }

                // Handle JSON data
                if (in_array($key, ['old_data', 'new_data'])) {
                    $value = json_decode($value, true);
                }
                // Format timestamps
                elseif (strpos($key, '_at') !== false || strpos($key, 'date') !== false) {
                    $value = $this->formatDatabaseDateTime($value);
                }
                // Convert numeric values
                elseif (is_numeric($value)) {
                    $value = strpos($value, '.') !== false ? (float) $value : (int) $value;
                }
            }
        }

        return [
            'total' => $total,
            'results' => $results
        ];
    }

    /**
     * Get all column names for a table
     */
    private function getTableColumns($tableName)
    {
        $stmt = $this->conn->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $columns;
    }

    /**
     * Format database datetime to a more readable format
     */
    private function formatDatabaseDateTime($dateString)
    {
        if (!$dateString) {
            return null;
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $dateString; // Return original if parsing fails
        }
    }

    public function removeParameterFromServer($serverId, $paramId)
    {
        $sql = "DELETE FROM server_params 
            WHERE server_id = :serverId AND param_id = :paramId";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':serverId' => $serverId,
            ':paramId' => $paramId
        ]);
    }

    public function removeParameterFromLocation($locationId, $paramId)
    {
        $sql = "DELETE FROM location_params 
            WHERE location_id = :locationId AND param_id = :paramId";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':locationId' => $locationId,
            ':paramId' => $paramId
        ]);
    }
    /**
     * Update a parameter by its ID
     * 
     * @param int $paramId The ID of the parameter to update
     * @param array $data Associative array with fields to update (param_name, param_value, description, is_common)
     * @return bool Whether the update was successful
     */
    /**
     * Update a parameter in the 'params' table by its ID using structured input.
     *
     * @param array $data Associative array with keys:
     *                    - param_id (required)
     *                    - param_name
     *                    - param_value
     *                    - description
     *                    - is_common
     * @return array Returns an associative array with status and optional messages.
     */
    public function updateParameterById($data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($data['param_id'])) {
            return ['success' => false, 'message' => 'param_id is required'];
        }

        $paramId = (int) $data['param_id'];
        $currentData = $this->getParameterById($paramId);
        if (!$currentData) {
            return ['success' => false, 'message' => "Parameter with ID $paramId not found"];
        }

        $allowed = ['param_name', 'param_value', 'description', 'is_common'];
        $updates = [];
        $params = [':paramId' => $paramId];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = ($key === 'is_common') ? (int) (bool) $data[$key] : $data[$key];
            }
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'No valid fields provided for update'];
        }

        $sql = "UPDATE params SET " . implode(', ', $updates) . " WHERE param_id = :paramId";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $newData = $this->getParameterById($paramId);
            $this->logChange(
                'params',
                $paramId,
                'UPDATE',
                $currentData,
                $newData,
                $_SESSION['username'] ?? 'system',
                $data['change_reason'] ?? 'Parameter updated'
            );
            return ['success' => true, 'message' => 'Parameter updated successfully'];
        }

        return ['success' => false, 'message' => 'Database update failed'];
    }


    /**
     * Get a parameter by its ID
     * 
     * @param int $paramId The ID of the parameter to retrieve
     * @return array|null The parameter data or null if not found
     */
    public function getParameterById($paramId)
    {
        $sql = "SELECT * FROM params WHERE param_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$paramId]);
        return $stmt->fetch() ?: null;
    }
}