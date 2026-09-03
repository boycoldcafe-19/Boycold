<?php
require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once '../config/db_config.php';
$posEmployee = pos_require_employee($connect);

// Session guard - POS staff only
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Scanner - BoyCold POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a1a;
            color: #fff;
            min-height: 100vh;
        }
        .scanner-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #D4A017;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .header p {
            color: #888;
            font-size: 0.9rem;
        }
        .scanner-box {
            background: #2a2a2a;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #D4A017;
            font-weight: 600;
        }
        .input-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #444;
            border-radius: 8px;
            background: #1a1a1a;
            color: #fff;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .input-group input:focus {
            outline: none;
            border-color: #D4A017;
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #D4A017;
            color: #1a1a1a;
        }
        .btn-primary:hover {
            background: #e5b52a;
        }
        .btn-secondary {
            background: #444;
            color: #fff;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #555;
        }
        .customer-info {
            background: #2a2a2a;
            border-radius: 15px;
            padding: 30px;
            display: none;
        }
        .customer-info.active {
            display: block;
        }
        .customer-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #444;
        }
        .customer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #D4A017;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }
        .customer-name {
            font-size: 1.3rem;
            font-weight: 600;
        }
        .customer-card {
            color: #888;
            font-size: 0.9rem;
        }
        .loyalty-card {
            background: linear-gradient(135deg, #D4A017 0%, #b8860b 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        .loyalty-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .loyalty-title {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .loyalty-stamps {
            font-size: 2rem;
            font-weight: 700;
        }
        .loyalty-progress {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            height: 20px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .loyalty-progress-bar {
            height: 100%;
            background: #1a1a1a;
            transition: width 0.5s ease;
        }
        .loyalty-status {
            font-size: 0.9rem;
            font-weight: 600;
        }
        .reward-badge {
            background: #1a1a1a;
            color: #D4A017;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            margin-top: 10px;
        }
        .reward-badge.hidden {
            display: none;
        }
        .error-message {
            background: #ff4444;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .error-message.active {
            display: block;
        }
        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }
        .loading.active {
            display: block;
        }
        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .back-link {
            display: inline-block;
            color: #888;
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #D4A017;
        }
    </style>
</head>
<body>
    <div class="scanner-container">
        <a href="dashboard/pos-status.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to POS
        </a>

        <div class="header">
            <h1><i class="fa-solid fa-qrcode"></i> Loyalty Scanner</h1>
            <p>Scan customer QR code to view loyalty information</p>
        </div>

        <div class="scanner-box">
            <div class="input-group">
                <label for="tokenInput">Enter Loyalty Token or Scan QR Code</label>
                <input type="text" id="tokenInput" placeholder="Paste token or scan QR code..." autocomplete="off">
            </div>
            <button class="btn btn-primary" onclick="scanLoyalty()">
                <i class="fa-solid fa-search"></i> Look Up Customer
            </button>
            <button class="btn btn-secondary" onclick="clearForm()">
                <i class="fa-solid fa-eraser"></i> Clear
            </button>
        </div>

        <div class="error-message" id="errorMessage"></div>
        <div class="loading" id="loading">
            <i class="fa-solid fa-spinner"></i>
            <p>Looking up customer...</p>
        </div>

        <div class="customer-info" id="customerInfo">
            <div class="customer-header">
                <div class="customer-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <div class="customer-name" id="customerName"></div>
                    <div class="customer-card">Card: <span id="customerCardNo"></span></div>
                </div>
            </div>

            <div class="loyalty-card">
                <div class="loyalty-header">
                    <div class="loyalty-title">Loyalty Progress</div>
                    <div class="loyalty-stamps"><span id="stampsCount">0</span> / <span id="maxStamps">10</span></div>
                </div>
                <div class="loyalty-progress">
                    <div class="loyalty-progress-bar" id="progressBar" style="width: 0%"></div>
                </div>
                <div class="loyalty-status" id="loyaltyStatus"></div>
                <div class="reward-badge" id="rewardBadge">
                    <i class="fa-solid fa-gift"></i> Reward Available!
                </div>
            </div>

            <div style="text-align: center; color: #888; font-size: 0.85rem;">
                <p>Total Stamps Earned: <span id="totalStamps">0</span></p>
                <p>Remaining: <span id="remainingStamps">10</span></p>
            </div>
        </div>
    </div>

    <script>
        function scanLoyalty() {
            const token = document.getElementById('tokenInput').value.trim();
            const errorMessage = document.getElementById('errorMessage');
            const loading = document.getElementById('loading');
            const customerInfo = document.getElementById('customerInfo');

            if (token === '') {
                showError('Please enter a loyalty token or scan a QR code');
                return;
            }

            // Hide previous results
            errorMessage.classList.remove('active');
            customerInfo.classList.remove('active');
            loading.classList.add('active');

            // Extract token from URL if full URL was pasted
            let tokenToUse = token;
            if (token.includes('?t=')) {
                const urlParams = new URLSearchParams(token.split('?')[1]);
                tokenToUse = urlParams.get('t') || token;
            }

            // Call the scan endpoint
            fetch(`../loyalty/scan.php?t=${encodeURIComponent(tokenToUse)}`)
                .then(response => response.json())
                .then(data => {
                    loading.classList.remove('active');

                    if (data.success) {
                        displayCustomer(data);
                    } else {
                        showError(data.error || 'Failed to look up customer');
                    }
                })
                .catch(error => {
                    loading.classList.remove('active');
                    showError('Network error. Please try again.');
                    console.error('Error:', error);
                });
        }

        function displayCustomer(data) {
            const customerInfo = document.getElementById('customerInfo');
            const customer = data.customer;
            const loyalty = data.loyalty;

            document.getElementById('customerName').textContent = customer.name;
            document.getElementById('customerCardNo').textContent = customer.card_no;
            document.getElementById('stampsCount').textContent = loyalty.stamps;
            document.getElementById('maxStamps').textContent = loyalty.max_stamps;
            document.getElementById('totalStamps').textContent = loyalty.total_stamps;
            document.getElementById('remainingStamps').textContent = loyalty.remaining;
            document.getElementById('loyaltyStatus').textContent = loyalty.reward_status;

            // Update progress bar
            const progressPercent = (loyalty.stamps / loyalty.max_stamps) * 100;
            document.getElementById('progressBar').style.width = progressPercent + '%';

            // Show/hide reward badge
            const rewardBadge = document.getElementById('rewardBadge');
            if (loyalty.reward_available) {
                rewardBadge.classList.remove('hidden');
            } else {
                rewardBadge.classList.add('hidden');
            }

            customerInfo.classList.add('active');
        }

        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorMessage.classList.add('active');
        }

        function clearForm() {
            document.getElementById('tokenInput').value = '';
            document.getElementById('errorMessage').classList.remove('active');
            document.getElementById('customerInfo').classList.remove('active');
            document.getElementById('loading').classList.remove('active');
        }

        // Allow Enter key to submit
        document.getElementById('tokenInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                scanLoyalty();
            }
        });
    </script>
</body>
</html>
