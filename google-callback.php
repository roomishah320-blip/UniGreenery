<?php
/**
 * Google OAuth 2.0 Callback Handler
 * Step 1: Google Authentication Integration
 * Step 11: Auto Role Assignment
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if (!empty($error)) {
    setFlash('danger', 'Google Authentication Error: ' . sanitize($error));
    redirect('login.php');
}

if (empty($code)) {
    setFlash('danger', 'Invalid authentication request. Missing code.');
    redirect('login.php');
}

// 1. Exchange Auth Code for Access Token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP environment SSL bypass
$response = curl_exec($ch);

if (curl_errno($ch)) {
    setFlash('danger', 'Network connection error during Google login: ' . curl_error($ch));
    curl_close($ch);
    redirect('login.php');
}
curl_close($ch);

$tokenData = json_decode($response, true);
if (isset($tokenData['error'])) {
    setFlash('danger', 'OAuth Exchange Failed: ' . ($tokenData['error_description'] ?? $tokenData['error']));
    redirect('login.php');
}

$accessToken = $tokenData['access_token'] ?? '';
if (empty($accessToken)) {
    setFlash('danger', 'Authentication token exchange was empty.');
    redirect('login.php');
}

// 2. Fetch User Profile Info via Access Token
$profileUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $profileUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$profileResponse = curl_exec($ch);
curl_close($ch);

$profile = json_decode($profileResponse, true);
if (empty($profile) || !isset($profile['sub'])) {
    setFlash('danger', 'Unable to retrieve your Google profile details.');
    redirect('login.php');
}

$googleId = sanitize($profile['sub']);
$fullName = sanitize($profile['name'] ?? '');
$email = sanitize($profile['email'] ?? '');
$avatarUrl = sanitize($profile['picture'] ?? '');
$emailVerified = isset($profile['email_verified']) && $profile['email_verified'] ? 1 : 0;

if (empty($email)) {
    setFlash('danger', 'Google did not return a valid email address.');
    redirect('login.php');
}

try {
    // 3. Check if user already exists (by Google ID or Email)
    $user = db()->fetchOne(
        "SELECT u.*, r.role_name, r.role_slug FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.google_id = ? AND r.is_active = 1",
        [$googleId]
    );

    if (!$user) {
        // Find by email to link existing local account
        $user = db()->fetchOne(
            "SELECT u.*, r.role_name, r.role_slug FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ? AND r.is_active = 1",
            [$email]
        );

        if ($user) {
            // Link existing account with Google OAuth
            db()->update(
                "UPDATE users SET google_id = ?, auth_provider = 'Google', login_type = 'Google', profile_image = ?, email_verified = ?, last_login = NOW() WHERE id = ?",
                [$googleId, $avatarUrl, $emailVerified, $user['id']]
            );
            
            logActivity('auth_link', 'Linked existing account with Google Sign-In', 'auth', $user['id']);
        } else {
            // 4. Create New Account (Auto Role Assignment)
            $roleSlug = 'public_user'; // default fallback
            
            if (str_ends_with($email, '@faculty.university.edu')) {
                $roleSlug = 'faculty';
            } elseif (str_ends_with($email, '@staff.university.edu')) {
                $roleSlug = 'staff';
            }

            // Get target role ID from database
            $role = db()->fetchOne("SELECT id, role_name FROM roles WHERE role_slug = ?", [$roleSlug]);
            $roleId = $role ? $role['id'] : 13; // Fallback to last role ID
            
            // Generate clean username from email prefix
            $usernameBase = explode('@', $email)[0];
            $username = $usernameBase;
            
            // Verify username uniqueness
            $count = 1;
            while (db()->fetchOne("SELECT id FROM users WHERE username = ?", [$username])) {
                $username = $usernameBase . $count;
                $count++;
            }

            // Create new secure user without password (uses Google auth)
            $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $userId = db()->insert(
                "INSERT INTO users (username, email, password, full_name, role_id, google_id, profile_image, auth_provider, login_type, email_verified, is_active, last_login) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Google', 'Google', ?, 1, NOW())",
                [$username, $email, $randomPassword, $fullName, $roleId, $googleId, $avatarUrl, $emailVerified]
            );

            if (!$userId) {
                throw new Exception("Registration failed. Unable to write user details to database.");
            }

            // Log registration and fetch new user payload
            logActivity('register_google', "Created user via Google Sign-In: $username", 'auth', $userId);
            
            $user = db()->fetchOne(
                "SELECT u.*, r.role_name, r.role_slug FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ?",
                [$userId]
            );
        }
    } else {
        // Update last login timestamp and profile picture changes
        db()->update(
            "UPDATE users SET profile_image = ?, last_login = NOW() WHERE id = ?",
            [$avatarUrl, $user['id']]
        );
        logActivity('login_google', 'User signed in via Google OAuth', 'auth', $user['id']);
    }

    // 5. Establish secure session payload
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_slug'] = $user['role_slug'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['last_activity'] = time();

    // Set dynamic login success notification
    setFlash('success', "Welcome back, {$user['full_name']}! You have successfully signed in with Google.");
    
    // Redirect to dashboard
    redirect('dashboard.php');

} catch (Exception $e) {
    setFlash('danger', 'Authentication Transaction Failure: ' . htmlspecialchars($e->getMessage()));
    redirect('login.php');
}
