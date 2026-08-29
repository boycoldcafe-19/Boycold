<?php
session_start();
require_once './config/db_config.php';

if (empty($_SESSION['reset_email'])) {
    header('Location: forgotpass.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $email    = $_SESSION['reset_email'];

    if (!$password || !$confirm) {
        $error = 'Both fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (
        strlen($password) < 8 || strlen($password) > 25
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/[0-9]/', $password)
    ) {
        $error = 'Password does not meet the requirements.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $upd    = $connect->prepare("UPDATE users SET password=? WHERE email=?");
        $upd->bind_param("ss", $hashed, $email);

        if ($upd->execute() && $upd->affected_rows > 0) {
            unset($_SESSION['reset_email']);
            header('Location: login.php?reset=1');
            exit;
        } else {
            $error = 'Could not update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold Café</title>
    <link rel="stylesheet" href="styles/newpassword.css">
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

    <h1 class="font">New Password</h1>
    <p class="p1">Enter new password</p><br>

    <?php if ($error): ?>
        <p style="color:red;font-size:14px;padding-left: 122px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="newpassword.php" method="post">
        <label for="password"></label>
        <div class="password-container">
            <input type="password" id="password" name="password" placeholder="*New Password" required>
            <img src="picture/eye-close.png" alt="Toggle Password Visibility" class="hide-icon">
        </div>
        <br><br>

        <label for="confirm_password"></label>
        <div class="password-container">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="*Confirm New Password" required>
            <img src="picture/eye-close.png" alt="Toggle Password Visibility" class="hide-icon">
        </div>
        <br><br>

        <div class="password-rules">
            <p id="length" class="invalid">✘ 8–25 characters</p>
            <p id="uppercase" class="invalid">✘ At least 1 uppercase letter</p>
            <p id="lowercase" class="invalid">✘ At least 1 lowercase letter</p>
            <p id="number" class="invalid">✘ At least 1 number</p>
        </div>

        <div class="terms">
            <button type="submit">Reset Password</button>
            <p>Don't have an account? <a href="register.php">Create an Account</a></p>
        </div>
    </form>

    <script>
        const hideIcons = document.querySelectorAll('.hide-icon');
        hideIcons.forEach((icon) => {
            icon.addEventListener('click', () => {
                const input = icon.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.src = 'picture/eye-open.png';
                } else {
                    input.type = 'password';
                    icon.src = 'picture/eye-close.png';
                }
            });
        });

        const password = document.getElementById("password");

        const lengthRule = document.getElementById("length");
        const uppercaseRule = document.getElementById("uppercase");
        const lowercaseRule = document.getElementById("lowercase");
        const numberRule = document.getElementById("number");

        password.addEventListener("keyup", function() {

            const value = password.value;
            if (value.length >= 8 && value.length <= 25) {
                lengthRule.textContent = "✔ 8–25 characters";
                lengthRule.classList.remove("invalid");
                lengthRule.classList.add("valid");
            } else {
                lengthRule.textContent = "✘ 8–25 characters";
                lengthRule.classList.remove("valid");
                lengthRule.classList.add("invalid");
            }
            if (/[A-Z]/.test(value)) {
                uppercaseRule.textContent = "✔ At least 1 uppercase letter";
                uppercaseRule.classList.remove("invalid");
                uppercaseRule.classList.add("valid");
            } else {
                uppercaseRule.textContent = "✘ At least 1 uppercase letter";
                uppercaseRule.classList.remove("valid");
                uppercaseRule.classList.add("invalid");
            }
            if (/[a-z]/.test(value)) {
                lowercaseRule.textContent = "✔ At least 1 lowercase letter";
                lowercaseRule.classList.remove("invalid");
                lowercaseRule.classList.add("valid");
            } else {
                lowercaseRule.textContent = "✘ At least 1 lowercase letter";
                lowercaseRule.classList.remove("valid");
                lowercaseRule.classList.add("invalid");
            }

            if (/[0-9]/.test(value)) {
                numberRule.textContent = "✔ At least 1 number";
                numberRule.classList.remove("invalid");
                numberRule.classList.add("valid");
            } else {
                numberRule.textContent = "✘ At least 1 number";
                numberRule.classList.remove("valid");
                numberRule.classList.add("invalid");
            }

        });
    </script>
</body>

</html>