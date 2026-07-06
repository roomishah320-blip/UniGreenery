<?php
/**
 * Mailer Helper — Task Assignment Notifications
 * Uses PHP mail() which works on XAMPP with configured sendmail/SMTP.
 * For production, replace with PHPMailer/SMTP credentials.
 */
if (!defined('GREENERY_APP')) exit;

/**
 * Send an HTML task assignment email to a staff member.
 *
 * @param string $toEmail    Recipient email
 * @param string $toName     Recipient full name
 * @param string $taskType   e.g. "Maintenance Task", "Plantation Drive"
 * @param string $taskTitle  Title of the assigned task
 * @param string $taskDesc   Short description
 * @param string $taskDate   Date of the task (formatted)
 * @param string $assignedBy Name of the person who assigned the task
 * @return bool
 */
function sendTaskAssignmentEmail($toEmail, $toName, $taskType, $taskTitle, $taskDesc, $taskDate, $assignedBy) {
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName   = SITE_NAME;
    $siteUrl    = BASE_URL;
    $year       = date('Y');
    $firstName  = explode(' ', $toName)[0];
    $subject    = "📋 New Task Assigned: $taskTitle — $siteName";

    // Build HTML body
    $body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Task Assigned</title>
</head>
<body style="margin:0;padding:0;background:#0a0a1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a1a;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:linear-gradient(145deg,#0d1b2a,#112240);border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);max-width:600px;">
        
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#2d6a4f,#40916c);padding:32px 40px;text-align:center;">
            <div style="font-size:36px;margin-bottom:8px;">🌿</div>
            <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">$siteName</h1>
            <p style="margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">Smart Plantation & Environmental Management</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="color:#94a3b8;margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Task Notification</p>
            <h2 style="margin:0 0 24px;color:#e2e8f0;font-size:24px;font-weight:700;">You've been assigned a new task!</h2>
            
            <p style="color:#94a3b8;margin:0 0 28px;font-size:15px;line-height:1.7;">
              Hello <strong style="color:#e2e8f0;">$firstName</strong>, 
              you have been assigned a new <strong style="color:#52b788;">$taskType</strong>. 
              Please log in to the system to view full details and get started.
            </p>

            <!-- Task Card -->
            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-left:4px solid #52b788;border-radius:12px;padding:24px;margin-bottom:28px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <span style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Task Title</span><br>
                    <strong style="color:#e2e8f0;font-size:16px;">$taskTitle</strong>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <span style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Task Type</span><br>
                    <span style="color:#52b788;font-weight:600;">$taskType</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <span style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Scheduled Date</span><br>
                    <span style="color:#e2e8f0;">📅 $taskDate</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <span style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Assigned By</span><br>
                    <span style="color:#e2e8f0;">👤 $assignedBy</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;">
                    <span style="color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Description</span><br>
                    <span style="color:#94a3b8;font-size:14px;line-height:1.6;">$taskDesc</span>
                  </td>
                </tr>
              </table>
            </div>

            <!-- CTA Button -->
            <div style="text-align:center;margin-bottom:32px;">
              <a href="$siteUrl/dashboard.php" style="display:inline-block;background:linear-gradient(135deg,#2d6a4f,#52b788);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:700;font-size:15px;letter-spacing:0.3px;">
                🔗 View Task in Dashboard
              </a>
            </div>

            <p style="color:#475569;font-size:13px;margin:0;line-height:1.6;">
              If you believe this was assigned in error or have questions, please contact your supervisor or system administrator.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:rgba(0,0,0,0.3);padding:20px 40px;text-align:center;border-top:1px solid rgba(255,255,255,0.06);">
            <p style="margin:0;color:#475569;font-size:12px;">© $year $siteName. This is an automated notification.</p>
            <p style="margin:6px 0 0;color:#475569;font-size:12px;">Do not reply to this email.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;

    // Email headers
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Suppress errors — mail() returns false if not configured
    return @mail($toEmail, $subject, $body, $headers);
}

/**
 * Send an HTML password reset email to a user.
 *
 * @param string $toEmail   Recipient email
 * @param string $toName    Recipient full name
 * @param string $resetLink Password reset URL
 * @return bool
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName   = SITE_NAME;
    $year       = date('Y');
    $firstName  = explode(' ', $toName)[0];
    $subject    = "🔒 Password Reset Request — $siteName";

    // Build HTML body
    $body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Your Password</title>
</head>
<body style="margin:0;padding:0;background:#0a0a1a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a1a;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:linear-gradient(145deg,#0d1b2a,#112240);border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);max-width:600px;">
        
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#2d6a4f,#40916c);padding:32px 40px;text-align:center;">
            <div style="font-size:36px;margin-bottom:8px;">🌿</div>
            <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">$siteName</h1>
            <p style="margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">Smart Plantation & Environmental Management</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="color:#94a3b8;margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Security Verification</p>
            <h2 style="margin:0 0 24px;color:#e2e8f0;font-size:24px;font-weight:700;">Password Reset Request</h2>
            
            <p style="color:#94a3b8;margin:0 0 28px;font-size:15px;line-height:1.7;">
              Hello <strong style="color:#e2e8f0;">$firstName</strong>, 
              we received a request to reset your password for your account on the Greenery Management System.
              If you made this request, please click the secure link below to choose a new password:
            </p>

            <!-- CTA Button -->
            <div style="text-align:center;margin-bottom:32px;margin-top:32px;">
              <a href="$resetLink" style="display:inline-block;background:linear-gradient(135deg,#2d6a4f,#52b788);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:700;font-size:15px;letter-spacing:0.3px;">
                🔑 Reset My Password
              </a>
            </div>

            <p style="color:#94a3b8;margin:28px 0 28px;font-size:14px;line-height:1.7;">
              Or copy and paste this URL into your browser:<br>
              <a href="$resetLink" style="color:#52b788;text-decoration:none;word-break:break-all;">$resetLink</a>
            </p>

            <p style="color:#e2e8f0;font-size:13px;margin:24px 0 0;font-weight:600;">
              Please note: This link will expire in 1 hour.
            </p>
            
            <p style="color:#475569;font-size:13px;margin:16px 0 0;line-height:1.6;">
              If you did not request a password reset, you can safely ignore this email — your password will remain secure and unchanged.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:rgba(0,0,0,0.3);padding:20px 40px;text-align:center;border-top:1px solid rgba(255,255,255,0.06);">
            <p style="margin:0;color:#475569;font-size:12px;">© $year $siteName. This is an automated security notification.</p>
            <p style="margin:6px 0 0;color:#475569;font-size:12px;">Do not reply to this email.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;

    // Email headers
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($toEmail, $subject, $body, $headers);
}
