<?php
session_start();
require_once 'NginxReverseProxyDB.php';

class NginxConfigLoader
{
    private $db;

    public function __construct()
    {
        $this->db = new NginxReverseProxyDB();
    }

    public function loadCertificatesFromJson($jsonFile)
    {
        $jsonData = file_get_contents($jsonFile);
        $certificates = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format in certificate file: " . json_last_error_msg());
        }

        $existingCerts = $this->db->getCertificates();

        foreach ($certificates as $cert) {
            $certPath = trim($cert['cert_path']);
            $keyPath = trim($cert['key_path']);

            $duplicate = false;
            foreach ($existingCerts as $existing) {
                if ($existing['cert_path'] === $certPath && $existing['key_path'] === $keyPath) {
                    echo "Skipping duplicate certificate: {$certPath}\n";
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate)
                continue;

            $certData = [
                'cert_name' => str_replace('.crt', '', str_replace('_bundle.crt', '', $cert['cert_name'])),
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'issuer' => trim($cert['issuer']),
                'subject' => trim($cert['subject']),
                'valid_from' => ($from = strtotime(trim($cert['valid_from']))) ? date('Y-m-d H:i:s', $from) : null,
                'valid_to' => ($to = strtotime(trim($cert['valid_to']))) ? date('Y-m-d H:i:s', $to) : null,
                'serial_number' => trim($cert['serial_number']),
                'fingerprint' => trim($cert['fingerprint']),
                'algorithm' => trim($cert['algorithm']),
                'key_size' => (int) preg_replace('/[^0-9]/', '', $cert['key_size']),
                'is_self_signed' => isset($cert['is_self_signed']) ? (int) $cert['is_self_signed'] : 0,
                'notes' => 'Loaded from JSON config'
            ];

            $this->db->addCertificate($certData);
        }
    }
    private function serverExists($serverName, $port)
    {
        $servers = $this->db->getReverseProxyServers();
        foreach ($servers as $server) {
            if ($server['server_name'] === $serverName && $server['port'] == $port) {
                return true;
            }
        }
        return false;
    }

    public function loadServersFromJson($jsonFile)
    {
        if (!file_exists($jsonFile)) {
            throw new Exception("JSON file not found: $jsonFile");
        }

        $jsonData = file_get_contents($jsonFile);
        if ($jsonData === false) {
            throw new Exception("Failed to read JSON file: $jsonFile");
        }
        $jsonData = file_get_contents($jsonFile);
        $config = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format in server config file: " . json_last_error_msg());
        }
        try {
            foreach ($config['servers'] as $server) {
                $serverName = trim($server['server_name']);
                $port = (int) $server['port'];

                if ($this->serverExists($serverName, $port)) {
                    echo "Server already exists: {$serverName}:{$port}\n";
                    continue;
                }

                $sslCertId = null;
                if (!empty($server['ssl_enabled']) && !empty($server['ssl_certificate'])) {
                    $certPath = trim($server['ssl_certificate']);
                    $keyPath = trim($server['ssl_certificate_key']);

                    $certs = $this->db->getCertificates();
                    foreach ($certs as $cert) {
                        if ($cert['cert_path'] === $certPath && $cert['key_path'] === $keyPath) {
                            $sslCertId = $cert['cert_id'];
                            break;
                        }
                    }

                    if (!$sslCertId) {
                        $certData = [
                            'cert_name' => str_replace('_bundle.crt', '', basename($certPath)),
                            'cert_path' => $certPath,
                            'key_path' => $keyPath,
                            'issuer' => 'Unknown (from server config)',
                            'subject' => 'Unknown (from server config)',
                            'valid_from' => null,
                            'valid_to' => null,
                            'serial_number' => '',
                            'fingerprint' => '',
                            'algorithm' => '',
                            'key_size' => 0,
                            'is_self_signed' => 0,
                            'notes' => 'Auto-created from server config'
                        ];
                        $this->db->addCertificate($certData);

                        $certs = $this->db->getCertificates();
                        foreach ($certs as $cert) {
                            if ($cert['cert_path'] === $certPath && $cert['key_path'] === $keyPath) {
                                $sslCertId = $cert['cert_id'];
                                break;
                            }
                        }
                    }
                }

                $serverTitle = substr(trim($serverName), 0, 50);
                $serverId = $this->db->addReverseProxyServer(
                    $serverTitle,
                    $port,
                    $serverName,
                    $sslCertId,
                    [
                        'is_http2' => !empty($server['ssl_enabled']) ? 1 : 0,
                        'is_websocket_enabled' => 0,
                    ]
                );

                foreach ($server['directives'] as $directive) {
                    $directive['param_name'] = trim($directive['param_name']);
                    $directive['param_value'] = trim($directive['param_value']);
                    $this->addParameterToServerOrLocation($directive, $serverId);
                }

                foreach ($server['locations'] as $location) {
                    $location['proxy_pass'] = trim($location['proxy_pass']);
                    $locationId = $this->db->addLocation(
                        $location['path'],
                        $location['proxy_pass']
                    );

                    $this->addLocationToServerUnique($serverId, $locationId, count($server['locations']));

                    foreach ($location['directives'] as $directive) {
                        $directive['param_name'] = trim($directive['param_name']);
                        $directive['param_value'] = trim($directive['param_value']);
                        $this->addParameterToServerOrLocation($directive, $serverId, $locationId);
                    }
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getAllServersForAPI()
    {
        $servers = $this->db->getReverseProxyServers();
        $result = [];

        foreach ($servers as $server) {
            $serverConfig = $this->db->getServerConfig($server['server_id']);

            $directives = [];
            if (!empty($serverConfig['parameters'])) {
                foreach ($serverConfig['parameters'] as $param) {
                    $directives[] = [
                        'param_name' => $param['param_name'],
                        'param_value' => $param['param_value'],
                        'is_common' => $param['is_common']
                    ];
                }
            }

            $locations = [];
            if (!empty($serverConfig['locations'])) {
                foreach ($serverConfig['locations'] as $location) {
                    $locationDirectives = [];
                    if (!empty($location['parameters'])) {
                        foreach ($location['parameters'] as $param) {
                            $locationDirectives[] = [
                                'param_name' => $param['param_name'],
                                'param_value' => $param['param_value'],
                                'is_common' => $param['is_common']
                            ];
                        }
                    }

                    $locations[] = [
                        'location_id' => $location['location_id'],
                        'path' => $location['path_pattern'],
                        'proxy_pass' => $location['proxy_to'],
                        'directives' => $locationDirectives
                    ];
                }
            }

            $sslEnabled = !empty($serverConfig['ssl_cert_id']);
            $sslCertificate = '';
            $sslCertificateKey = '';

            if ($sslEnabled && !empty($serverConfig['certificate'])) {
                $sslCertificate = $serverConfig['certificate']['cert_path'];
                $sslCertificateKey = $serverConfig['certificate']['key_path'];
            }

            $result[] = [
                'server_id' => $server['server_id'],
                'server_name' => $server['server_name'],
                'server_title' => $server['server_title'],
                'port' => (int) $server['port'],
                'ssl_enabled' => $sslEnabled,
                'ssl_certificate' => $sslCertificate,
                'ssl_certificate_key' => $sslCertificateKey,
                'ssl_client_certificate' => '',
                'ssl_verify_client' => 'off',
                'is_mtls' => false,
                'is_http2' => (bool) $server['is_http2'],
                'is_websocket_enabled' => (bool) $server['is_websocket_enabled'],
                'directives' => $directives,
                'locations' => $locations
            ];
        }

        return $result;
    }

    public function addServerFromAPI($serverData)
    {
        try {
            $serverName = $serverData['server_name'];
            $port = (int) $serverData['port'];

            if ($this->serverExists($serverName, $port)) {
                return [
                    'success' => false,
                    'error' => "Server already exists: {$serverName}:{$port}"
                ];
            }

            $sslCertId = null;
            if (!empty($serverData['ssl_enabled']) && !empty($serverData['ssl_certificate'])) {
                $sslCertId = $this->findOrCreateCertificate(
                    $serverData['ssl_certificate'],
                    $serverData['ssl_certificate_key']
                );
            }

            $serverTitle = trim($serverData['server_title']);
            $serverId = $this->db->addReverseProxyServer(
                $serverTitle,
                $port,
                $serverName,
                $sslCertId,
                [
                    'is_http2' => !empty($serverData['ssl_enabled']) ? 1 : 0,
                    'is_websocket_enabled' => !empty($serverData['is_websocket_enabled']) ? 1 : 0,
                ]
            );

            if (!empty($serverData['directives'])) {
                foreach ($serverData['directives'] as $directive) {
                    $this->addParameterToServerOrLocation($directive, $serverId);
                }
            }

            if (!empty($serverData['locations'])) {
                foreach ($serverData['locations'] as $index => $location) {
                    $locationId = $this->db->addLocation(
                        $location['path'],
                        $location['proxy_pass']
                    );

                    $this->addLocationToServerUnique($serverId, $locationId, $index + 1);

                    if (!empty($location['directives'])) {
                        foreach ($location['directives'] as $directive) {
                            $this->addParameterToServerOrLocation($directive, $serverId, $locationId);
                        }
                    }
                }
            }

            return [
                'success' => true,
                'server_id' => $serverId,
                'message' => 'Server created successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateServerFromAPI($serverId, $serverData)
    {
        try {
            // First validate the server exists
            $currentConfig = $this->db->getServerConfig($serverId);
            if (!$currentConfig) {
                return [
                    'success' => false,
                    'error' => "Server not found with ID: $serverId"
                ];
            }

            // Start transaction
            $this->db->getConnection()->beginTransaction();

            // Update basic server properties
            $sslCertId = null;
            if (!empty($serverData['ssl_enabled'])) {
                $sslCertId = $this->findOrCreateCertificate(
                    $serverData['ssl_certificate'],
                    $serverData['ssl_certificate_key']
                );
            }

            $serverUpdate = [
                'server_title' => $serverData['server_title'] ?? $currentConfig['server_title'],
                'port' => $serverData['port'] ?? $currentConfig['port'],
                'server_name' => $serverData['server_name'] ?? $currentConfig['server_name'],
                'ssl_cert_id' => $sslCertId,
                'is_http2' => isset($serverData['is_http2']) ? (int) $serverData['is_http2'] : $currentConfig['is_http2'],
                'is_websocket_enabled' => isset($serverData['is_websocket_enabled']) ? (int) $serverData['is_websocket_enabled'] : $currentConfig['is_websocket_enabled']
            ];

            $this->db->updateServer($serverId, $serverUpdate);

            // Handle server-level directives
            $this->updateServerDirectives(
                $serverId,
                $serverData['directives'] ?? []
            );

            // Handle locations - remove all existing location parameter links and add new ones
            $this->updateServerLocations(
                $serverId,
                $serverData['locations'] ?? []
            );

            // Commit transaction
            $this->db->getConnection()->commit();

            return [
                'success' => true,
                'message' => 'Server updated successfully',
                'server_id' => $serverId
            ];

        } catch (Exception $e) {
            // Rollback on error
            if ($this->db->getConnection()->inTransaction()) {
                $this->db->getConnection()->rollBack();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function updateServerDirectives($serverId, $directives)
    {
        // Get current server parameters
        $currentParams = $this->db->getServerConfig($serverId)['parameters'] ?? [];

        // First remove all existing parameters for this server
        foreach ($currentParams as $param) {
            $this->db->removeParameterFromServer($serverId, $param['param_id']);
        }

        // Add new directives as parameters
        foreach ($directives as $directive) {
            $paramId = $this->findOrCreateParameter($directive['param_name'], $directive['param_value'], $directive['is_common']);
            $this->addParamToServerUnique($serverId, $paramId);
        }
    }

    private function updateServerLocations($serverId, $locations)
    {
        // Get current locations for this server
        $currentLocations = $this->getCurrentServerLocations($serverId);
        $processedLocationIds = [];

        // Process each location in the new data
        foreach ($locations as $locationData) {
            $locationId = $this->processLocation($serverId, $locationData, $currentLocations);
            $processedLocationIds[] = $locationId;

            // Remove all existing parameter links for this location (but keep the parameters themselves)
            $currentParams = $this->db->getLocation($locationId)['parameters'] ?? [];
            foreach ($currentParams as $param) {
                $this->db->removeParameterFromLocation($locationId, $param['param_id']);
            }

            // Add new parameter links for this location
            if (!empty($locationData['directives'])) {
                foreach ($locationData['directives'] as $directive) {
                    $paramId = $this->findOrCreateParameter($directive['param_name'], $directive['param_value'], $directive['is_common'] ?? true);
                    $this->addParamToLocationUnique($locationId, $paramId);
                }
            }
        }

        // Remove locations that are no longer needed
        foreach ($currentLocations as $locationId => $locationData) {
            if (!in_array($locationId, $processedLocationIds)) {
                // Remove this location from the server (but keep the location itself)
                $this->db->removeLocationFromServer($serverId, $locationId);

                // Check if this location is used by other servers
                $isUsedByOtherServers = $this->isLocationUsedByOtherServers($locationId, $serverId);

                // If not used by other servers, delete the location completely
                if (!$isUsedByOtherServers) {
                    $this->db->deleteLocation($locationId);
                }
            }
        }
    }
    

    // Helper method to check if a location is used by other servers
    private function isLocationUsedByOtherServers($locationId, $excludeServerId)
    {
        $sql = "SELECT COUNT(*) as count 
            FROM server_locations 
            WHERE location_id = :locationId AND server_id != :excludeServerId";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            ':locationId' => $locationId,
            ':excludeServerId' => $excludeServerId
        ]);

        $result = $stmt->fetch();
        return $result['count'] > 0;
    }


    private function processLocation($serverId, $locationData, &$currentLocations)
    {
        // Check if this is an existing location for this server
        $existingLocationId = $this->findExistingLocationForServer($serverId, $locationData, $currentLocations);

        if ($existingLocationId) {
            // This location already exists for this server
            unset($currentLocations[$existingLocationId]);
            return $existingLocationId;
        }

        // Check if this location exists for other servers
        $sharedLocationId = $this->findSharedLocation($locationData);

        if ($sharedLocationId) {
            // Location is shared with other servers - create a new copy
            $newLocationId = $this->duplicateLocation($sharedLocationId, $locationData);
            $this->db->addLocationToServer($serverId, $newLocationId);
            return $newLocationId;
        }

        // Create a brand new location
        $locationId = $this->db->addLocation(
            $locationData['path'],
            $locationData['proxy_pass']
        );
        $this->db->addLocationToServer($serverId, $locationId);
        return $locationId;
    }

    private function findExistingLocationForServer($serverId, $locationData, $currentLocations)
    {
        if (isset($locationData['location_id'])) {
            // Check if this location ID exists in our current locations
            foreach ($currentLocations as $locId => $loc) {
                if ($locId == $locationData['location_id']) {
                    // Update the existing location
                    $this->db->updateLocation($locId, [
                        'path_pattern' => $locationData['path'],
                        'proxy_to' => $locationData['proxy_pass']
                    ]);
                    return $locId;
                }
            }
        }

        // Check by path and proxy_pass
        foreach ($currentLocations as $locId => $loc) {
            if (
                $loc['path_pattern'] === $locationData['path'] &&
                $loc['proxy_to'] === $locationData['proxy_pass']
            ) {
                return $locId;
            }
        }

        return null;
    }

    private function findSharedLocation($locationData)
    {
        // Check if this location exists and is used by other servers
        $sql = "SELECT l.location_id 
            FROM locations l
            JOIN server_locations sl ON l.location_id = sl.location_id
            WHERE l.path_pattern = :path AND l.proxy_to = :proxy_pass
            GROUP BY l.location_id
            HAVING COUNT(sl.server_id) > 0";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            ':path' => $locationData['path'],
            ':proxy_pass' => $locationData['proxy_pass']
        ]);

        $result = $stmt->fetch();
        return $result ? $result['location_id'] : null;
    }

    private function duplicateLocation($originalLocationId, $newLocationData)
    {
        // Create new location with updated data
        $newLocationId = $this->db->addLocation(
            $newLocationData['path'],
            $newLocationData['proxy_pass']
        );

        // Copy all parameters from original location
        $originalParams = $this->db->getLocation($originalLocationId)['parameters'] ?? [];
        foreach ($originalParams as $param) {
            $this->db->addParameterToLocation($newLocationId, $param['param_id']);
        }

        return $newLocationId;
    }

    private function updateLocationDirectives($locationId, $directives)
    {
        // Get current parameters for this location
        $currentParams = $this->db->getLocation($locationId)['parameters'] ?? [];

        // First remove all existing parameters for this location
        foreach ($currentParams as $param) {
            $this->db->removeParameterFromLocation($locationId, $param['param_id']);
        }

        // Add new directives as parameters
        foreach ($directives as $directive) {
            $paramId = $this->findOrCreateParameter($directive['param_name'], $directive['param_value'], $directive['is_common'] ?? true);
            $this->addParamToLocationUnique($locationId, $paramId);
        }
    }

    private function getCurrentServerLocations($serverId)
    {
        $sql = "SELECT l.location_id, l.path_pattern, l.proxy_to
            FROM server_locations sl
            JOIN locations l ON sl.location_id = l.location_id
            WHERE sl.server_id = ?";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$serverId]);

        $locations = [];
        while ($row = $stmt->fetch()) {
            $locations[$row['location_id']] = $row;
        }

        return $locations;
    }

    private function cleanupUnusedLocations($serverId, $remainingLocations)
    {
        foreach ($remainingLocations as $locId => $loc) {
            // Check if this location is used by other servers
            $sql = "SELECT COUNT(*) as server_count 
                FROM server_locations 
                WHERE location_id = ? AND server_id != ?";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$locId, $serverId]);
            $result = $stmt->fetch();

            if ($result['server_count'] > 0) {
                // Location is used by other servers - just remove from this server
                $this->db->removeLocationFromServer($serverId, $locId);
            } else {
                // Location is only used by this server - delete completely
                $this->db->removeLocationFromServer($serverId, $locId);
                $this->db->deleteLocation($locId);
            }
        }
    }

    private function findOrCreateParameter($name, $value, $is_common)
    {
        $params = $this->db->getParameters();
        foreach ($params as $param) {
            if ($param['param_name'] === $name && $param['param_value'] === $value) {
                return $param['param_id'];
            }
        }

        // Parameter doesn't exist, create it
        $this->db->addParameter(
            $name,
            $value,
            'Auto-created from server config update',
            $is_common
        );

        // Get the new parameter ID
        $params = $this->db->getParameters();
        foreach ($params as $param) {
            if ($param['param_name'] === $name && $param['param_value'] === $value) {
                return $param['param_id'];
            }
        }

        throw new Exception("Failed to create parameter: $name $value");
    }


    public function deleteServerFromAPI($serverId)
    {
        try {
            $result = $this->db->deleteServer($serverId);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Server deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to delete server'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getAllCertificatesForAPI()
    {
        $certificates = $this->db->getCertificates();
        $result = [];

        foreach ($certificates as $cert) {
            $result[] = [
                'cert_id' => $cert['cert_id'],
                'cert_name' => $cert['cert_name'],
                'cert_path' => $cert['cert_path'],
                'key_path' => $cert['key_path'],
                'issuer' => $cert['issuer'],
                'subject' => $cert['subject'],
                'valid_from' => $cert['valid_from'],
                'valid_to' => $cert['valid_to'],
                'serial_number' => $cert['serial_number'],
                'fingerprint' => $cert['fingerprint'],
                'algorithm' => $cert['algorithm'],
                'key_size' => $cert['key_size'],
                'is_self_signed' => (bool) $cert['is_self_signed'],
                'notes' => $cert['notes']
            ];
        }

        return $result;
    }

    public function addCertificateFromAPI($certData)
    {
        try {
            $certId = $this->db->addCertificate($certData);

            return [
                'success' => true,
                'cert_id' => $certId,
                'message' => 'Certificate added successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateCertificateFromAPI($certId, $certData)
    {
        try {
            $result = $this->db->updateCertificate($certId, $certData);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Certificate updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to update certificate'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function deleteCertificateFromAPI($certId)
    {
        try {
            $result = $this->db->deleteCertificate($certId);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Certificate deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to delete certificate'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getLocationParametersForAPI()
    {
        $sql = "SELECT DISTINCT p.param_id, p.param_name, p.param_value, p.description, p.is_common
            FROM params p
            INNER JOIN location_params lp ON p.param_id = lp.param_id 
            ORDER BY p.param_name, p.param_value";

        $stmt = $this->db->getConnection()->query($sql);
        $results = $stmt->fetchAll();

        $parameters = [];
        foreach ($results as $param) {
            $parameters[] = [
                'param_id' => $param['param_id'],
                'param_name' => $param['param_name'],
                'param_value' => $param['param_value'],
                'description' => $param['description'],
                'is_common' => (bool) $param['is_common']
            ];
        }

        return $parameters;
    }


    public function getAllParametersForAPI()
    {
        $parameters = $this->db->getParameters();
        $result = [];

        foreach ($parameters as $param) {
            $result[] = [
                'param_id' => $param['param_id'],
                'param_name' => $param['param_name'],
                'param_value' => $param['param_value'],
                'description' => $param['description'],
                'is_common' => (bool) $param['is_common']
            ];
        }

        return $result;
    }

    public function addParameterFromAPI($paramData)
    {
        try {
            $result = $this->db->addParameter(
                $paramData['param_name'],
                $paramData['param_value'],
                $paramData['description'] ?? '',
                $paramData['is_commo']
            );

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Parameter added successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to add parameter'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function findOrCreateCertificate($certPath, $keyPath)
    {
        $certs = $this->db->getCertificates();
        foreach ($certs as $cert) {
            if ($cert['cert_path'] === $certPath && $cert['key_path'] === $keyPath) {
                return $cert['cert_id'];
            }
        }

        $certData = [
            'cert_name' => basename($certPath),
            'cert_path' => $certPath,
            'key_path' => $keyPath,
            'issuer' => 'Unknown (auto-created)',
            'subject' => 'Unknown (auto-created)',
            'valid_from' => null,
            'valid_to' => null,
            'serial_number' => '',
            'fingerprint' => '',
            'algorithm' => '',
            'key_size' => 0,
            'is_self_signed' => 0,
            'notes' => 'Auto-created from API request'
        ];

        return $this->db->addCertificate($certData);
    }

    private function addLocationToServerUnique($serverId, $locationId, $order)
    {
        $existing = $this->db->getConnection()->prepare(
            "SELECT 1 FROM server_locations WHERE server_id = ? AND location_id = ?"
        );
        $existing->execute([$serverId, $locationId]);
        if (!$existing->fetch()) {
            $this->db->addLocationToServer($serverId, $locationId);
        }
    }

    private function addParameterToServerOrLocation($directive, $serverId, $locationId = null)
    {
        $params = $this->db->getParameters();
        $paramId = null;

        foreach ($params as $param) {
            if ($param['param_name'] === $directive['param_name'] && $param['param_value'] === $directive['param_value']) {
                $paramId = $param['param_id'];
                break;
            }
        }

        if (!$paramId) {
            $this->db->addParameter(
                $directive['param_name'],
                $directive['param_value'],
                'Auto-created from server config',
                $directive['is_common'] ?? true
            );

            $params = $this->db->getParameters();
            foreach ($params as $param) {
                if ($param['param_name'] === $directive['param_name'] && $param['param_value'] === $directive['param_value']) {
                    $paramId = $param['param_id'];
                    break;
                }
            }
        }

        if ($locationId) {
            $this->addParamToLocationUnique($locationId, $paramId);
        } else {
            $this->addParamToServerUnique($serverId, $paramId);
        }
    }

    private function addParamToServerUnique($serverId, $paramId)
    {
        $existing = $this->db->getConnection()->prepare(
            "SELECT 1 FROM server_params WHERE server_id = ? AND param_id = ?"
        );
        $existing->execute([$serverId, $paramId]);
        if (!$existing->fetch()) {
            $this->db->addParameterToServer($serverId, $paramId);
        }
    }

    private function addParamToLocationUnique($locationId, $paramId)
    {
        $existing = $this->db->getConnection()->prepare(
            "SELECT 1 FROM location_params WHERE location_id = ? AND param_id = ?"
        );
        $existing->execute([$locationId, $paramId]);
        if (!$existing->fetch()) {
            $this->db->addParameterToLocation($locationId, $paramId);
        }
    }

    public function searchHistory($searchQuery, $offset = 0, $limit = 10)
    {
        try {
            $results = $this->db->searchHistory(
                trim($searchQuery),
                (int) $offset,
                (int) $limit
            );

            return [
                'success' => true,
                'data' => $results
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk update multiple parameters using their full data (including param_id).
     *
     * @param array $payload Expected format: ['data' => [ ... ]]
     * @return array Result summary for each parameter
     */
    public function updateParametersBulk(array $payload)
    {
        try {
            if (empty($payload['data']) || !is_array($payload['data'])) {
                throw new Exception("Invalid input format. Expecting 'data' as array.");
            }

            $parameters = $payload['data'];
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($parameters as $param) {
                try {
                    // Validate required fields
                    if (
                        !isset($param['param_id']) ||
                        !isset($param['param_name']) ||
                        !isset($param['param_value'])
                    ) {
                        throw new Exception("Missing required fields (param_id, param_name, param_value)");
                    }

                    // Ensure valid structure for updateParameterById
                    $updateData = [
                        'param_id' => (int) $param['param_id'],
                        'param_name' => $param['param_name'],
                        'param_value' => $param['param_value'],
                        'description' => $param['description'] ?? 'updated parameters',
                        'is_common' => isset($param['is_common']) ? (bool) $param['is_common'] : false,
                    ];

                    $result = $this->db->updateParameterById($updateData);

                    if ($result['success']) {
                        $successCount++;
                        $results[] = [
                            'param_id' => $param['param_id'],
                            'status' => 'success',
                            'message' => $result['message'] ?? 'Updated successfully'
                        ];
                    } else {
                        $errorCount++;
                        $results[] = [
                            'param_id' => $param['param_id'],
                            'status' => 'error',
                            'message' => $result['message'] ?? 'Update failed'
                        ];
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $results[] = [
                        'param_id' => $param['param_id'] ?? null,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total' => count($parameters)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

}

//  try {
//     $loader = new NginxConfigLoader();
//     $db = new NginxReverseProxyDB();

//     $loader->loadCertificatesFromJson('certificates.json');
//     echo "Certificates loaded successfully.\n";

//     $loader->loadServersFromJson('servers.json');
//     echo "Server configurations loaded successfully.\n";

// } catch (Exception $e) {
//     echo "? Error: " . $e->getMessage() . "\n";
// }