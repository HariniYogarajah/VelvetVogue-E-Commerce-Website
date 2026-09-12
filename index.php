<?php
// Connect to the database
include 'includes/db.php';

// Fetch a few products to display on the home page
$sql = "SELECT * FROM products ORDER BY product_id DESC LIMIT 8";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Velvet Vogue | Trendy Casualwear & Formalwear</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Header / Navigation -->
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
        <a href="login.php" title="Account">👤</a>
        <a href="cart.php" title="Cart">🛒</a>
    </div>
</header>

<!-- Hero / Promotion Banner -->
<section class="hero">
    <div class="hero-content">
        <h1>Express Your Style</h1>
        <p>Discover the latest trends in casual and formal wear, made for young adults who dare to stand out.</p>
        <a href="products.php" class="btn-primary">Shop New Arrivals</a>
    </div>
</section>

<!-- Featured Products -->
<section class="featured-products">
    <h2>New Arrivals</h2>

    <div class="product-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($row['image'])): ?>
                            <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                        <?php else: ?>
                            <div class="no-image">No Image</div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                    <p class="price">Rs. <?php echo number_format($row['price'], 2); ?></p>
                    <a href="product-details.php?id=<?php echo $row['product_id']; ?>" class="btn-secondary">View Details</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-products">No products available yet. Add some from the admin panel!</p>
        <?php endif; ?>
    </div>
</section>

<!-- Categories Section -->
<section class="categories">
    <h2>Shop by Category</h2>
    <div class="category-grid">
        <?php
        $cat_result = $conn->query("SELECT * FROM categories");
        if ($cat_result && $cat_result->num_rows > 0):
            while ($cat = $cat_result->fetch_assoc()):
        ?>
           <a href="products.php?category_id=<?php echo $cat['category_id']; ?>" class="category-card">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </a>
        <?php
            endwhile;
        endif;
        ?>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer">
    <p>&copy; <?php echo date("Y"); ?> Velvet Vogue. All rights reserved.</p>
    <div class="footer-links">
        <a href="contact.php">Contact Us</a>
        <a href="about.php">About</a>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>