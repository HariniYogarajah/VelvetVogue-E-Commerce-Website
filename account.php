<?php
session_start();

// Security: block access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: customer_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account | Velvet Vogue</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="logo">Velvet<span>Vogue</span></div>
    <nav class="main-nav">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Shop</a></li>
            <li><a href="products.php?category_id=1">Men</a></li>
            <li><a href="products.php?category_id=2">Women</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>
    <div class="nav-icons">
        <a href="account.php" title="Account">👤</a>
        <a href="cart.php" title="Cart">🛒</a>
    </div>
</header>

<section class="account-section">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
    <p>Manage your account and view your orders below.</p>

    <div class="account-menu">
        <a href="products.php" class="account-card">
            <h3>🛍️ Continue Shopping</h3>
            <p>Browse the latest arrivals</p>
        </a>
        <a href="cart.php" class="account-card">
            <h3>🛒 My Cart</h3>
            <p>Review items you've added</p>
        </a>
      <a href="customer_logout.php" class="account-card">
            <h3>🚪 Log Out</h3>
            <p>Sign out of your account</p>
        </a>
    </div>
</section>

<footer class="site-footer">
    <p>&copy; <?php echo date("Y"); ?> Velvet Vogue. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

</body>
</html>