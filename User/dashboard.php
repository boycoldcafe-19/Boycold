<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOYCOLD Café</title>
</head>
<body>
    <h1>Welcome to Boycold Café<?php if (!empty($_SESSION['user_name'])): ?>, <?= htmlspecialchars($_SESSION['user_name']) ?><?php endif; ?>!</h1>
    <p>Experience the perfect blend of flavors and ambiance at BoyCold Café. Indulge in our handcrafted beverages, delectable pastries, and cozy atmosphere. Whether you're seeking a quiet spot to work or a vibrant space to connect with friends, BoyCold Café is your go-to destination for a delightful coffee experience.</p>
    <div class="button-container">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="logout.php">Log Out</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
    </div>

    <main>
        <h2>Discover Our Menu</h2>
        <p>Explore our diverse menu featuring a wide range of coffee blends, teas, and specialty drinks. From classic espresso to innovative seasonal creations, we have something to satisfy every palate. Don't forget to pair your beverage with our delicious pastries and snacks for the ultimate café experience.</p>
    </main>

    <?php if (empty($_SESSION['user_id'])): ?>
        <a href="register.php">Sign Up</a>
    <?php endif; ?>
</body>
</html>