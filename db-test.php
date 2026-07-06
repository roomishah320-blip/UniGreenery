<?php
/**
 * Database Connection Diagnostic Script
 * Upload this file to your hosting root to test database connectivity.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>DB Test Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333; padding: 30px; }
        .card { max-width: 600px; margin: 0 auto; background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #2d6a4f; border-bottom: 2px solid #52b788; padding-bottom: 10px; margin-top: 0; }
        .info { background: #eef8f4; border-left: 4px solid #52b788; padding: 12px; margin: 15px 0; font-family: monospace; font-size: 14px; }
        .status { font-weight: bold; padding: 8px; border-radius: 4px; display: inline-block; margin-top: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .failed { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        code { background: #eee; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class='card'>
    <h2>🔍 Database Connection Diagnostics</h2>
";

// 1. Locate config.php
$configPath = __DIR__ . '/includes/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
    echo "<p style='color:green;'>✔ Loaded <code>includes/config.php</code> successfully.</p>";
} else {
    echo "<div class='status failed'>❌ Error: Could not find <code>includes/config.php</code> at the expected path.</div>";
    echo "</div></body></html>";
    exit;
}

// 2. Display the configuration settings (hiding password for security)
echo "<p>Checking database configuration variables defined in your config file:</p>";
echo "<div class='info'>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_CHARSET: " . DB_CHARSET . "<br>";
echo "DB_PASS: " . (empty(DB_PASS) ? '[EMPTY]' : str_repeat('*', strlen(DB_PASS)) . ' (' . strlen(DB_PASS) . ' chars)') . "<br>";
echo "</div>";

// 3. Test Connection via PDO
echo "<h3>Testing PDO Connection...</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES    => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<div class='status success'>✔ PDO Connection SUCCESSFUL!</div>";
} catch (PDOException $e) {
    echo "<div class='status failed'>❌ PDO Connection FAILED</div>";
    echo "<p style='color:red; font-family:monospace; margin-top:10px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. Test Connection via MySQLi (as alternative diagnostic)
echo "<h3>Testing MySQLi Connection...</h3>";
try {
    $link = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($link) {
        echo "<div class='status success'>✔ MySQLi Connection SUCCESSFUL!</div>";
        mysqli_close($link);
    } else {
        echo "<div class='status failed'>❌ MySQLi Connection FAILED</div>";
        echo "<p style='color:red; font-family:monospace; margin-top:10px;'>" . htmlspecialchars(mysqli_connect_error()) . "</p>";
    }
} catch (Exception $e) {
    echo "<div class='status failed'>❌ MySQLi Exception occurred</div>";
    echo "<p style='color:red; font-family:monospace; margin-top:10px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "
</div>
</body>
</html>";
