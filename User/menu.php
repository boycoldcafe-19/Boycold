<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/inventory_service.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Fetch user data for navbar
$stmt = $connect->prepare("SELECT firstname, lastname, email, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar'] ?? '';

// Set default branch if not set
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 1; // Default to Baliuag
}

// Fetch all products from DB
boycold_ensure_inventory_schema($connect);
$branchId = (int) $_SESSION['branch_id'];
$productsResult = $connect->query("SELECT id, product_name, price, image, category FROM products WHERE is_available = 1 ORDER BY category, product_name");
$productsList = [];
while ($productsResult && ($row = $productsResult->fetch_assoc())) {
    $productsList[] = $row;
}
$productAvailability = boycold_get_product_inventory_availability($connect, $branchId, array_column($productsList, 'product_name'));
?>


<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/sidebar-responsive.css">
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
        <div class="box">
            <ul>
                <li><a href="#" data-filter="popular" class="active">Popular</a></li>
                <li><a href="#" data-filter="coffee">Coffee</a></li>
                <li><a href="#" data-filter="non-coffee">Non-Coffee</a></li>
                <li><a href="#" data-filter="matcha-fusion">Matcha Fusion</a></li>
                <li><a href="#" data-filter="smoothie">Smoothie</a></li>
                <li><a href="#" data-filter="frappe-series">Frappe Series</a></li>
                <li><a href="#" data-filter="rice-meal">Rice Meal</a></li>
                <li><a href="#" data-filter="light-snack">Light Snack</a></li>
                <li><a href="#" data-filter="pasta">Pasta</a></li>
                <li><a href="#" data-filter="waffles">Waffles</a></li>
                <li><a href="#" data-filter="quesadilla">Quesadilla</a></li>
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
                    $popularProducts = ['americano', 'cafe latte', 'spanish latte', 'sea salt latte', 'french vanilla', 'white mocha', 'mont blanc', 'horchata', 'ocean mist', 'cheesecake latte', 'dark mocha', 'biscoff creamy latte', 'caramel macchiato', 'salted caramel', 'einspanner latte', 'strawberry milk', 'blueberry milk', 'milky oreo', 'choco berry', 'choco banana pudding', 'choco vanilla cookie', 'strawberry matcha', 'matcha banana pudding', 'biscoff matcha', 'mango matcha', 'sea salt matcha', 'matcha freddo', 'matcha latte', 'ube matcha', 'cheesecake matcha', 'strawberry smoothie', 'berry mango', 'tropical matcha yogurt', 'ube yogurt', 'blueberry', 'mango graham', 'hershey delight', 'ube frappe', 'oreo frappe', 'matcha frappe', 'java chips', 'cheesecake frappe', 'black forrest', 'biscoff frappe', 'honey gochujang katsu', 'dak galbi', 'salted egg fish fillet', 'cheezy fries', 'fries and chicken tenders', 'onion rings', 'nachos', 'chicken alfredo', 'chicken pesto', 'aglio olio', 'carbonara', 'lolly waffle biscoff', 'lolly waffle chocolate', 'lolly waffle matcha', 'lolly waffle strawberry', 'lolly waffle oreo', 'lolly waffle tiramisu', 'chicken quesadilla', 'beef quesadilla']; // Add more popular product names here (lowercase)
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
                            $categoryValue = match ($categoryValue) {
                                'bites' => 'light-snack',
                                'waffle' => 'waffles',
                                default => $categoryValue,
                            };
                            $category = htmlspecialchars($categoryValue);

                            // Normalize for comparison: trim whitespace, lowercase
                            $normalizedName = strtolower(trim($product['product_name']));
                            $isPopular = in_array($normalizedName, $popularProducts, true);

                            // Add "popular" to category if product is in popular list
                            $dataCategory = $category;
                            if ($isPopular) {
                                $dataCategory .= ' popular';
                            }
                    ?>

                            <div class="product-card"
                                data-category="<?= $dataCategory ?>"
                                data-id="<?= strtolower(str_replace(' ', '-', $name)) ?>"
                                data-product-id="<?= $id ?>"
                                data-product-name="<?= $name ?>"
                                data-price="<?= $price ?>"
                                data-image="<?= $image ?>"
                                data-can-order="<?= $canOrder ? '1' : '0' ?>"
                                data-stock-status="<?= $stockStatus ?>"
                                data-available-servings="<?= $servings ?>"
                                data-stock-reason="<?= $stockReason ?>">
                                <div class="card-image">
                                    <div class="card-image-placeholder">
                                        <div class="card-top">
                                            <?php if ($isPopular): ?>
                                                <span class="card-badge">Popular<i class="fa-solid fa-star"></i></span>
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
</body>

</html>
