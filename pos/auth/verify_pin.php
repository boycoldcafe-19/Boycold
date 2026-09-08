<?php
require_once __DIR__ . '/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';

// Check if user is logged in
if (empty($_SESSION['employee_id'])) {
    header('Location: ../../User/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_pin') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'errors' => []];

    $pin = trim($_POST['pin'] ?? '');

    if (!preg_match('/^\d{4}$/', $pin)) {
        $response['errors']['pin'] = 'PIN must contain exactly 4 digits.';
    }

    if (empty($response['errors'])) {
        $lockedUntil = (int) ($_SESSION['pos_pin_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            $response['errors']['pin'] = 'Too many attempts. Please try again later.';
            echo json_encode($response);
            exit;
        }

        $stmt = $connect->prepare('SELECT id, employee_name, email, role, pin, is_active, branch_id FROM employees WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['employee_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $employeeIsValid = $result
            && (int) $result['is_active'] === 1
            && in_array($result['role'], ['cashier', 'admin'], true)
            && (int) $result['branch_id'] > 0;

        if (!$employeeIsValid || empty($result['pin']) || !password_verify($pin, $result['pin'])) {
            $response['errors']['pin'] = 'Incorrect PIN.';
            $_SESSION['pos_pin_attempts'] = (int) ($_SESSION['pos_pin_attempts'] ?? 0) + 1;
            if ($_SESSION['pos_pin_attempts'] >= 5) {
                $_SESSION['pos_pin_locked_until'] = time() + 300;
                $_SESSION['pos_pin_attempts'] = 0;
            }
        } else {
            session_regenerate_id(true);
            // Refresh branch identity from the verified database record so a stale
            // POS session cannot reject a correct PIN after switching branches.
            $_SESSION['employee_id'] = (int) $result['id'];
            $_SESSION['employee_name'] = $result['employee_name'];
            $_SESSION['employee_email'] = $result['email'];
            $_SESSION['employee_role'] = $result['role'];
            $_SESSION['branch_id'] = (int) $result['branch_id'];
            $_SESSION['pos_pin_verified'] = true;
            $_SESSION['pos_authenticated'] = true;
            $_SESSION['pos_login_at'] = date('c');
            $_SESSION['pos_pin_attempts'] = 0;
            unset($_SESSION['pos_pin_locked_until']);
            $response['success'] = true;
            $response['redirect'] = '../dashboard/pos-shift.php';
        }
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="auth-css/pin.css">
    <link rel="icon" href="/img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>BoyCold Cafe</title>
</head>
<body>
    <nav>
        <div class="nav-logo">
            <img src="/img/BoyCold Logo 2.png" alt="BoyCold Cafe Logo" class="logo">
        </div>
    </nav>

    <section class="pin-container">
        <div class="form-step active">
            <h1>Enter Your PIN</h1>
            <p>Point of sale</p>

            <form id="verifyPinForm" novalidate>
                <div class="pin-group">
                    <div class="pin-inputs">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                    </div>
                    <span class="error" id="pinError"></span>
                </div>
                <div class="btns">
                    <button type="button" class="cancel-btn" id="cancelBtn">
                        Cancel
                    </button>
                    <button class="submit-btn" id="verifyBtn">
                        Verify
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        const verifyPinForm = document.getElementById("verifyPinForm");
        const pinBoxes = document.querySelectorAll(".pin-box");
        const pinError = document.getElementById("pinError");
        const cancelBtn = document.getElementById("cancelBtn");
        const verifyBtn = document.getElementById("verifyBtn");

        function setupPinBoxes(boxes, errorElement) {
            boxes.forEach((box, index) => {
                box.addEventListener("keydown", (e) => {
                    const allowedKeys = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Enter"];
                    if (allowedKeys.includes(e.key)) {
                        errorElement.textContent = "";
                        if (e.key === "Backspace") {
                            if (box.value === "" && index > 0) {
                                boxes[index - 1].focus();
                            }
                        }
                        return;
                    }
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                        errorElement.textContent = "*Use numerical digits only.*";
                        return;
                    }
                    errorElement.textContent = "";
                });

                box.addEventListener("input", () => {
                    box.value = box.value.replace(/\D/g, "");
                    if (box.value && index < boxes.length - 1) {
                        boxes[index + 1].focus();
                    }
                });
            });
        }

        function getPin(boxes) {
            return [...boxes].map(box => box.value).join("");
        }

        setupPinBoxes(pinBoxes, pinError);

        verifyPinForm.addEventListener("submit", (e) => {
            e.preventDefault();
            pinError.textContent = "";
            const pin = getPin(pinBoxes);

            if (pin.length !== 4) {
                pinError.textContent = "PIN must contain exactly 4 digits.";
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.textContent = "Verifying...";

            const formData = new FormData();
            formData.append("action", "verify_pin");
            formData.append("pin", pin);

            fetch("verify_pin.php", { method: "POST", body: formData })
                .then(res => res.json())
                .then(data => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = "Verify";

                    if (!data.success) {
                        if (data.errors.pin) pinError.textContent = data.errors.pin;
                        return;
                    }

                    window.location.href = data.redirect;
                })
                .catch(() => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = "Verify";
                    pinError.textContent = "Something went wrong. Please try again.";
                });
        });

        cancelBtn.addEventListener("click", () => {
            window.location.href = "login.php";
        });

        pinBoxes[0].focus();
    </script>
</body>
</html>
