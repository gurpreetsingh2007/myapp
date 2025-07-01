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
                'cert_name' => $cert['cert_name'],
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
        $jsonData = file_get_contents($jsonFile);
        $config = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format in server config file: " . json_last_error_msg());
        }

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
                        'cert_name' => basename($certPath),
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
                $directive['name'] = trim($directive['name']);
                $directive['value'] = trim($directive['value']);
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
                    $directive['name'] = trim($directive['name']);
                    $directive['value'] = trim($directive['value']);
                    $this->addParameterToServerOrLocation($directive, $serverId, $locationId);
                }
            }
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
                        'name' => $param['param_name'],
                        'value' => $param['param_value']
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
                                'name' => $param['param_name'],
                                'value' => $param['param_value']
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

            $serverTitle = substr(trim($serverName), 0, 50);
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
            $sslCertId = null;
            if (!empty($serverData['ssl_enabled']) && !empty($serverData['ssl_certificate'])) {
                $sslCertId = $this->findOrCreateCertificate(
                    $serverData['ssl_certificate'],
                    $serverData['ssl_certificate_key']
                );
            }

            $updateData = [];
            if (isset($serverData['server_title'])) {
                $updateData['server_title'] = $serverData['server_title'];
            }
            if (isset($serverData['server_name'])) {
                $updateData['server_name'] = $serverData['server_name'];
            }
            if (isset($serverData['port'])) {
                $updateData['port'] = (int) $serverData['port'];
            }
            if (isset($sslCertId)) {
                $updateData['ssl_cert_id'] = $sslCertId;
            }
            $updateData['is_http2'] = !empty($serverData['ssl_enabled']) ? 1 : 0;
            $updateData['is_websocket_enabled'] = !empty($serverData['is_websocket_enabled']) ? 1 : 0;

            $this->db->updateServer($serverId, $updateData);

            return [
                'success' => true,
                'message' => 'Server updated successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
        $sql = "SELECT DISTINCT p.param_name, p.param_value, p.description
                FROM params p
                INNER JOIN location_params lp ON p.param_id = lp.param_id
                ORDER BY p.param_name, p.param_value";

        $stmt = $this->db->getConnection()->query($sql);
        $results = $stmt->fetchAll();

        $parameters = [];
        foreach ($results as $param) {
            $parameters[] = [
                'name' => $param['param_name'],
                'value' => $param['param_value'],
                'description' => $param['description']
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
                $paramData['is_common'] ?? true
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
            if ($param['param_name'] === $directive['name'] && $param['param_value'] === $directive['value']) {
                $paramId = $param['param_id'];
                break;
            }
        }

        if (!$paramId) {
            $this->db->addParameter(
                $directive['name'],
                $directive['value'],
                'Auto-created from server config'
            );

            $params = $this->db->getParameters();
            foreach ($params as $param) {
                if ($param['param_name'] === $directive['name'] && $param['param_value'] === $directive['value']) {
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
}
// Example usage
// try {
//     $loader = new NginxConfigLoader();

//     $loader->loadCertificatesFromJson('certificates.json');
//     echo "Certificates loaded successfully.\n";

//     $loader->loadServersFromJson('servers.json');
//     echo "Server configurations loaded successfully.\n";

// } catch (Exception $e) {
//     echo "? Error: " . $e->getMessage() . "\n";
// }