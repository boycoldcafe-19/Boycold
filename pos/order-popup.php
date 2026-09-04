<?php
// ── order-popup.php ───────────────────────────────────────────
// Order confirmation card, opened right after checkout.php places
// an order (?order_id=123) — either as its own page or embedded
// in checkout.php's popup iframe.
// ─────────────────────────────────────────────────────────────
session_name('POS_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once '../config/db_config.php';

$userId     = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
$isStaff    = $employeeId > 0 || !empty($_SESSION['is_admin']);

// Log session info for debugging
error_log('[Order Popup] Session check - userId: ' . $userId . ', employeeId: ' . $employeeId . ', isStaff: ' . ($isStaff ? 'true' : 'false'));

if ($userId <= 0 && !$isStaff) {
    error_log('[Order Popup] No valid session, redirecting to flash screen');
    header('Location: auth/flashscreen.php');
    exit;
}

// Resolve user_name + phone fresh from the DB — never trust the
// session for this (same reasoning as orders_api.php: keeps
// orders.user_name lookups exact even if the session is stale).
$userRow = null;
if ($userId > 0) {
    $uStmt = $connect->prepare("SELECT user_name, phone FROM users WHERE id = ?");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $userRow = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();
}

if (!$userRow && !$isStaff) {
    session_destroy();
    header('Location: auth/flashscreen.php');
    exit;
}

$userName  = $userRow['user_name'] ?? '';
$userPhone = $userRow['phone'] ?? '';
$isAdmin   = $isStaff;

$orderId    = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$order      = null;
$items      = [];
$errorMsg   = '';
$orderTime  = '';

if ($orderId <= 0) {
    $errorMsg = 'Invalid order.';
} else {
    // Get employee branch for staff members
    $employeeBranchId = 0;
    if ($employeeId > 0) {
        $empStmt = $connect->prepare("SELECT branch_id FROM employees WHERE id = ?");
        $empStmt->bind_param("i", $employeeId);
        $empStmt->execute();
        $empResult = $empStmt->get_result()->fetch_assoc();
        $empStmt->close();
        if ($empResult) {
            $employeeBranchId = (int) $empResult['branch_id'];
        }
        error_log('[Order Popup] Employee branch_id: ' . $employeeBranchId);
    }

    if ($isAdmin && $employeeBranchId > 0) {
        // Staff member with branch assignment - check branch ownership
        $stmt = $connect->prepare("SELECT * FROM orders WHERE id = ? AND branch_id = ?");
        $stmt->bind_param("ii", $orderId, $employeeBranchId);
    } else if ($isAdmin) {
        // Super admin (branch_id = 0) - can see all orders
        $stmt = $connect->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
    } else {
        // Regular user - ownership check by user_name
        $stmt = $connect->prepare("SELECT * FROM orders WHERE id = ? AND user_name = ?");
        $stmt->bind_param("is", $orderId, $userName);
    }
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $errorMsg = 'Order not found.';
        error_log('[Order Popup] Order not found for order_id: ' . $orderId);
    } else {
        if ($userPhone === '' && !empty($order['user_name'])) {
            $phoneStmt = $connect->prepare("SELECT phone FROM users WHERE user_name = ? LIMIT 1");
            $phoneStmt->bind_param("s", $order['user_name']);
            $phoneStmt->execute();
            $phoneRow = $phoneStmt->get_result()->fetch_assoc();
            $phoneStmt->close();
            $userPhone = $phoneRow['phone'] ?? '';
        }

        $itemStmt = $connect->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();
    }
}

// ── Presentation lookups ────────────────────────────────────────
$statusMap = [
    'pending'   => ['label' => 'Not Confirmed', 'class' => 'badge-pending',   'icon' => 'fa-circle-exclamation', 'msg' => 'The order is waiting for confirmation'],
    'confirmed' => ['label' => 'Confirmed',      'class' => 'badge-confirmed', 'icon' => 'fa-circle-check',       'msg' => 'The order has been confirmed'],
    'preparing' => ['label' => 'Preparing',      'class' => 'badge-preparing', 'icon' => 'fa-mug-hot',            'msg' => 'The order is being prepared'],
    'ready'     => ['label' => 'Ready',          'class' => 'badge-ready',     'icon' => 'fa-bell',               'msg' => 'The order is ready'],
    'delivered' => ['label' => 'Delivered',      'class' => 'badge-delivered', 'icon' => 'fa-truck',              'msg' => 'The order has been delivered'],
    'completed' => ['label' => 'Completed',      'class' => 'badge-completed', 'icon' => 'fa-circle-check',       'msg' => 'The order is complete. Enjoy!'],
    'cancelled' => ['label' => 'Order Cancelled',      'class' => 'badge-cancelled', 'icon' => 'fa-circle-xmark',       'msg' => 'This order was cancelled'],
];

$orderTypeLabels = [
    'dine-in'  => 'Dine-in',
    'takeout'  => 'Takeout',
    'delivery' => 'Delivery',
    'pickup'   => 'Pickup',
];

$paymentLabels = [
    'cod'   => 'Cash on Delivery',
    'qrph'  => 'QRPh',
];

$addressLabel = 'Location';
$orderTypeL   = '';
$paymentL     = '';
$status       = $statusMap['pending'];
$orderNumber  = '';

if ($order) {
    $status       = $statusMap[$order['status']] ?? $statusMap['pending'];
    $orderTypeKey = $order['order_type'] ?? 'dine-in';
    $orderTypeL   = $orderTypeLabels[$orderTypeKey] ?? ucfirst($orderTypeKey);
    $paymentL     = $paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method']);
    $addressLabel = $orderTypeKey === 'delivery'
        ? 'Delivery Address'
        : (in_array($orderTypeKey, ['takeout', 'pickup'], true) ? 'Pickup Branch' : 'Location');
    $orderNumber  = 'ONL - ' . date('Y', strtotime($order['created_at'])) . '-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
    $orderTime    = date('g:i a', strtotime($order['created_at']));
}

$backUrl  = $isStaff ? 'dashboard/pos-online.php' : 'menu.php';
$trackUrl = $isStaff ? ('dashboard/pos-status.php?order_id=' . $orderId) : ('status.php?order_id=' . $orderId);
$backButtonLabel = $isStaff ? 'Back to Online Orders' : 'Back to Menu';
$isQrphUnpaid = $order
    && strtolower((string) ($order['payment_method'] ?? '')) === 'qrph'
    && strtolower((string) ($order['payment_status'] ?? '')) !== 'paid';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="order-popup.css">
    <title>Order Confirmation - BoyCold</title>
</head>
<body>

    <?php if ($errorMsg): ?>

        <div class="order-card">
            <div class="order-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <p><?= htmlspecialchars($errorMsg) ?></p>
                <button type="button" class="btn btn-confirm btn-single" onclick="closePopupOrGo('<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>')"><?= htmlspecialchars($backButtonLabel) ?></button>
            </div>
        </div>

    <?php else: ?>

        <div class="order-card">

            <!-- Top tag row -->
            <div class="top-row">
                <span class="tag-new">New Order</span>
                <div class="top-time">
                    <span><?= htmlspecialchars($orderTime) ?></span>
                    <button type="button" class="popup-close" onclick="closePopupOrGo('<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>')" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Order No. + status -->
            <div class="order-header">
                <div>
                    <div class="order-no-label">Order No.</div>
                    <div class="order-title"><?= htmlspecialchars($orderNumber) ?></div>
                </div>
                <div class="order-status-wrap">
                    <span class="order-badge <?= $status['class'] ?>">
                        <i class="fa-solid <?= $status['icon'] ?>"></i> <?= htmlspecialchars($status['label']) ?>
                    </span>
                    <span class="order-status-msg"><?= htmlspecialchars($status['msg']) ?></span>
                </div>
            </div>

            <hr class="divider">

            <!-- Customer -->
            <div class="info-block">
                <div class="info-icon-row">
                    <div class="info-icon"><i class="fa-solid fa-user"></i></div>
                    <div class="info-icon-text">
                        <div class="name"><?= htmlspecialchars($order['user_name']) ?></div>
                        <?php if ($userPhone): ?>
                            <div class="sub"><?= htmlspecialchars($userPhone) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-kv">
                    <div class="info-kv-row">
                        <span class="info-kv-label">Order Type</span>
                        <span class="info-kv-value"><?= htmlspecialchars($orderTypeL) ?></span>
                    </div>
                    <div class="info-kv-row">
                        <span class="info-kv-label">Payment Method</span>
                        <span class="info-kv-value"><?= htmlspecialchars($paymentL) ?></span>
                    </div>
                    <div class="info-kv-row">
                        <span class="info-kv-label">Payment Status</span>
                        <span class="info-kv-value"><?= htmlspecialchars(strtoupper((string) ($order['payment_status'] ?? 'unpaid'))) ?></span>
                    </div>
                </div>
            </div>

            <!-- Address / Time / Total -->
            <div class="info-block">
                <div class="info-icon-row">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-icon-text">
                        <?php if (!empty($order['address'])): ?>
                            <div class="sub"><?= nl2br(htmlspecialchars($order['address'])) ?></div>
                        <?php else: ?>
                            <div class="sub"><?= htmlspecialchars($addressLabel) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-kv">
                    <div class="info-kv-row">
                        <span class="info-kv-label">Order Time</span>
                        <span class="info-kv-value"><?= htmlspecialchars($orderTime) ?></span>
                    </div>
                    <div class="info-kv-row">
                        <span class="info-kv-label">Total Amount</span>
                        <span class="info-kv-value">₱<?= number_format((float) $order['total'], 2) ?></span>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <!-- Items -->
            <div class="items-section">
                <div class="items-header">Order Items</div>

                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div class="item-icon">
                            <?php if (!empty($item['product_image'])): ?>
                                <?php $imagePath = $item['product_image']; ?>
                                <?php if (strpos($imagePath, '../') === 0): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>"
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <?php else: ?>
                                    <img src="../<?= htmlspecialchars(ltrim($imagePath, '/')) ?>"
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <?php endif; ?>
                                <i class="fa-solid fa-mug-hot" style="display:none;"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-mug-hot"></i>
                            <?php endif; ?>
                        </div>
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="item-qty"><?= (int) $item['quantity'] ?></span>
                        <span class="item-price">₱<?= number_format((float) $item['line_total'], 2) ?></span>
                    </div>

                    <?php if (!empty($item['milk'])): ?>
                        <div class="item-row">
                            <div class="item-icon-spacer"></div>
                            <span class="item-name item-sub">&bull; <?= htmlspecialchars($item['milk']) ?></span>
                            <span class="item-qty"></span>
                            <span class="item-price item-sub"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['addons'])): ?>
                        <?php foreach (array_filter(array_map('trim', explode(',', $item['addons']))) as $addon): ?>
                            <div class="item-row">
                                <div class="item-icon-spacer"></div>
                                <span class="item-name item-sub">&bull; <?= htmlspecialchars($addon) ?></span>
                                <span class="item-qty"></span>
                                <span class="item-price item-sub"></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <hr class="divider">

            <!-- Totals -->
            <div class="totals-section">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>₱<?= number_format((float) $order['subtotal'], 2) ?></span>
                </div>
                <?php if ((float) $order['delivery_fee'] > 0): ?>
                <div class="total-row">
                    <span>Delivery Fee</span>
                    <span>₱<?= number_format((float) $order['delivery_fee'], 2) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float) $order['tax'] > 0): ?>
                <div class="total-row">
                    <span>Tax</span>
                    <span>₱<?= number_format((float) $order['tax'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row grand-total">
                    <span>Total Amount</span>
                    <span>₱<?= number_format((float) $order['total'], 2) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="actions">
                <?php if ($order['status'] === 'pending' && $isQrphUnpaid): ?>
                    <button type="button" class="btn" id="declineBtn" onclick="declineOrder(<?= $orderId ?>)">Cancel Order</button>
                    <button type="button" class="btn btn-confirm" id="confirmPaymentBtn" disabled>Confirm Payment</button>
                <?php elseif ($order['status'] === 'pending'): ?>
                    <button type="button" class="btn" id="declineBtn" onclick="declineOrder(<?= $orderId ?>)">Decline Order</button>
                    <button type="button" class="btn btn-confirm" id="acceptBtn" onclick="acceptOrder(<?= $orderId ?>)">Confirm Order</button>
                <?php else: ?>
                    <button type="button" class="btn btn-confirm btn-single" onclick="goTop('<?= htmlspecialchars($trackUrl, ENT_QUOTES) ?>')">Track Order</button>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

    <script>
        function isEmbeddedPopup() {
            return window.top && window.top !== window;
        }

        function closePopupOrGo(fallbackUrl) {
            if (isEmbeddedPopup()) {
                window.top.postMessage({ type: 'closeOrderPopup' }, '*');
                return;
            }

            goTop(fallbackUrl);
        }

        // Navigates the top-level window for standalone popup pages.
        function goTop(url) {
            window.top.location.href = new URL(url, window.location.href).href;
        }

        // "Decline Order" cancels the customer's own still-pending order
        // (same permission model as status.php's cancel button) and
        // reloads this card in place to reflect the new status.
        async function declineOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order?')) return;

            const btn = document.getElementById('declineBtn');
            btn.disabled = true;
            btn.textContent = 'Cancelling…';

            try {
                const res  = await fetch('../api/orders_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'cancel', order_id: orderId })
                });
                const data = await res.json();
                if (data.success) {
                    if (isEmbeddedPopup()) {
                        window.top.postMessage({ type: 'orderCancelled', orderId }, '*');
                        return;
                    }
                    location.reload();
                } else {
                    alert(data.error || 'Could not cancel this order.');
                    btn.disabled = false;
                    btn.textContent = 'Cancel Order';
                }
            } catch (err) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Cancel Order';
            }
        }

        async function acceptOrder(orderId) {
            const btn = document.getElementById('acceptBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Confirming…';
            }

            try {
                const res = await fetch('../api/orders_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_status', order_id: orderId, status: 'confirmed' })
                });
                const data = await res.json();
                if (data.success) {
                    if (isEmbeddedPopup()) {
                        // Auto-popup case (order-notify.js): let the parent page
                        // close this popup and refresh the online orders table
                        // in place. Don't hijack whatever POS page staff is on.
                        window.top.postMessage({ type: 'orderAccepted', orderId }, '*');
                    } else {
                        // Standalone page (opened directly, not embedded) —
                        // send staff back to the online orders list, customers
                        // back to the menu. Same destination as the close (X)
                        // button uses.
                        goTop('<?= addslashes($backUrl) ?>');
                    }
                } else {
                    alert(data.error || 'Could not confirm this order.');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Confirm Order';
                    }
                }
            } catch (err) {
                alert('Network error. Please try again.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Confirm Order';
                }
            }
        }

        <?php if ($isStaff && $order && $isQrphUnpaid && $order['status'] === 'pending'): ?>
        (function monitorQrphPayment() {
            const confirmButton = document.getElementById('confirmPaymentBtn');
            const cancelButton = document.getElementById('declineBtn');
            const expiresAt = <?= json_encode($order['payment_expires_at'] ?? '') ?>;
            const pollUrl = `online-orders-api.php?action=payment_status&order_id=<?= $orderId ?>`;
            let paymentPoll;
            let expiryTimer;
            let confirming = false;

            function showExpired() {
                if (paymentPoll) clearTimeout(paymentPoll);
                if (expiryTimer) clearTimeout(expiryTimer);
                if (cancelButton) cancelButton.disabled = true;
                if (confirmButton) {
                    confirmButton.disabled = true;
                    confirmButton.textContent = 'QRPh Payment Expired';
                }
            }

            async function checkPayment() {
                try {
                    const response = await fetch(pollUrl, { cache: 'no-store' });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Payment status unavailable');

                    if (result.payment_status === 'paid' && !confirming) {
                        confirming = true;
                        if (confirmButton) {
                            confirmButton.disabled = false;
                            confirmButton.textContent = 'Payment Confirmed';
                        }
                        await acceptOrder(<?= $orderId ?>);
                        return;
                    }

                    if (['expired', 'failed', 'cancelled'].includes(result.payment_status) || result.order_status === 'cancelled') {
                        showExpired();
                        return;
                    }
                } catch (error) {
                    console.error('QRPh payment status check failed:', error);
                }
                paymentPoll = setTimeout(checkPayment, 3000);
            }

            if (expiresAt) {
                const remaining = new Date(expiresAt.replace(' ', 'T')).getTime() - Date.now();
                expiryTimer = setTimeout(showExpired, Math.max(0, remaining));
            }

            confirmButton?.addEventListener('click', () => {
                if (!confirming) checkPayment();
            });
            checkPayment();
        })();
        <?php endif; ?>
    </script>
</body>
</html>
