<?php
/**
 * Database Setup & Repair Utility
 * Run this script to fix missing tables or schema mismatches.
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';

echo "<h2>🔧 Greenery System Database Repair</h2>";

try {
    $db = db();
    
    // 1. Create maintenance_logs if missing
    echo "Checking maintenance_logs... ";
    $db->query("CREATE TABLE IF NOT EXISTS maintenance_logs (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color:green'>Done!</span><br>";

    // 2. Create fertilizer_logs if missing
    echo "Checking fertilizer_logs... ";
    $db->query("CREATE TABLE IF NOT EXISTS fertilizer_logs (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color:green'>Done!</span><br>";

    echo "<br><strong style='color:blue'>Success! Your database tables have been synchronized.</strong><br>";
    echo "<p>You can now go back to your <a href='dashboard.php'>Dashboard</a>.</p>";
    
} catch (Exception $e) {
    echo "<br><strong style='color:red'>Error: " . $e->getMessage() . "</strong>";
}
