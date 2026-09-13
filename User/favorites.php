<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/loyalty.php';

// Session guard — redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch fresh user data from DB (same pattern as account.php)
$stmt = $connect->prepare("SELECT Firstname, Lastname, user_name, email, avatar, loyalty_stamps FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$fullName  = htmlspecialchars($user['Firstname'] . ' ' . $user['Lastname']);
$userEmail = htmlspecialchars($user['email']);
$avatar    = $user['avatar'] ? htmlspecialchars($user['avatar']) : '';
$userName  = $user['user_name'];
$loyaltyStamps = (int) ($user['loyalty_stamps'] ?? 0);
$loyaltyMaxStamps = 10;
$isLoyaltyCardComplete = $loyaltyStamps >= $loyaltyMaxStamps;

// Check if we should show the free drink modal
if (!isset($_SESSION['loyalty_popup_shown_for_completion'])) {
    $_SESSION['loyalty_popup_shown_for_completion'] = false;
}

$showLoyaltyPopupOnLoad = $isLoyaltyCardComplete
    && (!isset($_SESSION['loyalty_popup_shown_for_completion']) || $_SESSION['loyalty_popup_shown_for_completion'] !== true);

if ($showLoyaltyPopupOnLoad) {
    $_SESSION['loyalty_popup_shown_for_completion'] = true;
}

if (!$isLoyaltyCardComplete) {
    $_SESSION['loyalty_popup_shown_for_completion'] = false;
}

// Keep session in sync
$_SESSION['user_name']  = $user['user_name'];
$_SESSION['user_email'] = $user['email'];
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/favorites.css">
    <link rel="stylesheet" href="css/sidebar-responsive.css">
    <link rel="stylesheet" href="css/free-drink-nav.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Favorites</title>
</head>

<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR DRAWER -->
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="status.php">ORDER</a></li>
                <li><a href="../store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php" class="active">FAVORITES</a></li>
                <li><a href="cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon" style="display:none;"></i>
                    <?php else: ?>
                        <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                    <?php endif; ?>
                </div>
            </a>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= $fullName ?></span>
                <span class="sidebar-user-email"><?= $userEmail ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN NAV -->
    <nav id="mainNav">
        <div class="nav-box"></div>
        <div class="nav-left-group">
            <div class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </div>
            <ul class="nav-links">
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="status.php">ORDERS</a></li>
                <li><a href="favorites.php" class="active">FAVORITES</a></li>
                <li><a href="../store/store.php">STORES</a></li>
            </ul>

        </div>

        <!-- CENTER: logo -->
        <div class="logo">
            <img src="../picture/LOGO.png" alt="BoyCold logo">
        </div>

        <div class="nav-right-group">
            <div class="nav-search" id="navSearch">
                <i class="fa-solid fa-magnifying-glass" id="searchIconBtn" onclick="toggleSearch()"></i>
                <input type="text" placeholder="Search coffee and more">
            </div>
            <a href="cart.php" class="cart-link">
                <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i>
            </a>
            <div class="avatar-dropdown-wrap">
                <div class="sidebar-avatar" id="navAvatarBtn" onclick="toggleAvatarDropdown()">
                    <?php if ($avatar): ?>
                        <img id="navAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
                        <i class="fa-solid fa-user" id="navAvatarIcon" style="display:none;"></i>
                    <?php else: ?>
                        <img id="navAvatarImg" src="" alt="avatar" style="display:none;">
                        <i class="fa-solid fa-user" id="navAvatarIcon"></i>
                    <?php endif; ?>
                </div>
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="account.php"><i class="fa-solid fa-user"></i> Account</a>
                    <hr>
                    <a href="logout.php" class="dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
                </div>
            </div>
        </div>
    </nav>
    <header>
        <div class="background"></div>
        <section class="fav-section">
            <div class="fav-content">

                <div class="fav-header">
                    <div class="fav-header-left">
                        <h1>My Favorites</h1>
                        <p>Your go-to drinks, all in one place</p>
                    </div>
                    <div class="fav-header-right">
                        <select class="fav-sort" id="favSort">
                            <option value="default">Sort by</option>
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                            <option value="name-asc">Name: A–Z</option>
                        </select>
                    </div>
                </div>

                <div class="fav-count" id="favCount">
                    <i class="fa-solid fa-heart" style="color:#e53935;"></i>
                    <span id="favCountNum">0</span> Items
                </div>

                <!-- EMPTY STATE -->
                <div class="fav-empty" id="favEmpty" style="display:none;">
                    <div class="fav-empty-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <p class="fav-empty-title">No favorites yet</p>
                    <p class="fav-empty-desc">Heart any item on the menu and it will appear here.</p>
                    <a href="menu.php" class="fav-empty-cta">Browse Menu</a>
                </div>

                <!-- GRID -->
                <div class="product-grid" id="favGrid"></div>

            </div>
        </section>
    </header>

    <footer>
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="../picture/icon2.png" alt="BoyCold logo">
                    <h1>BOYCOLD CAFE</h1>
                    <p>&copy; <?php echo date("Y"); ?> BoyCold Café. All Rights Reserved.</p>
                </div>
                <div class="footer-links">
                    <ul>
                        <li><a href="../footer-link/about.php">About Us</a></li>
                        <li><a href="../footer-link/compinfo.php">Company Information</a></li>
                        <li><a href="../footer-link/faqs.php">FAQs</a></li>
                        <li><a href="../footer-link/privacy.php">Privacy and Safety</a></li>
                        <li><a href="../footer-link/terms.php">Terms and Conditions</a></li>
                    </ul>
                </div>
            </div>
        </footer>

    <!-- CART TOAST -->
    <div id="cartToast" style="
        display:none; position:fixed; bottom:28px; left:50%; transform:translateX(-50%);
        background:#1e1e1e; color:#fff; padding:12px 24px; border-radius:30px;
        font-family:'Afacad',sans-serif; font-size:15px; font-weight:600;
        box-shadow:0 4px 20px rgba(0,0,0,0.35); z-index:9999; white-space:nowrap;
        align-items:center; gap:10px;">
        <i class="fa-solid fa-check" style="color:#6F4E37;"></i>
        <span id="cartToastMsg">Added to cart!</span>
    </div>

    <!-- FREE DRINK MODAL -->
    <div class="freedrink-overlay" id="freeDrinkOverlay" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(20,12,6,0.55);align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity 0.25s ease;">
        <div class="freedrink-modal" style="position:relative;width:100%;max-width:340px;background:#fff;border-radius:20px;padding:28px 24px;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,0.3);transform:scale(0.95);transition:transform 0.25s ease;">
            <button class="freedrink-close" id="freeDrinkClose" style="position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:50%;background:#f5f0eb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#692727;font-size:16px;transition:background 0.15s;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="freedrink-img" style="width:120px;height:120px;margin:0 auto 20px;border-radius:16px;overflow:hidden;background:#f5f0eb;display:flex;align-items:center;justify-content:center;">
                <img src="../picture/icon2.png" alt="Free drink" style="width:100%;height:100%;object-fit:contain;">
            </div>
            <h2 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1e1e1e;font-family:'Afacad',sans-serif;">Free Drink Ready!</h2>
            <p style="margin:0 0 4px;font-size:15px;color:#777;font-family:'Afacad',sans-serif;">You've completed your loyalty card.</p>
            <p style="margin:0 0 20px;font-size:15px;color:#777;font-family:'Afacad',sans-serif;">You can claim your free drink in any Boycold Cafe branch.</p>
            <button class="freedrink-btn" id="freeDrinkViewBtn" style="padding:12px 24px;background:#692727;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:'Afacad',sans-serif;cursor:pointer;transition:background 0.15s;">View Loyalty Card</button>
        </div>
    </div>

    <script>
        // FREE DRINK MODAL
        const freeDrinkOverlay = document.getElementById('freeDrinkOverlay');
        const freeDrinkClose = document.getElementById('freeDrinkClose');
        const freeDrinkViewBtn = document.getElementById('freeDrinkViewBtn');

        function openFreeDrinkModal() {
            if (!freeDrinkOverlay) return;
            freeDrinkOverlay.style.display = 'flex';
            setTimeout(() => {
                freeDrinkOverlay.style.opacity = '1';
                freeDrinkOverlay.querySelector('.freedrink-modal').style.transform = 'scale(1)';
            }, 10);
        }

        function closeFreeDrinkModal() {
            if (!freeDrinkOverlay) return;
            freeDrinkOverlay.style.opacity = '0';
            freeDrinkOverlay.querySelector('.freedrink-modal').style.transform = 'scale(0.95)';
            setTimeout(() => {
                freeDrinkOverlay.style.display = 'none';
            }, 250);
        }

        if (freeDrinkClose) freeDrinkClose.addEventListener('click', closeFreeDrinkModal);
        if (freeDrinkOverlay) {
            freeDrinkOverlay.addEventListener('click', (e) => {
                if (e.target === freeDrinkOverlay) closeFreeDrinkModal();
            });
        }
        if (freeDrinkViewBtn) {
            freeDrinkViewBtn.addEventListener('click', () => {
                closeFreeDrinkModal();
                window.location.href = 'account.php';
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeFreeDrinkModal();
        });

        <?php if ($showLoyaltyPopupOnLoad): ?>
        openFreeDrinkModal();
        <?php endif; ?>
    </script>

    <script src="../scr/favorites.js"></script>
    <script src="../scr/free-drink-nav.js"></script>
</body>

</html>
