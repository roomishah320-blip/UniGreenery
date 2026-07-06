<?php
/**
 * Emergency Login Fix Script
 * Visit this page once to reset admin credentials to:
 * Username: admin
 * Password: admin123
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/db.php';

$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);

try {
    // Check if roles exist
    $role = db()->fetchOne("SELECT id FROM roles WHERE role_slug = 'super_admin'");
    if (!$role) {
        die("Error: Roles table is empty. Please import greenery_db.sql first.");
    }

    // Update or Insert the admin user
    $user = db()->fetchOne("SELECT id FROM users WHERE id = 1");
    if ($user) {
        db()->update("UPDATE users SET username = ?, password = ?, full_name = ?, role_id = ?, is_active = 1 WHERE id = 1", [
            $username, $password, 'System Administrator', $role['id']
        ]);
        echo "<h2 style='color:green;'>Success! Admin credentials updated.</h2>";
    } else {
        db()->insert("INSERT INTO users (id, username, email, password, full_name, role_id, is_active) VALUES (1, ?, ?, ?, ?, ?, 1)", [
            $username, 'admin@university.edu', $password, 'System Administrator', $role['id']
        ]);
        echo "<h2 style='color:green;'>Success! Admin user created.</h2>";
    }

    echo "<p>You can now login at <a href='login.php'>login.php</a> using:</p>";
    echo "<ul><li><b>Username:</b> admin</li><li><b>Password:</b> admin123</li></ul>";
    echo "<p style='color:red;'><b>SECURITY WARNING:</b> Please delete this file (fix-login.php) after use.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error: " . $e->getMessage() . "</h2>";
}
?>
