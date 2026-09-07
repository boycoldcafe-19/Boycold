<?php
require_once '../config/google.php';
require_once '../config/db_config.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../pos/auth/guard.php';

function clearPosSessionIfPresent(): void
{
    if (empty($_COOKIE['POS_SESSION'])) {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    pos_start_session();
    pos_clear_session();
    boycold_start_session('PHPSESSID');
}

function startUnifiedPosSession(array $employee): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    pos_start_session();
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['employee_id'] = (int) $employee['id'];
    $_SESSION['employee_name'] = $employee['employee_name'];
    $_SESSION['employee_email'] = $employee['email'];
    $_SESSION['employee_role'] = $employee['role'];
    $_SESSION['branch_id'] = (int) $employee['branch_id'];
    $_SESSION['branch_code'] = $employee['branch_code'];
    $_SESSION['branch_name'] = $employee['branch_name'];
    $_SESSION['pos_authenticated'] = false;
    $_SESSION['pos_pin_verified'] = false;
    $_SESSION['pos_login_at'] = null;
}

function authenticatePosEmployee(mysqli $connect, string $email, string $password): ?array
{
    $stmt = $connect->prepare(
        "SELECT e.id, e.employee_name, e.email, e.password, e.role, e.is_active, e.branch_id,
            b.branch_code, b.branch_name, b.status AS branch_status
         FROM employees e
         LEFT JOIN branches b ON b.id = e.branch_id
         WHERE e.email = ? AND e.branch_id > 0
         LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$employee || !password_verify($password, $employee['password'])) {
        return null;
    }

    if ((int) $employee['is_active'] !== 1 || ($employee['branch_status'] ?? '') !== 'active') {
        $employee['_inactive'] = true;
    }

    return $employee;
}

$error = $_SESSION['google_error'] ?? '';
unset($_SESSION['google_error']);
$verified = isset($_GET['verified']);
$reset    = isset($_GET['reset']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $employee = authenticatePosEmployee($connect, $email, $password);

        if ($employee) {
            if (!empty($employee['_inactive'])) {
                $error = 'This POS account has been deactivated.';
            } else {
                startUnifiedPosSession($employee);
                $response = ['success' => true, 'redirect' => '../pos/auth/flashscreen.php'];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
        }

        if ($error === '') {
            $stmt = $connect->prepare("SELECT id, firstname, lastname, user_name, password, account_status FROM users WHERE email=? AND is_verified=1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && (($user['account_status'] ?? 'active') !== 'active')) {
            $error = 'This account is inactive. Please contact the administrator to reactivate it.';
        } elseif ($user && password_verify($password, $user['password'])) {
            clearPosSessionIfPresent();
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name']  = $user['user_name'];

            if ($remember) {
                setcookie('remember_email', $email, time() + (86400 * 30), '/');
            } else {
                setcookie('remember_email', '', time() - 3600, '/');
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => 'home.php']);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
        }

        if ($error !== '') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => ['password' => $error]]);
            exit;
        }
    }

    if ($error !== '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['password' => $error]]);
        exit;
    }
}

$savedEmail = $_COOKIE['remember_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold Café</title>
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="icon" type="image/png" href="../picture/icon.png">
</head>
<header>
    <img src="../picture/LOGO.png" alt="BoyCold CAFE Logo" width="50px">
</header>

<body>
    <div class="pic1">
        <img src="../picture/Mask group.png" alt="Sign Up Image" width="690px">
    </div>

    <div class="hero-banner">
        <img src="../picture/Mask group.png" alt="BoyCold Café hero">
    </div>

    <h1 class="font">Log in Now</h1>
    <h2 class="p1">Please Log in to continue using our app</h2>

    <?php if ($verified): ?>
        <p class="form-message success">
            ✅ Account verified! You can now log in.
        </p>
    <?php endif; ?>

    <?php if ($reset): ?>
        <p class="form-message success">
            ✅ Password reset successfully! Please log in.
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="form-message error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <label for="email"></label>
        <input type="email" id="email" name="email" placeholder="*Email" value="<?= htmlspecialchars($savedEmail) ?>" required><br><br>

        <label for="password"></label>
        <div class="password-container">
            <input type="password" id="password" name="password" placeholder="*Password" required>
            <img src="../picture/eye-close.png" alt="Hide Icon" class="hide-icon">
        </div>

        <div class="remember-row">
            <label class="remember-label" for="Remember">
                <input type="checkbox" id="Remember" name="remember" <?= $savedEmail ? 'checked' : '' ?> required>
                Remember me</label>
            <a href="forgotpass.php" class="forgot">Forgot Password?</a>
        </div>

        <div class="terms">
            <button type="submit">Log In</button>
            <p>Don't have an account? <a href="register.php">Create an Account</a></p>
        </div>
        <div class="google">
            <p>Or sign in with:</p>
            <a href="<?= getGoogleAuthUrl() ?>" class="google-btn">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="" width="20" height="20">
                <span>Continue with Google</span>
            </a>
        </div>
    </form>

    <script>
        const passwordInput = document.getElementById('password');
        const hideIcon = document.querySelector('.hide-icon');
        hideIcon.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                hideIcon.src = '../picture/eye-open.png';
            } else {
                passwordInput.type = 'password';
                hideIcon.src = '../picture/eye-close.png';
            }
        });
    </script>
</body>

</html>