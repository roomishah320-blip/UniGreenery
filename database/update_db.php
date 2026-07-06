<?php
/**
 * Dynamic Database Schema Update and Sync Utility
 * Step 1: Execute this script to synchronize tables for Google OAuth & 13-Role RBAC.
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Database Sync | Greenery System</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #050511; color: #fff; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 30px; }
        h1 { color: #52b788; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .log-entry { margin: 10px 0; font-size: 14px; }
        .log-success { color: #52b788; font-weight: 600; }
        .log-info { color: #3a86c8; }
        .log-error { color: #ff8a94; font-weight: bold; }
        .btn { display: inline-block; background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%); color: #white; border: none; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; margin-top: 20px; transition: 0.3s; color: #fff; }
        .btn:hover { filter: brightness(1.15); transform: translateY(-2px); }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Database Synchronization Manager</h1>
";

try {
    $db = db();
    $pdo = $db->getConnection();

    // ---- 1. UPGRADE USERS TABLE WITH GOOGLE AUTH FIELDS ----
    echo "<div class='log-entry log-info'>Checking users table schema...</div>";
    
    $columnsToAdd = [
        'google_id' => "VARCHAR(255) NULL UNIQUE AFTER password",
        'profile_image' => "VARCHAR(500) NULL AFTER avatar",
        'auth_provider' => "VARCHAR(50) DEFAULT 'local' AFTER profile_image",
        'login_type' => "VARCHAR(50) DEFAULT 'password' AFTER auth_provider",
        'email_verified' => "TINYINT(1) DEFAULT 0 AFTER is_active"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        // Check if column already exists (SHOW COLUMNS doesn't support ? placeholder in some drivers)
        $escapedCol = str_replace("'", "''", $col);
        $check = $db->fetchOne("SHOW COLUMNS FROM users LIKE '$escapedCol'");
        if (!$check) {
            $db->query("ALTER TABLE users ADD COLUMN $col $definition");
            echo "<div class='log-entry log-success'>&check; Added '$col' column to users table.</div>";
        } else {
            echo "<div class='log-entry log-info'>&bull; Column '$col' already exists in users table.</div>";
        }
    }

    // ---- 2. CREATE LOGIN LOGS TABLE FOR BRUTE FORCE/SECURITY TRACKING ----
    echo "<div class='log-entry log-info'>Verifying login_logs table...</div>";
    $db->query("CREATE TABLE IF NOT EXISTS login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        email VARCHAR(255) NOT NULL,
        status ENUM('success', 'failed', 'locked') NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='log-entry log-success'>&check; login_logs table is ready.</div>";

    // ---- 3. CREATE SECURE DATABASE SESSIONS TABLE ----
    echo "<div class='log-entry log-info'>Verifying sessions table...</div>";
    $db->query("CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(255) PRIMARY KEY,
        user_id INT NULL,
        payload TEXT NOT NULL,
        last_activity INT NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<div class='log-entry log-success'>&check; sessions table is ready.</div>";

    // ---- 4. SYNCHRONIZE AND SEED THE 13 ROLES ----
    echo "<div class='log-entry log-info'>Synchronizing 13 enterprise system roles...</div>";
    
    $requiredRoles = [
        [
            'name'  => 'Super Admin',
            'slug'  => 'super_admin',
            'desc'  => 'Full system access with all administrative permissions',
            'perms' => ['all' => true]
        ],
        [
            'name'  => 'Vice Chancellor',
            'slug'  => 'vice_chancellor',
            'desc'  => 'University Vice Chancellor - oversight, analytics & state plantation status',
            'perms' => [
                'dashboard'       => true,
                'lawns'           => ['view' => true],
                'plants'          => ['view' => true],
                'rankings'        => ['view' => true],
                'reports'         => ['view' => true],
                'staff'           => ['view' => true],
                'maintenance'     => ['view' => true],
                'map_management'  => ['view' => true],
                'transformations' => ['view' => true],
                'projects'        => ['view' => true],
            ]
        ],
        [
            'name'  => 'Registrar',
            'slug'  => 'registrar',
            'desc'  => 'University Registrar - department records, reports, faculty & staff',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'plants'     => ['view' => true],
                'rankings'   => ['view' => true],
                'reports'    => ['view' => true],
                'staff'      => ['view' => true, 'create' => true, 'edit' => true],
                'projects'   => ['view' => true],
                'maintenance'=> ['view' => true],
            ]
        ],
        [
            'name'  => 'Dean',
            'slug'  => 'dean',
            'desc'  => 'Department Dean - assigned lawns, plant reports & staff',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'plants'     => ['view' => true],
                'rankings'   => ['view' => true],
                'reports'    => ['view' => true],
                'staff'      => ['view' => true],
                'projects'   => ['view' => true],
                'maintenance'=> ['view' => true],
            ]
        ],
        [
            'name'  => 'State Officer',
            'slug'  => 'state_officer',
            'desc'  => 'State Government Officer - monitor state-level greenery analytics',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'rankings'   => ['view' => true],
                'reports'    => ['view' => true],
                'projects'   => ['view' => true],
                'maintenance'=> ['view' => true],
                'map_management' => ['view' => true],
            ]
        ],
        [
            'name'  => 'Treasurer Officer',
            'slug'  => 'treasurer_officer',
            'desc'  => 'Budget & Finance Officer - manage budget, expenses and statements',
            'perms' => [
                'dashboard'  => true,
                'reports'    => ['view' => true, 'create' => true],
                'staff'      => ['view' => true],
                'projects'   => ['view' => true],
            ]
        ],
        [
            'name'  => 'Security Officer',
            'slug'  => 'security_officer',
            'desc'  => 'Campus Security - visitor activities and restricted zones',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'staff'      => ['view' => true],
                'map_management' => ['view' => true],
            ]
        ],
        [
            'name'  => 'Irrigation Officer',
            'slug'  => 'irrigation_officer',
            'desc'  => 'Irrigation Manager - water schedules, zones and water reports',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'irrigation' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'reports'    => ['view' => true],
                'map_management' => ['view' => true],
            ]
        ],
        [
            'name'  => 'Plantation Officer',
            'slug'  => 'plantation_officer',
            'desc'  => 'Greenery & Plantation Manager - plants, drives and transformations',
            'perms' => [
                'dashboard'       => true,
                'categories'      => ['view' => true],
                'lawns'           => ['view' => true, 'create' => true, 'edit' => true],
                'plants'          => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'transformations' => ['view' => true, 'create' => true, 'edit' => true],
                'projects'        => ['view' => true, 'create' => true, 'edit' => true],
                'maintenance'     => ['view' => true, 'create' => true],
                'rankings'        => ['view' => true],
                'irrigation'      => ['view' => true],
                'map_management'  => ['view' => true],
            ]
        ],
        [
            'name'  => 'Staff Management Officer',
            'slug'  => 'staff_mgmt_officer',
            'desc'  => 'Staff Scheduler - assign staff, schedules and shifts',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'staff'      => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'projects'   => ['view' => true],
                'maintenance'=> ['view' => true],
            ]
        ],
        [
            'name'  => 'Faculty',
            'slug'  => 'faculty',
            'desc'  => 'University Faculty - view assigned lawns and post reports',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'plants'     => ['view' => true],
                'rankings'   => ['view' => true],
                'reports'    => ['view' => true, 'create' => true],
                'projects'   => ['view' => true],
            ]
        ],
        [
            'name'  => 'Staff',
            'slug'  => 'staff',
            'desc'  => 'Field Staff - assigned duties and daily tasks',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'maintenance'=> ['view' => true],
                'projects'   => ['view' => true],
                'irrigation' => ['view' => true],
            ]
        ],
        [
            'name'  => 'Public/User',
            'slug'  => 'public_user',
            'desc'  => 'Public Visitor - browse public gallery, rankings and awareness content',
            'perms' => [
                'dashboard'  => true,
                'lawns'      => ['view' => true],
                'rankings'   => ['view' => true],
            ]
        ]
    ];

    foreach ($requiredRoles as $roleDef) {
        // Check if role exists by slug
        $existing = $db->fetchOne("SELECT id FROM roles WHERE role_slug = ?", [$roleDef['slug']]);
        $permsJson = json_encode($roleDef['perms']);

        if ($existing) {
            // Update existing role info, description, AND permissions to match the target config
            $db->update("UPDATE roles SET role_name = ?, description = ?, permissions = ? WHERE id = ?", [
                $roleDef['name'], $roleDef['desc'], $permsJson, $existing['id']
            ]);
            echo "<div class='log-entry log-success'>&check; Synced role: <b>{$roleDef['name']}</b> ({$roleDef['slug']}) — permissions updated.</div>";
        } else {
            // Insert missing role
            $roleId = $db->insert("INSERT INTO roles (role_name, role_slug, description, permissions, is_active) VALUES (?, ?, ?, ?, 1)", [
                $roleDef['name'], $roleDef['slug'], $roleDef['desc'], $permsJson
            ]);
        }
    }

    // ---- 5. SYNCHRONIZE AND SEED THE SYSTEM SETTINGS ----
    echo "<div class='log-entry log-info'>Synchronizing system settings...</div>";
    
    $defaultSettings = [
        [
            'key' => 'weather_location',
            'value' => 'Dera Ghazi Khan',
            'group' => 'dashboard',
            'type' => 'text',
            'label' => 'Weather Location City',
            'desc' => 'City name displayed in the weather widget'
        ],
        [
            'key' => 'weather_latitude',
            'value' => '30.0511',
            'group' => 'dashboard',
            'type' => 'text',
            'label' => 'Weather Latitude',
            'desc' => 'Latitude for real-time weather coordinates'
        ],
        [
            'key' => 'weather_longitude',
            'value' => '70.6372',
            'group' => 'dashboard',
            'type' => 'text',
            'label' => 'Weather Longitude',
            'desc' => 'Longitude for real-time weather coordinates'
        ]
    ];

    foreach ($defaultSettings as $s) {
        $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$s['key']]);
        if (!$existing) {
            $db->insert("INSERT INTO settings (setting_key, setting_value, setting_group, setting_type, label, description, is_public) VALUES (?, ?, ?, ?, ?, ?, 1)", [
                $s['key'], $s['value'], $s['group'], $s['type'], $s['label'], $s['desc']
            ]);
            echo "<div class='log-entry log-success'>&check; Added missing setting: <b>{$s['label']}</b> ({$s['key']}).</div>";
        } else {
            echo "<div class='log-entry log-info'>&bull; Setting '{$s['key']}' already exists.</div>";
        }
    }


    echo "<br><div class='log-success' style='font-size: 18px; margin-top:20px;'>🎉 Schema upgrade and roles synchronization completed successfully!</div>";
    echo "<p>Your site's core database is fully prepared for Google Authentication and RBAC.</p>";
    echo "<a href='../dashboard.php' class='btn'>Return to Dashboard</a>";

} catch (Exception $e) {
    echo "<div class='log-entry log-error'>&times; Sync Failure: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p style='color:red;'>Make sure you have imported the base database tables from database/greenery_db.sql first.</p>";
}

echo "
</div>
</body>
</html>";
