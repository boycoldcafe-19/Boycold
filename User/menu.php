<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/inventory_service.php';
require_once '../config/menu_catalog_service.php';
require_once '../config/loyalty.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Fetch user data for navbar
$stmt = $connect->prepare("SELECT firstname, lastname, email, avatar, loyalty_stamps FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar'] ?? '';
$loyaltyStamps = (int) ($user['loyalty_stamps'] ?? 0);
$loyaltyMaxStamps = 10;
$isLoyaltyCardComplete = $loyaltyStamps >= $loyaltyMaxStamps;
$isFreeDrinkMode = isset($_GET['free_drink']) && $_GET['free_drink'] === '1';

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

// Set default branch if not set
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 1; // Default to Baliuag
}

// Fetch all products from DB
boycold_ensure_inventory_schema($connect);
boycold_ensure_product_addons_schema($connect);
$branchId = (int) $_SESSION['branch_id'];
$productsResult = $connect->query("SELECT id, product_name, price, image, category, popular_category, addons_configured FROM products WHERE is_available = 1 ORDER BY category, product_name");
$productsList = [];
while ($productsResult && ($row = $productsResult->fetch_assoc())) {
    $productsList[] = $row;
}
$productAddons = boycold_menu_get_product_addons($connect, array_column($productsList, 'id'));
$productAvailability = boycold_get_product_inventory_availability($connect, $branchId, array_column($productsList, 'product_name'));
$menuCategories = boycold_menu_get_categories($connect);
?>


<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/sidebar-responsive.css">
    <link rel="stylesheet" href="css/free-drink-nav.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Menu</title>
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
                <li><a <?= $isFreeDrinkMode ? 'class="cart-link is-disabled" aria-disabled="true" tabindex="-1"' : 'href="cart.php" class="cart-link"' ?>>
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
                <span class="sidebar-user-email"><?= $email ?></span>
            </div>
        </div>
    </div>
    <nav id="mainNav">
        <div class="nav-box"></div>
        <div class="nav-left-group">
            <div class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </div>
            <ul class="nav-links">
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php" class="active">MENU</a></li>
                <li><a href="status.php">ORDERS</a></li>
                <li><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../store/store.php">STORES</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="../picture/LOGO.png" alt="BoyCold logo">
        </div>
        <div class="nav-right-group">
            <div class="nav-search" id="navSearch">
                <i class="fa-solid fa-magnifying-glass" id="searchIconBtn" onclick="toggleSearch()"></i>
                <input type="text" placeholder="Search coffee and more">
            </div>
            <a <?= $isFreeDrinkMode ? 'class="cart-link is-disabled" aria-disabled="true" tabindex="-1"' : 'href="cart.php" class="cart-link"' ?>>
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
        <div class="box">
            <ul>
                <li><a href="#" data-filter="popular" class="active">Popular</a></li>
                <?php foreach ($menuCategories as $menuCategory): ?>
                    <li>
                        <a href="#" data-filter="<?= htmlspecialchars($menuCategory['slug'], ENT_QUOTES) ?>">
                            <?= htmlspecialchars($menuCategory['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div>
        <section class="menu-section">
            <div class="menu-content">
                <!-- No search results state -->
                <div id="noSearchResults">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <p class="nsr-title">No results found</p>
                    <p class="nsr-sub">Try searching for something else.</p>
                </div>
                <div class="product-grid" id="productGrid">
                    <?php
                    if (!empty($productsList)) {
                        foreach ($productsList as $product) {
                            $id    = htmlspecialchars($product['id']);
                            $name  = htmlspecialchars($product['product_name']);
                            $price = htmlspecialchars($product['price']);
                            $image = htmlspecialchars($product['image'] ?? '');
                            $availabilityInfo = $productAvailability[boycold_inventory_normalize_name((string) $product['product_name'])] ?? null;
                            $stockStatus = htmlspecialchars((string) ($availabilityInfo['status'] ?? 'unavailable'));
                            $stockLabel = htmlspecialchars((string) ($availabilityInfo['status_label'] ?? 'Unavailable'));
                            $stockReason = htmlspecialchars((string) ($availabilityInfo['reason'] ?? ''));
                            $canOrder = !empty($availabilityInfo['can_order']);
                            $servings = (int) ($availabilityInfo['available_servings'] ?? 0);
                            $categoryValue = strtolower(trim((string) ($product['category'] ?? '')));
                            if ($categoryValue === 'bites') {
                                $categoryValue = 'light-snack';
                            } elseif ($categoryValue === 'waffle') {
                                $categoryValue = 'waffles';
                            }
                            $category = htmlspecialchars($categoryValue);
                            $popularCategory = strtolower(trim((string) ($product['popular_category'] ?? '')));
                            $popularCategory = htmlspecialchars($popularCategory);
                            $addons = $productAddons[(int) $product['id']] ?? [];
                            // Never expose legacy/default choices. A product has
                            // add-ons only when the admin saved actual rows for it.
                            $addonsConfigured = !empty($addons);

                            $dataCategory = $category;
                    ?>

                            <div class="product-card"
                                data-category="<?= $dataCategory ?>"
                                data-popular-category="<?= $popularCategory ?>"
                                data-id="<?= strtolower(str_replace(' ', '-', $name)) ?>"
                                data-product-id="<?= $id ?>"
                                data-product-name="<?= $name ?>"
                                data-price="<?= $price ?>"
                                data-image="<?= $image ?>"
                                data-can-order="<?= $canOrder ? '1' : '0' ?>"
                                data-stock-status="<?= $stockStatus ?>"
                                data-available-servings="<?= $servings ?>"
                                data-stock-reason="<?= $stockReason ?>"
                                data-addons-configured="<?= $addonsConfigured ? '1' : '0' ?>"
                                data-addons="<?= htmlspecialchars(json_encode($addons), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="card-image">
                                    <div class="card-image-placeholder">
                                        <div class="card-top">
                                            <?php if ($popularCategory !== ''): ?>
                                                <span class="card-badge">Popular</span>
                                            <?php else: ?>
                                                <span></span>
                                            <?php endif; ?>
                                            <button class="card-heart"><i class="fa-solid fa-heart"></i></button>
                                        </div>
                                        <?php if (strpos($image, '/public/') === 0): ?>
                                            <img src="<?= $image ?>" alt="<?= $name ?>">
                                        <?php elseif (strpos($image, '../') === 0): ?>
                                            <img src="<?= $image ?>" alt="<?= $name ?>">
                                        <?php else: ?>
                                            <img src="../<?= ltrim($image, '/') ?>" alt="<?= $name ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-info">
                                    <div class="card-mid">
                                        <p class="card-name"><?= $name ?></p>
                                        <p class="card-price">₱<?= number_format($price, 2) ?></p>
                                    </div>
                                    <div class="card-footer">
                                        <div class="menu-stock">
                                            <span class="menu-stock-status <?= $stockStatus ?>">
                                                <span class="status-dot"></span><?= $stockLabel ?>
                                            </span>
                                            <span class="menu-stock-servings"><?= $servings ?> serving<?= $servings === 1 ? '' : 's' ?></span>
                                        </div>
                                        <div class="card-actions">
                                            <button class="card-btn btn-cart" <?= $canOrder ? '' : 'disabled' ?>>
                                                <i class="fa-solid fa-cart-shopping"></i> Cart
                                            </button>
                                            <button class="card-btn btn-order" <?= $canOrder ? '' : 'disabled' ?>>
                                                <i class="fa-solid fa-bag-shopping"></i> Order
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </section>

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
        <script src="../scr/menu.js"></script>
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
            <button class="freedrink-btn" id="freeDrinkViewBtn" style="padding:12px 24px;background:#692727;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:'Afacad',sans-serif;cursor:pointer;transition:background 0.15s;">Claim Free Drinks</button>
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
    <script src="../scr/free-drink-nav.js"></script>
</body>

</html>
