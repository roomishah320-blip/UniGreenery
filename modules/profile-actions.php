<?php
/**
 * Profile Actions Handler
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('profile.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('profile.php');
}

$action = sanitize($_POST['action'] ?? '');
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'update_profile':
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        db()->update(
            "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?",
            [$fullName, $email, $phone, $userId]
        );

        // Update session data
        $_SESSION['full_name'] = $fullName;
        
        logActivity('update', 'Updated personal profile information', 'users', $userId);
        setFlash('success', 'Profile updated successfully!');
        redirect('profile.php');
        break;

    case 'update_avatar':
        if (empty($_FILES['avatar']['name'])) {
            setFlash('warning', 'Please select an image first.');
            redirect('profile.php');
        }

        $upload = uploadFile($_FILES['avatar'], 'uploads/avatars', ALLOWED_IMAGE_TYPES);
        if ($upload['success']) {
            // Delete old avatar if exists
            $oldAvatar = db()->fetchOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
            if ($oldAvatar && $oldAvatar['avatar']) {
                deleteUploadedFile($oldAvatar['avatar']);
            }

            db()->update("UPDATE users SET avatar = ? WHERE id = ?", [$upload['filepath'], $userId]);
            logActivity('update', 'Changed profile avatar', 'users', $userId);
            setFlash('success', 'Avatar updated!');
        } else {
            setFlash('danger', $upload['error']);
        }
        redirect('profile.php');
        break;

    case 'update_password':
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            setFlash('danger', 'New passwords do not match.');
            redirect('profile.php');
        }

        $user = db()->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!password_verify($current, $user['password'])) {
            setFlash('danger', 'Current password is incorrect.');
            redirect('profile.php');
        }

        $hashed = password_hash($new, PASSWORD_DEFAULT);
        db()->update("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId]);
        
        logActivity('security', 'Updated account password', 'users', $userId);
        setFlash('success', 'Password updated successfully!');
        redirect('profile.php');
        break;

    default:
        redirect('profile.php');
}
