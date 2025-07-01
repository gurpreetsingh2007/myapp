CREATE DATABASE IF NOT EXISTS nginx_reverse_proxy;
USE nginx_reverse_proxy;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS server_locations;
DROP TABLE IF EXISTS location_params;
DROP TABLE IF EXISTS server_params;
DROP TABLE IF EXISTS reverse_proxy;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS params;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS configuration_history;

-- Table for SSL certificates
CREATE TABLE certificates (
    cert_id INT AUTO_INCREMENT PRIMARY KEY,
    cert_name VARCHAR(255) NOT NULL,
    cert_path VARCHAR(512) NOT NULL COMMENT 'File system path to .crt file',
    key_path VARCHAR(512) NOT NULL COMMENT 'File system path to .key file',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    issuer VARCHAR(255),
    subject VARCHAR(255),
    valid_from DATETIME,
    valid_to DATETIME,
    serial_number VARCHAR(255),
    fingerprint VARCHAR(255),
    algorithm VARCHAR(100),
    key_size INT,
    is_self_signed BOOLEAN DEFAULT FALSE,
    notes TEXT,
    UNIQUE KEY (cert_path, key_path)
);

-- Table for parameters that can be used in reverse proxy blocks
CREATE TABLE params (
    param_id INT AUTO_INCREMENT PRIMARY KEY,
    param_name VARCHAR(255) NOT NULL,
    param_value TEXT NOT NULL,
    description TEXT,
    is_common BOOLEAN DEFAULT TRUE COMMENT 'Mark if this is a commonly used parameter',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (param_name, param_value(255))
);

-- Table for location blocks
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    path_pattern VARCHAR(255) NOT NULL COMMENT 'e.g., /api/, /static/, etc.',
    proxy_to VARCHAR(512) NOT NULL COMMENT 'The URL to proxy to',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Junction table for location-parameter relationships
CREATE TABLE location_params (
    location_id INT NOT NULL,
    param_id INT NOT NULL,
    PRIMARY KEY (location_id, param_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE,
    FOREIGN KEY (param_id) REFERENCES params(param_id) ON DELETE CASCADE
);

-- Table for server blocks
CREATE TABLE reverse_proxy (
    server_id INT AUTO_INCREMENT PRIMARY KEY,
    server_title VARCHAR(255) NOT NULL NOT NULL,
    port INT NOT NULL COMMENT 'Listening port (e.g., 80, 443)',
    server_name TEXT NOT NULL COMMENT 'Domain name(s)',
    ssl_cert_id INT NULL COMMENT 'Reference to SSL certificate',
    is_http2 BOOLEAN DEFAULT FALSE,
    is_websocket_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ssl_cert_id) REFERENCES certificates(cert_id) ON DELETE SET NULL
);

-- Junction table for server-location relationships
CREATE TABLE server_locations (
    server_id INT NOT NULL,
    location_id INT NOT NULL,
    PRIMARY KEY (server_id, location_id),
    FOREIGN KEY (server_id) REFERENCES reverse_proxy(server_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE
);

-- Table for additional server block parameters
CREATE TABLE server_params (
    server_id INT NOT NULL,
    param_id INT NOT NULL,
    PRIMARY KEY (server_id, param_id),
    FOREIGN KEY (server_id) REFERENCES reverse_proxy(server_id) ON DELETE CASCADE,
    FOREIGN KEY (param_id) REFERENCES params(param_id) ON DELETE CASCADE
);

CREATE TABLE `configuration_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL COMMENT 'Name of the table that was modified',
  `record_id` int(11) NOT NULL COMMENT 'ID of the modified record',
  `action_type` enum('INSERT','UPDATE','DELETE') NOT NULL COMMENT 'Type of action performed',
  `old_data` json DEFAULT NULL COMMENT 'Previous data before change (for UPDATE/DELETE)',
  `new_data` json DEFAULT NULL COMMENT 'New data after change (for INSERT/UPDATE)',
  `changed_by` varchar(100) DEFAULT NULL COMMENT 'User who made the change',
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the change occurred',
  `change_reason` varchar(255) DEFAULT NULL COMMENT 'Optional reason for the change',
  PRIMARY KEY (`history_id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks all changes to configuration tables';