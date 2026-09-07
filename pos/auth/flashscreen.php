<?php
require_once __DIR__ . '/guard.php';
pos_start_session();

if (empty($_SESSION['employee_id'])) {
    header('Location: ../../User/login.php');
    exit;
}

if (!empty($_SESSION['pos_pin_verified'])) {
    header('Location: ../dashboard/pos-shift.php');
    exit;
}

$redirectPage = 'verify_pin.php';
$branchName = $_SESSION['branch_name'] ?? 'Assigned POS Branch';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="auth-css/flashscreen.css">
    <link rel="icon" href="/img/LOGO 2.png">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold Cafe</title>
</head>
<body>
    <div class="flashscreen">
        <img src="/img/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt="BoyCold Cafe">
        <div class="middle-part">
            <h1>BoyCold Cafe</h1>
            <p><?= htmlspecialchars($branchName) ?></p>
        </div>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = "<?= $redirectPage ?>";
        }, 4300);
    </script>
</body>
</html>