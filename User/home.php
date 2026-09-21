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

$reviews = [];
if ($reviewTable = $connect->query("SHOW TABLES LIKE 'order_reviews'")) {
    if ($reviewTable->num_rows > 0) {
        $reviewQuery = $connect->query("SELECT r.rating, r.review, r.created_at,
                                               CONCAT(u.firstname, ' ', u.lastname) AS customer_name
                                        FROM order_reviews r
                                        INNER JOIN users u ON u.id = r.user_id
                                        ORDER BY r.created_at DESC, r.id DESC
                                        LIMIT 12");
        if ($reviewQuery) {
            while ($row = $reviewQuery->fetch_assoc()) $reviews[] = $row;
        }
    }
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidebar-responsive.css">
    <link rel="stylesheet" href="css/free-drink-nav.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold Café</title>
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
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li><a href="cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
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
                <li><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../store/store.php">STORES</a></li>
            </ul>
        </div>

        <!-- CENTER: logo -->
        <div class="logo">
            <img src="../picture/LOGO.png" alt="BoyCold logo">
        </div>

        <!-- RIGHT: avatar with dropdown -->
        <div class="nav-right-group">
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
        <div class="background">
            <div class="box">
                <p>Ready to sweeten your day?</p>
                <button class="btn" onclick="location.href='menu.php'">Start an order</button>
            </div>
            <h1>BOYCOLD<br>CAFE</h1>
            <div class="tag-line">
                <p>
                    Fresh brews, cozy vibes, and pastries to sweeten your day.
                    <span>At BoyCold Cafe, we believe great coffee is best enjoyed at your own pace.</span>
                    <span>Whether you're starting your morning, taking a break, or winding down,</span>
                    our space is made for comfort, connection, and calm.
                </p>
            </div>
        </div>
    </header>

    <section>
        <div class="hero-section">
            <div class="top-rectangle">
                <div class="top-content">
                    <h2>Made especially for banana pudding lovers.</h2>
                    <p>Show off your love for this creamy classic with a treat inspired by layers of sweet bananas, smooth pudding, and comforting homemade goodness.</p>
                    <button class="hero-btn" onclick="location.href='menu.php'">View menu</button>
                </div>
            </div>
            <div class="mid-rectangle">
                <img src="../picture/Layer 2 1.png" alt="">
                <img class="img2" src="../picture/dasdasd 1.png" alt="">
            </div>
            <div class="bottom-rectangle">
                <img src="../picture/Rectangle 24.png" alt="">
                <div class="bottom-content">
                    <h2>Your favorites just got even better.</h2>
                    <p>Introducing our 4 signature pasta flavors and our Mango Sticky Rice with 3 delightful new twists. Comfort food made the BoyCold way, crafted to satisfy every craving.</p>
                    <button class="hero-btn" onclick="location.href='../store/store.php'">Find a store</button>
                </div>
            </div>
        </div>
    </section>

    <section class="customer-reviews" aria-labelledby="customerReviewsTitle">
        <div class="customer-reviews-heading">
            <h2 id="customerReviewsTitle">What Our Customers Say</h2>
            <p>Feedback from completed orders</p>
        </div>
        <?php if ($reviews): ?>
            <div class="customer-reviews-carousel">
                <button type="button" class="customer-reviews-nav customer-reviews-prev" aria-label="Previous reviews">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>
                <div class="customer-reviews-grid">
                    <div class="customer-reviews-track">
                    <?php foreach ($reviews as $review): ?>
                        <article class="customer-review-card">
                            <div class="customer-review-stars" aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?></div>
                            <p><?= $review['review'] !== '' ? nl2br(htmlspecialchars($review['review'])) : '<em>No written comment.</em>' ?></p>
                            <strong><?= htmlspecialchars($review['customer_name']) ?></strong>
                            <small><?= htmlspecialchars(date('M d, Y', strtotime($review['created_at']))) ?></small>
                        </article>
                    <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="customer-reviews-nav customer-reviews-next" aria-label="Next reviews">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        <?php else: ?>
            <p class="customer-reviews-empty">Customer feedback will appear here after completed orders are reviewed.</p>
        <?php endif; ?>
    </section>

    <script>
        (() => {
            const carousel = document.querySelector('.customer-reviews-carousel');
            if (!carousel) return;
            const grid = carousel.querySelector('.customer-reviews-grid');
            const track = carousel.querySelector('.customer-reviews-track');
            const cards = [...track.querySelectorAll('.customer-review-card')];
            const previous = carousel.querySelector('.customer-reviews-prev');
            const next = carousel.querySelector('.customer-reviews-next');
            let currentIndex = 5;
            let desktopReady = false;

            function isResponsive() {
                return window.matchMedia('(max-width: 700px)').matches;
            }

            function cardStep() {
                const card = track.querySelector('.customer-review-card');
                if (!card) return 0;
                return card.getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap || '0');
            }

            function sizeDesktopCards() {
                const gap = parseFloat(getComputedStyle(track).gap || '0');
                const width = (grid.clientWidth - gap * 4) / 5;
                cards.forEach((card) => { card.style.flex = `0 0 ${width}px`; });
                track.querySelectorAll('.customer-review-card').forEach((card) => { card.style.flex = `0 0 ${width}px`; });
            }

            function moveTrack(animate = true) {
                track.style.transition = animate ? 'transform 360ms ease' : 'none';
                track.style.transform = `translateX(-${currentIndex * cardStep()}px)`;
            }

            function setupDesktop() {
                if (desktopReady || isResponsive()) return;
                const cloneCount = Math.min(5, cards.length);
                sizeDesktopCards();
                const firstClones = cards.slice(0, cloneCount).map((card) => card.cloneNode(true));
                const lastClones = cards.slice(-cloneCount).map((card) => card.cloneNode(true));
                track.prepend(...lastClones);
                track.append(...firstClones);
                currentIndex = cloneCount;
                cards.forEach((card) => { card.hidden = false; });
                desktopReady = true;
                requestAnimationFrame(() => moveTrack(false));
            }

            function render() {
                if (isResponsive()) {
                    if (desktopReady) {
                        track.innerHTML = '';
                        cards.forEach((card) => track.appendChild(card));
                        desktopReady = false;
                    }
                    track.style.transform = '';
                    track.style.transition = '';
                    cards.forEach((card) => { card.style.flex = ''; });
                    cards.forEach((card) => { card.hidden = false; });
                    return;
                }
                setupDesktop();
            }

            previous.addEventListener('click', () => { if (!isResponsive()) { currentIndex--; moveTrack(); } });
            next.addEventListener('click', () => { if (!isResponsive()) { currentIndex++; moveTrack(); } });
            track.addEventListener('transitionend', () => {
                const cloneCount = Math.min(5, cards.length);
                if (currentIndex >= cards.length + cloneCount) {
                    currentIndex = cloneCount;
                    moveTrack(false);
                } else if (currentIndex < cloneCount) {
                    currentIndex = cards.length + cloneCount - 1;
                    moveTrack(false);
                }
            });
            window.addEventListener('resize', () => { render(); if (!isResponsive()) { sizeDesktopCards(); moveTrack(false); } });
            render();
        })();
    </script>

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

    <script src="../scr/account.js"></script>

    <!-- FREE DRINK MODAL -->
    <div class="freedrink-overlay" id="freeDrinkOverlay" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(20,12,6,0.55);align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity 0.25s ease;">
        <div class="freedrink-modal" style="position:relative;width:100%;max-width:340px;background:#fff;border-radius:20px;padding:28px 24px;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,0.3);transform:scale(0.95);transition:transform 0.25s ease;">
            <button class="freedrink-close" id="freeDrinkClose" style="position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:50%;background:#f5f0eb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#692727;font-size:16px;transition:background 0.15s;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="freedrink-img" style="width:120px;height:120px;margin:0 auto 20px;border-radius:16px;overflow:hidden;background:#f5f0eb;display:flex;align-items:center;justify-content:center;">
                <img src="../picture/icon2.png" alt="Free drink" style="width:100%;height:100%;object-fit:contain;">
            </div>
            <h2 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1e1e1e;font-family:'Afacad',sans-serif;">Free Drink Reward</h2>
            <p style="margin:0 0 4px;font-size:15px;color:#777;font-family:'Afacad',sans-serif;">You've completed your loyalty card.</p>
            <p style="margin:0 0 20px;font-size:15px;color:#777;font-family:'Afacad',sans-serif;">Redeem it by having your loyalty QR code scanned at any Boycold Cafe branch POS.</p>
            <button class="freedrink-btn" id="freeDrinkViewBtn" style="padding:12px 24px;background:#692727;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:'Afacad',sans-serif;cursor:pointer;transition:background 0.15s;">Got it</button>
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
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeFreeDrinkModal();
        });

        <?php if ($showLoyaltyPopupOnLoad): ?>
        openFreeDrinkModal();
        <?php endif; ?>
    </script>
    <script src="../scr/free-drink-nav.js"></script>
</body>

</html>
