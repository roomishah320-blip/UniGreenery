<?php
/**
 * Index / Landing Page
 * Redirects to dashboard if logged in, otherwise to login
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
