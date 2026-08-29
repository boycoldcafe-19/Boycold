<?php
session_start();
if (empty($_SESSION['otp_email']) || ($_SESSION['otp_type'] ?? '') !== 'register') {
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold Café</title>
    <link rel="stylesheet" href="styles/verifyotp.css">
    <link rel="icon" type="image/png" href="picture/icon.png">
</head>
<header>
    <img src="picture/LOGO.png" alt="BoyCold CAFE Logo" width="50px">
</header>
<body>
    <div class="pic1">
        <img src="picture/Mask group.png" alt="Sign Up Image" width="690px">
    </div>
    <div class="hero-banner">
        <img src="picture/Mask group.png" alt="BoyCold Café hero">
    </div>
    <div class="otp">
        <div class="pic2">
            <img src="picture/otp.png" alt="OTP Image">
        </div>
        <p class="p1">Thanks - if you have a BoyCold account,
            we've sent you an email.</p>
        <a href="otp.php?mode=register"><button type="button">Verify OTP</button></a>
    </div>
</body>
</html>