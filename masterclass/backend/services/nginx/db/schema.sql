    -- Drop tables in reverse order of dependencies
    DROP TABLE IF EXISTS `nginx_location_optimizations`;
    DROP TABLE IF EXISTS `nginx_static_file_mappings`;
    DROP TABLE IF EXISTS `nginx_proxy_targets`;
    DROP TABLE IF EXISTS `nginx_upstream_servers`;
    DROP TABLE IF EXISTS `nginx_upstreams`;
    DROP TABLE IF EXISTS `nginx_ssl_certificates`;
    DROP TABLE IF EXISTS `nginx_location_directives`;
    DROP TABLE IF EXISTS `nginx_server_directives`;
    DROP TABLE IF EXISTS `nginx_http_directives`;
    DROP TABLE IF EXISTS `nginx_main_directives`;
    DROP TABLE IF EXISTS `nginx_locations`;
    DROP TABLE IF EXISTS `nginx_servers`;
    DROP TABLE IF EXISTS `nginx_http_blocks`;
    DROP TABLE IF EXISTS `nginx_config_files`;
    DROP TABLE IF EXISTS `ssl_certificate_domains`;
    DROP TABLE IF EXISTS `certificate_validations`;
    DROP TABLE IF EXISTS `ssl_certificates`;
    DROP TABLE IF EXISTS `nginx_scans`;

    -- Main scan tracking table
    CREATE TABLE `nginx_scans` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `scan_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `scan_path` VARCHAR(500) NOT NULL,
        `total_files` INT DEFAULT 0,
        `total_servers` INT DEFAULT 0,
        `total_certificates` INT DEFAULT 0,
        `scan_status` ENUM('running', 'completed', 'failed') DEFAULT 'running',
        `notes` TEXT,
        INDEX `idx_scan_date` (`scan_date`),
        INDEX `idx_scan_status` (`scan_status`)
    );

    -- SSL Certificates
    CREATE TABLE `ssl_certificates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `certificate_name` VARCHAR(255) NOT NULL,
        `certificate_path` VARCHAR(500) NOT NULL,
        `private_key_path` VARCHAR(500),
        `certificate_content` LONGTEXT,
        `private_key_content` LONGTEXT,
        `certificate_type` ENUM('self-signed', 'ca-signed', 'letsencrypt', 'wildcard', 'unknown') DEFAULT 'unknown',
        `issuer` VARCHAR(255),
        `subject` VARCHAR(255),
        `serial_number` VARCHAR(100),
        `fingerprint_sha1` VARCHAR(40),
        `fingerprint_sha256` VARCHAR(64),
        `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `scan_id` INT,
        UNIQUE KEY `uk_cert_path` (`certificate_path`),
        INDEX `idx_cert_name` (`certificate_name`),
        INDEX `idx_cert_type` (`certificate_type`),
        FOREIGN KEY (`scan_id`) REFERENCES `nginx_scans`(`id`) ON DELETE SET NULL
    );

    CREATE TABLE `certificate_validations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `certificate_id` INT NOT NULL,
        `is_valid` BOOLEAN DEFAULT FALSE,
        `validation_status` ENUM('valid', 'expired', 'invalid', 'not_yet_valid', 'revoked', 'unknown') DEFAULT 'unknown',
        `not_before` DATETIME,
        `not_after` DATETIME,
        `days_until_expiry` INT,
        `validation_errors` TEXT,
        `last_checked` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `auto_renewal_enabled` BOOLEAN DEFAULT FALSE,
        `renewal_command` VARCHAR(500),
        INDEX `idx_cert_validation` (`certificate_id`, `validation_status`),
        INDEX `idx_expiry_date` (`not_after`),
        INDEX `idx_days_until_expiry` (`days_until_expiry`),
        FOREIGN KEY (`certificate_id`) REFERENCES `ssl_certificates`(`id`) ON DELETE CASCADE
    );

    CREATE TABLE `ssl_certificate_domains` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `certificate_id` INT NOT NULL,
        `domain_name` VARCHAR(255) NOT NULL,
        `domain_type` ENUM('common_name', 'san', 'wildcard') DEFAULT 'san',
        `is_primary` BOOLEAN DEFAULT FALSE,
        INDEX `idx_cert_domains` (`certificate_id`),
        INDEX `idx_domain_name` (`domain_name`),
        FOREIGN KEY (`certificate_id`) REFERENCES `ssl_certificates`(`id`) ON DELETE CASCADE
    );

    -- NGINX configuration files
    CREATE TABLE `nginx_config_files` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `file_path` VARCHAR(500) NOT NULL,
        `original_path` VARCHAR(500),
        `filename` VARCHAR(255) NOT NULL,
        `file_size` BIGINT,
        `file_hash` VARCHAR(64),
        `file_content` LONGTEXT,
        `file_type` ENUM('main', 'http', 'server', 'location', 'upstream', 'include', 'snippet') DEFAULT 'server',
        `is_enabled` BOOLEAN DEFAULT TRUE,
        `is_symlink` BOOLEAN DEFAULT FALSE,
        `syntax_valid` BOOLEAN DEFAULT NULL,
        `syntax_errors` TEXT,
        `last_modified` TIMESTAMP,
        `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `scan_id` INT,
        `included_from` INT NULL,
        UNIQUE KEY `uk_file_path` (`file_path`),
        INDEX `idx_filename` (`filename`),
        INDEX `idx_file_type` (`file_type`),
        INDEX `idx_enabled` (`is_enabled`),
        INDEX `idx_included_from` (`included_from`),
        FOREIGN KEY (`scan_id`) REFERENCES `nginx_scans`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`included_from`) REFERENCES `nginx_config_files`(`id`) ON DELETE SET NULL
    );

    CREATE TABLE `nginx_http_blocks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `config_file_id` INT NOT NULL,
        `block_order` INT DEFAULT 1,
        `is_active` BOOLEAN DEFAULT TRUE,
        `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_config_file` (`config_file_id`),
        FOREIGN KEY (`config_file_id`) REFERENCES `nginx_config_files`(`id`) ON DELETE CASCADE
    );

    CREATE TABLE `nginx_servers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `config_file_id` INT NOT NULL,
    `http_block_id` INT,
    `server_name` TEXT,
    `listen_ports` VARCHAR(255),
    `server_order` INT DEFAULT 1,
    `is_default_server` BOOLEAN DEFAULT FALSE,
    `is_ssl_enabled` BOOLEAN DEFAULT FALSE,
    `root_directory` VARCHAR(500),
    `index_files` VARCHAR(255),
    `access_log` VARCHAR(500),
    `error_log` VARCHAR(500),
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_server_name` (`server_name`(191)),
    INDEX `idx_config_file` (`config_file_id`),
    INDEX `idx_ssl_enabled` (`is_ssl_enabled`),
    INDEX `idx_default_server` (`is_default_server`),
    FOREIGN KEY (`config_file_id`) REFERENCES `nginx_config_files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`http_block_id`) REFERENCES `nginx_http_blocks`(`id`) ON DELETE SET NULL
);

    CREATE TABLE `nginx_locations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `server_id` INT NOT NULL,
        `location_path` VARCHAR(500) NOT NULL,
        `location_modifier` ENUM('none', 'exact', 'prefix', 'regex', 'regex_case_insensitive') DEFAULT 'none',
        `location_order` INT DEFAULT 1,
        `proxy_pass` VARCHAR(500),
        `try_files` VARCHAR(255),
        `return_code` INT,
        `return_url` VARCHAR(500),
        `is_active` BOOLEAN DEFAULT TRUE,
        `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_server_location` (`server_id`, `location_path`),
        INDEX `idx_location_path` (`location_path`),
        FOREIGN KEY (`server_id`) REFERENCES `nginx_servers`(`id`) ON DELETE CASCADE
    );

    -- Reverse Proxy Targets
    CREATE TABLE `nginx_proxy_targets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `location_id` INT NOT NULL,
        `proxy_pass_url` VARCHAR(500) NOT NULL,
        `protocol` ENUM('http', 'https') DEFAULT 'http',
        `host` VARCHAR(255),
        `port` INT,
        `is_upstream` BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (`location_id`) REFERENCES `nginx_locations`(`id`) ON DELETE CASCADE
    );

    -- Upstream definitions
    CREATE TABLE `nginx_upstreams` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `config_file_id` INT,
        `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_upstream_name` (`name`),
        FOREIGN KEY (`config_file_id`) REFERENCES `nginx_config_files`(`id`) ON DELETE SET NULL
    );

    CREATE TABLE `nginx_upstream_servers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `upstream_id` INT NOT NULL,
        `server_address` VARCHAR(255) NOT NULL,
        `weight` INT DEFAULT 1,
        `max_fails` INT DEFAULT 1,
        `fail_timeout` VARCHAR(50),
        `backup` BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (`upstream_id`) REFERENCES `nginx_upstreams`(`id`) ON DELETE CASCADE
    );

    -- Static file roots & aliases
    CREATE TABLE `nginx_static_file_mappings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `location_id` INT NOT NULL,
        `root_path` VARCHAR(500),
        `alias_path` VARCHAR(500),
        `file_extensions` VARCHAR(255),
        FOREIGN KEY (`location_id`) REFERENCES `nginx_locations`(`id`) ON DELETE CASCADE
    );

    -- Optimizations
    CREATE TABLE `nginx_location_optimizations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `location_id` INT NOT NULL,
        `gzip_enabled` BOOLEAN,
        `expires` VARCHAR(100),
        `cache_control_header` VARCHAR(255),
        `add_headers` TEXT,
        FOREIGN KEY (`location_id`) REFERENCES `nginx_locations`(`id`) ON DELETE CASCADE
    );

    -- SSL usage in NGINX
    CREATE TABLE `nginx_ssl_certificates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `server_id` INT NOT NULL,
        `certificate_id` INT NOT NULL,
        `ssl_certificate_directive` VARCHAR(500),
        `ssl_certificate_key_directive` VARCHAR(500),
        `ssl_protocols` VARCHAR(100) DEFAULT 'TLSv1.2 TLSv1.3',
        `ssl_ciphers` TEXT,
        `ssl_prefer_server_ciphers` BOOLEAN DEFAULT TRUE,
        `ssl_session_cache` VARCHAR(100),
        `ssl_session_timeout` VARCHAR(50),
        `hsts_enabled` BOOLEAN DEFAULT FALSE,
        `hsts_max_age` INT DEFAULT 31536000,
        `ocsp_stapling` BOOLEAN DEFAULT FALSE,
        `ssl_verify_client` ENUM('on', 'off', 'optional') DEFAULT 'off',
        `ssl_trusted_certificate` VARCHAR(500),
        `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_server_cert` (`server_id`, `certificate_id`),
        FOREIGN KEY (`server_id`) REFERENCES `nginx_servers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`certificate_id`) REFERENCES `ssl_certificates`(`id`) ON DELETE CASCADE
    );

    -- Directive tables
    CREATE TABLE `nginx_main_directives` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `config_file_id` INT NOT NULL,
        `directive_name` VARCHAR(100) NOT NULL,
        `directive_value` TEXT,
        `directive_order` INT DEFAULT 1,
        `context` ENUM('main', 'events') DEFAULT 'main',
        FOREIGN KEY (`config_file_id`) REFERENCES `nginx_config_files`(`id`) ON DELETE CASCADE
    );

    CREATE TABLE `nginx_http_directives` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `http_block_id` INT NOT NULL,
        `directive_name` VARCHAR(100) NOT NULL,
        `directive_value` TEXT,
        `directive_order` INT DEFAULT 1,
        FOREIGN KEY (`http_block_id`) REFERENCES `nginx_http_blocks`(`id`) ON DELETE CASCADE
    );

    CREATE TABLE `nginx_server_directives` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `server_id` INT NOT NULL,
        `directive_name` VARCHAR(100) NOT NULL,
        `directive_value` TEXT,
        `directive_order` INT DEFAULT 1,
        FOREIGN KEY (`server_id`) REFERENCES `nginx_servers`(`id`) ON DELETE CASCADE
    );

    CREATE TABLE `nginx_location_directives` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `location_id` INT NOT NULL,
        `directive_name` VARCHAR(100) NOT NULL,
        `directive_value` TEXT,
        `directive_order` INT DEFAULT 1,
        FOREIGN KEY (`location_id`) REFERENCES `nginx_locations`(`id`) ON DELETE CASCADE
    );