<?php
// Test script to check PHPMailer and OpenSSL configuration
echo "<h2>PHP Configuration Check</h2>";

// Check OpenSSL extension
if (extension_loaded('openssl')) {
    echo "<p style='color:green;'>✓ OpenSSL extension is loaded</p>";
} else {
    echo "<p style='color:red;'>✗ OpenSSL extension is NOT loaded - This is required for PHPMailer SMTP</p>";
}

// Check PHPMailer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color:green;'>✓ Composer autoload.php exists</p>";
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color:green;'>✓ PHPMailer class is available</p>";
    } else {
        echo "<p style='color:red;'>✗ PHPMailer class is NOT available</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Composer autoload.php NOT found</p>";
}

// Test SMTP connection
echo "<h2>SMTP Connection Test</h2>";
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'boycoldcafe19@gmail.com';
    $mail->Password   = 'plcj mrda ruwk yvyb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='background:#f0f0f0;padding:5px;margin:2px;font-family:monospace;font-size:12px;'>[$level] $str</div>";
    };

    $mail->setFrom('boycoldcafe19@gmail.com', 'Test');
    $mail->addAddress('test@example.com', 'Test Recipient');
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email';

    echo "<p>Attempting to connect to SMTP server...</p>";
    $mail->send();
    echo "<p style='color:green;'>✓ Email sent successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ SMTP Error: " . $e->getMessage() . "</p>";
    echo "<p style='color:red;'>Error Info: " . $mail->ErrorInfo . "</p>";
}
?>
