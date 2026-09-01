<?php
// ── mailer.php — PHPMailer helper (Composer vendor) ──────
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function sendOTPEmail(string $toEmail, string $toName, string $otp, string $type = 'register'): bool
{
    $isReset  = ($type === 'reset');
    $subject  = $isReset ? 'BoyCold Cafe - Password Reset OTP'
                         : 'BoyCold Cafe - Email Verification OTP';
    $action   = $isReset ? 'reset your password' : 'verify your email address';

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
      body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:0}
      .wrap{max-width:500px;margin:40px auto;background:#fff;border-radius:12px;
            overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)}
      .hdr{background:#1a1a2e;padding:30px;text-align:center}
      .hdr h1{color:#e8c547;margin:0;font-size:26px;letter-spacing:2px}
      .bdy{padding:36px 40px}
      .bdy p{color:#444;line-height:1.7;margin:0 0 16px}
      .box{background:#f0f4ff;border:2px dashed #1a1a2e;border-radius:10px;
           text-align:center;padding:20px;margin:28px 0}
      .code{font-size:42px;font-weight:900;letter-spacing:12px;color:#1a1a2e;display:block}
      .note{font-size:12px;color:#888;margin-top:8px}
      .ftr{background:#f9f9f9;text-align:center;padding:16px;font-size:12px;color:#aaa}
    </style></head><body>
    <div class='wrap'>
      <div class='hdr'><h1>☕ BoyCold Café</h1></div>
      <div class='bdy'>
        <p>Hi <strong>$toName</strong>,</p>
        <p>Use the code below to $action. Valid for <strong>10 minutes</strong>.</p>
        <div class='box'>
          <span class='code'>$otp</span>
          <div class='note'>Do not share this code with anyone.</div>
        </div>
        <p>If you didn't request this, ignore this email.</p>
        <p>— The BoyCold Café Team</p>
      </div>
      <div class='ftr'>© " . date('Y') . " BoyCold Café. All rights reserved.</div>
    </div></body></html>";

    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('GMAIL_USERNAME') ?: 'boycoldcafe19@gmail.com';
        $mail->Password   = getenv('GMAIL_APP_PASSWORD') ?: 'zhjh ocsx gaww tvxv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // Enable debugging (set to 0 for production, 2 for development)
        $mail->SMTPDebug  = getenv('SMTP_DEBUG') ? (int)getenv('SMTP_DEBUG') : 0;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str");
        };

        // Additional SMTP options for better compatibility
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Set timeout values
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        $mail->setFrom($mail->Username, 'BoyCold Cafe');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = "Your OTP: $otp  (valid 10 minutes)";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = 'Mailer Error: ' . $e->getMessage();
        error_log($errorMsg);
        error_log('SMTP Error Info: ' . $mail->ErrorInfo);
        return false;
    }
}