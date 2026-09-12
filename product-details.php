<?php
session_start();
include 'includes/db.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";

// Handle "Add to Cart" form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $qty = max(1, (int)$_POST['quantity']);
    $selected_size = isset($_POST['size']) ? $_POST['size'] : '';
    $selected_color = isset($_POST['color']) ? $_POST['color'] : '';

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Build a unique cart key that includes size/color so different variants stack separately
    $cart_key = $product_id . '_' . $selected_size . '_' . $selected_color;

    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'qty' => $qty,
            'size' => $selected_size,
            'color' => $selected_color
        ];
    }

    $message = "Added to cart!";
}

// Fetch the product
$stmt = $conn->prepare("SELECT products.*, categories.category_name 
                         FROM products 
                         LEFT JOIN categories ON products.category_id = categories.category_id 
                         WHERE products.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Split sizes/colors into arrays for the dropdowns
$size_options = [];
$color_options = [];
if ($product) {
    if (!empty($product['sizes'])) {
        $size_options = array_map('trim', explode(',', $product['sizes']));
    }
    if (!empty($product['colors'])) {
        $color_options = array_map('trim', explode(',', $product['colors']));
    }
}

// Count items currently in cart (for the nav icon)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += is_array($item) ? $item['qty'] : $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $product ? htmlspecialchars($product['product_name']) : 'Product Not Found'; ?> | Velvet Vogue</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.product-details-section {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
}
.product-details-image {
    flex: 1 1 350px;
}
.product-details-image img {
    width: 100%;
    border-radius: 10px;
    display: block;
}
.product-details-info {
    flex: 1 1 350px;
}
.product-details-info h1 {
    margin-top: 0;
}
.product-details-info .price {
    font-size: 24px;
    font-weight: 700;
    color: #8b3fd1;
    margin: 10px 0;
}
.product-details-info .category-tag {
    display: inline-block;
    background: #f0e6fa;
    color: #8b3fd1;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 13px;
    margin-bottom: 15px;
}
.product-details-info .description {
    color: #444;
    line-height: 1.6;
    margin-bottom: 20px;
}
.stock-info {
    margin-bottom: 20px;
    font-weight: 600;
}
.in-stock { color: #1e7a3c; }
.out-of-stock { color: #b3261e; }
.variant-group {
    margin-bottom: 18px;
}
.variant-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
}
.variant-group select {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    min-width: 160px;
}
.add-to-cart-form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 15px;
}
.add-to-cart-form input[type="number"] {
    width: 70px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}
.add-to-cart-form button {
    padding: 12px 28px;
    background: #8b3fd1;
    color: #fff;
    border: none;
    border-radius: 25px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
}
.add-to-cart-form button:hover {
    background: #7530b8;
}
.add-to-cart-form button:disabled {
    background: #ccc;
    cursor: not-allowed;
}
.cart-success-msg {
    margin-top: 15px;
    padding: 10px 16px;
    background: #e6f7ec;
    color: #1e7a3c;
    border-radius: 6px;
    display: inline-block;
}
</style>
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
            <li><a href="products.php?category_id=3">Children</a></li>
            <li><a href="products.php?category_id=4">Formal</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>
    <div class="nav-icons">
        <a href="account.php" title="Account">👤</a>
        <a href="cart.php" title="Cart">🛒 <?php echo $cart_count > 0 ? '(' . $cart_count . ')' : ''; ?></a>
    </div>
</header>

<?php if (!$product): ?>
    <section class="shop-title">
        <h1>Product Not Found</h1>
        <p><a href="products.php">Back to shop</a></p>
    </section>
<?php else: ?>
    <section class="product-details-section">
        <div class="product-details-image">
            <?php if (!empty($product['image'])): ?>
                <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
                <div class="no-image">No Image</div>
            <?php endif; ?>
        </div>
        <div class="product-details-info">
            <?php if (!empty($product['category_name'])): ?>
                <span class="category-tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
            <p class="price">Rs. <?php echo number_format($product['price'], 2); ?></p>
            <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <p class="stock-info">
                <?php if ($product['stock'] > 0): ?>
                    <span class="in-stock">✔ In Stock (<?php echo (int)$product['stock']; ?> available)</span>
                <?php else: ?>
                    <span class="out-of-stock">✘ Out of Stock</span>
                <?php endif; ?>
            </p>

            <?php if (!empty($message)): ?>
                <p class="cart-success-msg"><?php echo htmlspecialchars($message); ?> — <a href="cart.php">View Cart</a></p>
            <?php endif; ?>

            <?php if ($product['stock'] > 0): ?>
                <form class="add-to-cart-form" method="POST" action="product-details.php?id=<?php echo $product['product_id']; ?>">

                    <?php if (!empty($size_options)): ?>
                        <div class="variant-group">
                            <label for="size">Size</label>
                            <select name="size" id="size" required>
                                <?php foreach ($size_options as $size): ?>
                                    <option value="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($color_options)): ?>
                        <div class="variant-group">
                            <label for="color">Color</label>
                            <select name="color" id="color" required>
                                <?php foreach ($color_options as $color): ?>
                                    <option value="<?php echo htmlspecialchars($color); ?>"><?php echo htmlspecialchars($color); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <input type="number" name="quantity" value="1" min="1" max="<?php echo (int)$product['stock']; ?>">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

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