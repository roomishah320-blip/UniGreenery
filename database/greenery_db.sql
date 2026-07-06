-- ============================================================
-- State Level Greenery Management System
-- Database Schema v1.0
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS greenery_management;
-- USE greenery_management;

-- ============================================================
-- ROLES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(500) DEFAULT NULL,
    role_id INT NOT NULL,
    department VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- LAWN CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS lawn_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color_code VARCHAR(20) DEFAULT '#28a745',
    icon VARCHAR(50) DEFAULT 'fa-leaf',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- LAWNS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS lawns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    category_id INT,
    description TEXT,
    area_sqm DECIMAL(10,2) DEFAULT 0,
    location VARCHAR(255),
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    soil_condition VARCHAR(100),
    grass_type VARCHAR(100),
    image VARCHAR(500) DEFAULT NULL,
    gallery JSON,
    total_trees INT DEFAULT 0,
    total_plants INT DEFAULT 0,
    total_flowers INT DEFAULT 0,
    irrigation_type VARCHAR(100),
    sustainability_score DECIMAL(5,2) DEFAULT 0,
    cleanliness_score DECIMAL(5,2) DEFAULT 0,
    maintenance_score DECIMAL(5,2) DEFAULT 0,
    grass_quality_score DECIMAL(5,2) DEFAULT 0,
    overall_rank INT DEFAULT 0,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES lawn_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PLANTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS plants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    scientific_name VARCHAR(255),
    category ENUM('plant', 'tree', 'flower', 'shrub', 'herb') DEFAULT 'plant',
    lawn_id INT,
    description TEXT,
    age_years DECIMAL(5,2) DEFAULT 0,
    health_status ENUM('excellent', 'good', 'fair', 'poor', 'dead') DEFAULT 'good',
    height_cm DECIMAL(8,2) DEFAULT 0,
    image VARCHAR(500) DEFAULT NULL,
    planted_date DATE,
    last_inspection DATE,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lawn (lawn_id),
    INDEX idx_category (category),
    INDEX idx_health (health_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- IRRIGATION SYSTEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS irrigation_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('sprinkler', 'drip', 'surface', 'subsurface', 'manual', 'smart') DEFAULT 'sprinkler',
    description TEXT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- IRRIGATION ZONES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS irrigation_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(255) NOT NULL,
    system_id INT,
    lawn_id INT,
    water_usage_liters DECIMAL(12,2) DEFAULT 0,
    schedule_time TIME,
    schedule_days VARCHAR(100),
    duration_minutes INT DEFAULT 30,
    is_automated TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_run DATETIME,
    next_run DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (system_id) REFERENCES irrigation_systems(id) ON DELETE SET NULL,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE SET NULL,
    INDEX idx_lawn (lawn_id),
    INDEX idx_system (system_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- STAFF TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    employee_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    designation VARCHAR(150),
    department VARCHAR(150),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    image VARCHAR(500) DEFAULT NULL,
    join_date DATE,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    performance_score DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- LAWN STAFF ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS lawn_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawn_id INT NOT NULL,
    staff_id INT NOT NULL,
    role VARCHAR(100) DEFAULT 'gardener',
    assigned_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (lawn_id, staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MAPS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS maps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(20),
    file_size INT DEFAULT 0,
    map_type ENUM('campus', 'zone', 'irrigation', 'plantation', 'other') DEFAULT 'campus',
    is_primary TINYINT(1) DEFAULT 0,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- RANKINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawn_id INT NOT NULL,
    period VARCHAR(50),
    total_trees_score DECIMAL(5,2) DEFAULT 0,
    plantation_quality_score DECIMAL(5,2) DEFAULT 0,
    irrigation_quality_score DECIMAL(5,2) DEFAULT 0,
    cleanliness_score DECIMAL(5,2) DEFAULT 0,
    maintenance_score DECIMAL(5,2) DEFAULT 0,
    grass_quality_score DECIMAL(5,2) DEFAULT 0,
    sustainability_score DECIMAL(5,2) DEFAULT 0,
    overall_score DECIMAL(5,2) DEFAULT 0,
    rank_position INT DEFAULT 0,
    ranked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE,
    FOREIGN KEY (ranked_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lawn (lawn_id),
    INDEX idx_period (period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TRANSFORMATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS transformations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawn_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    before_image VARCHAR(500),
    after_image VARCHAR(500),
    transformation_date DATE,
    future_requirements TEXT,
    roadmap_image VARCHAR(500) DEFAULT NULL,
    documents JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lawn (lawn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TRANSFORMATION IMAGES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS transformation_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transformation_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_type ENUM('before', 'after', 'progress') DEFAULT 'before',
    caption VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transformation_id) REFERENCES transformations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    icon VARCHAR(50) DEFAULT 'fa-bell',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REPORTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    report_type ENUM('plantation', 'irrigation', 'ranking', 'staff', 'general') DEFAULT 'general',
    format ENUM('pdf', 'excel', 'csv') DEFAULT 'pdf',
    file_path VARCHAR(500),
    parameters JSON,
    generated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SETTINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(100) DEFAULT 'general',
    setting_type ENUM('text', 'number', 'boolean', 'json', 'color', 'file') DEFAULT 'text',
    label VARCHAR(255),
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ACTIVITIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(100),
    description TEXT,
    module VARCHAR(100),
    reference_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MAINTENANCE LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawn_id INT NOT NULL,
    activity_type VARCHAR(100),
    description TEXT,
    performed_by VARCHAR(255),
    log_date DATE,
    status ENUM('completed', 'pending', 'in_progress') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE,
    INDEX idx_lawn (lawn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- FERTILIZER LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS fertilizer_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawn_id INT NOT NULL,
    fertilizer_name VARCHAR(255),
    fertilizer_type VARCHAR(100),
    quantity_kg DECIMAL(8,2),
    application_date DATE,
    applied_by VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PLANTATION DRIVES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS plantation_drives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    drive_date DATE,
    location VARCHAR(255),
    lawn_id INT,
    target_plants INT DEFAULT 0,
    planted_count INT DEFAULT 0,
    organized_by INT,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE SET NULL,
    FOREIGN KEY (organized_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA — ROLES
-- ============================================================
INSERT INTO roles (role_name, role_slug, description, permissions) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', '{"all": true}'),
('Vice Chancellor', 'vice_chancellor', 'University Vice Chancellor - oversight access', '{"dashboard": true, "reports": true, "rankings": true, "lawns": {"view": true}, "staff": {"view": true}}'),
('Registrar', 'registrar', 'University Registrar - administrative access', '{"dashboard": true, "reports": true, "staff": true, "lawns": {"view": true}}'),
('Dean', 'dean', 'Faculty Dean - department level access', '{"dashboard": true, "lawns": {"view": true}, "reports": {"view": true}}'),
('State Officer', 'state_officer', 'State Government Officer - monitoring access', '{"dashboard": true, "reports": true, "rankings": true, "lawns": {"view": true}}'),
('Security Officer', 'security_officer', 'Campus Security Officer', '{"dashboard": true, "lawns": {"view": true}, "staff": {"view": true}}'),
('Treasurer Officer', 'treasurer_officer', 'Budget and finance management', '{"dashboard": true, "reports": true, "staff": {"view": true}}'),
('Irrigation Officer', 'irrigation_officer', 'Irrigation system management', '{"dashboard": true, "irrigation": true, "lawns": {"view": true}}'),
('Plantation Officer', 'plantation_officer', 'Plantation and greenery management', '{"dashboard": true, "lawns": true, "plants": true, "transformations": true}'),
('Staff Management Officer', 'staff_mgmt_officer', 'Staff management and scheduling', '{"dashboard": true, "staff": true, "lawns": {"view": true}}'),
('University Department User', 'dept_user', 'Department level user with limited access', '{"dashboard": true, "lawns": {"view": true}}'),
('Public User', 'public_user', 'Public user with view-only access', '{"lawns": {"view": true}, "rankings": {"view": true}}');

-- ============================================================
-- SEED DATA — DEFAULT ADMIN USER (password: Admin@123)
-- ============================================================
INSERT INTO users (username, email, password, full_name, phone, role_id, department, is_active) VALUES
('superadmin', 'admin@greenery.gov', '$2y$10$M65xY3TrAMhn6Uxnxt5dtOOILqKdLL6k/t8QrSRS.ydR07mZGzlOW', 'System Administrator', '+91-9876543210', 1, 'IT Department', 1);

-- ============================================================
-- SEED DATA — LAWN CATEGORIES
-- ============================================================
INSERT INTO lawn_categories (name, slug, description, color_code, icon, sort_order) VALUES
('Best Lawns', 'best', 'Top-rated lawns with excellent maintenance and greenery', '#28a745', 'fa-crown', 1),
('Average Lawns', 'average', 'Lawns with moderate maintenance requiring improvements', '#ffc107', 'fa-star-half-alt', 2),
('Worst Lawns', 'worst', 'Lawns requiring immediate attention and renovation', '#dc3545', 'fa-exclamation-triangle', 3);

-- ============================================================
-- SEED DATA — IRRIGATION SYSTEMS
-- ============================================================
INSERT INTO irrigation_systems (name, type, description) VALUES
('Automatic Sprinkler System', 'sprinkler', 'Automated sprinkler system with timer control'),
('Drip Irrigation Network', 'drip', 'Water-efficient drip irrigation for targeted watering'),
('Smart IoT Irrigation', 'smart', 'IoT-enabled smart irrigation with soil moisture sensors'),
('Manual Watering', 'manual', 'Manual hose and bucket watering system'),
('Subsurface Irrigation', 'subsurface', 'Underground pipe irrigation system');

-- ============================================================
-- SEED DATA — DEFAULT SETTINGS
-- ============================================================
INSERT INTO settings (setting_key, setting_value, setting_group, setting_type, label, description, is_public) VALUES
('site_name', 'State Level Greenery Management System', 'general', 'text', 'Site Name', 'The name of the website', 1),
('site_tagline', 'Smart Plantation & Environmental Management', 'general', 'text', 'Site Tagline', 'Short tagline for the site', 1),
('site_logo', NULL, 'general', 'file', 'Site Logo', 'Upload site logo', 1),
('primary_color', '#2d6a4f', 'theme', 'color', 'Primary Color', 'Main theme color', 0),
('secondary_color', '#40916c', 'theme', 'color', 'Secondary Color', 'Secondary theme color', 0),
('dark_mode', '0', 'theme', 'boolean', 'Dark Mode', 'Enable dark mode by default', 0),
('items_per_page', '12', 'dashboard', 'number', 'Items Per Page', 'Number of items to show per page', 0),
('enable_notifications', '1', 'notifications', 'boolean', 'Enable Notifications', 'Enable system notifications', 0),
('maintenance_mode', '0', 'general', 'boolean', 'Maintenance Mode', 'Enable maintenance mode', 0),
('smtp_host', '', 'email', 'text', 'SMTP Host', 'Email server host', 0),
('smtp_port', '587', 'email', 'number', 'SMTP Port', 'Email server port', 0);
