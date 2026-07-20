SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_role_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id INT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(75) NOT NULL,
    last_name VARCHAR(75) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_username (username),
    UNIQUE KEY unique_email (email),
    KEY role_id_index (role_id),
    CONSTRAINT users_role_fk
        FOREIGN KEY (role_id)
        REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_number VARCHAR(30) NOT NULL,
    requester_id INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(75) NOT NULL,
    priority ENUM(
        'Low',
        'Medium',
        'High',
        'Critical'
    ) NOT NULL DEFAULT 'Medium',
    status ENUM(
        'New',
        'Assigned',
        'In Progress',
        'Waiting',
        'Resolved',
        'Closed'
    ) NOT NULL DEFAULT 'New',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    closed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_ticket_number (ticket_number),
    KEY requester_index (requester_id),
    KEY assigned_index (assigned_to),
    KEY status_index (status),
    KEY priority_index (priority),
    CONSTRAINT tickets_requester_fk
        FOREIGN KEY (requester_id)
        REFERENCES users (id),
    CONSTRAINT tickets_assigned_fk
        FOREIGN KEY (assigned_to)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ticket_notes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    is_internal TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ticket_index (ticket_id),
    KEY note_user_index (user_id),
    CONSTRAINT ticket_notes_ticket_fk
        FOREIGN KEY (ticket_id)
        REFERENCES tickets (id)
        ON DELETE CASCADE,
    CONSTRAINT ticket_notes_user_fk
        FOREIGN KEY (user_id)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_tag VARCHAR(50) NOT NULL,
    asset_type VARCHAR(75) NOT NULL,
    hostname VARCHAR(100) DEFAULT NULL,
    manufacturer VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    operating_system VARCHAR(150) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    assigned_user_id INT UNSIGNED DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    status ENUM(
        'Active',
        'In Storage',
        'Repair',
        'Retired'
    ) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_asset_tag (asset_tag),
    KEY assigned_user_index (assigned_user_id),
    CONSTRAINT assets_user_fk
        FOREIGN KEY (assigned_user_id)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY audit_user_index (user_id),
    KEY audit_action_index (action),
    CONSTRAINT audit_user_fk
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO roles (name, description)
VALUES
    ('Administrator', 'Full system access'),
    ('Help Desk Technician', 'Manage and work support tickets'),
    ('System Administrator', 'Manage infrastructure and advanced tickets'),
    ('Viewer', 'Read-only dashboard access');

SET FOREIGN_KEY_CHECKS = 1;
