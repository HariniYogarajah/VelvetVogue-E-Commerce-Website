<?php
session_start();
include 'includes/db.php';

// Handle quantity updates and removals (using cart_key, not plain product_id)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_cart']) && isset($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $cart_key => $qty) {
            $qty = (int)$qty;
            if (!isset($_SESSION['cart'][$cart_key])) continue;

            if ($qty <= 0) {
                unset($_SESSION['cart'][$cart_key]);
            } else {
                $_SESSION['cart'][$cart_key]['qty'] = $qty;
            }
        }
    }

    if (isset($_POST['remove_item'])) {
        $remove_key = $_POST['remove_item'];
        unset($_SESSION['cart'][$remove_key]);
    }

    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Build the list of cart items with full product info (same pattern as checkout.php)
$cart_items = [];
$grand_total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = [];
    foreach ($_SESSION['cart'] as $entry) {
        $product_ids[] = $entry['product_id'];
    }
    $product_ids = array_unique($product_ids);

    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));

    $sql = "SELECT * FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $products_by_id = [];
    while ($row = $result->fetch_assoc()) {
        $products_by_id[$row['product_id']] = $row;
    }

    foreach ($_SESSION['cart'] as $cart_key => $entry) {
        if (!isset($products_by_id[$entry['product_id']])) continue;
        $p = $products_by_id[$entry['product_id']];
        $subtotal = $p['price'] * $entry['qty'];
        $grand_total += $subtotal;
        $cart_items[] = [
            'cart_key' => $cart_key,
            'product' => $p,
            'qty' => $entry['qty'],
            'size' => $entry['size'],
            'color' => $entry['color'],
            'subtotal' => $subtotal
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart | Velvet Vogue</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.cart-section {
    max-width: 950px;
    margin: 40px auto;
    padding: 0 20px;
}
.cart-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.cart-table th {
    background: #1a1a1a;
    color: #fff;
    text-align: left;
    padding: 14px;
}
.cart-table td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.cart-product-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cart-product-cell img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}
.variant-tags {
    font-size: 12px;
    color: #777;
    margin-top: 3px;
}
.cart-table input[type="number"] {
    width: 60px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
.remove-btn {
    background: none;
    border: none;
    color: #b3261e;
    cursor: pointer;
    font-weight: 600;
    text-decoration: underline;
}
.cart-summary {
    margin-top: 20px;
    text-align: right;
}
.cart-summary .total-line {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 15px;
}
.cart-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}
.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border-radius: 25px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 15px;
}
.btn-primary {
    background: #8b3fd1;
    color: #fff;
}
.btn-primary:hover {
    background: #7530b8;
}
.btn-secondary {
    background: #eee;
    color: #1a1a1a;
}
.btn-secondary:hover {
    background: #ddd;
}
.empty-cart {
    text-align: center;
    padding: 60px 20px;
    color: #666;
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
        <a href="cart.php" title="Cart">🛒</a>
    </div>
</header>

<section class="shop-title">
    <h1>Your Cart</h1>
</section>

<section class="cart-section">
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST" action="cart.php">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <?php $p = $item['product']; ?>
                        <tr>
                            <td>
                                <div class="cart-product-cell">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>">
                                    <?php endif; ?>
                                    <div>
                                        <span><?php echo htmlspecialchars($p['product_name']); ?></span>
                                        <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                            <div class="variant-tags">
                                                <?php if (!empty($item['size'])): ?>Size: <?php echo htmlspecialchars($item['size']); ?><?php endif; ?>
                                                <?php if (!empty($item['size']) && !empty($item['color'])): ?> | <?php endif; ?>
                                                <?php if (!empty($item['color'])): ?>Color: <?php echo htmlspecialchars($item['color']); ?><?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                            <td>
                                <input type="number" name="quantities[<?php echo htmlspecialchars($item['cart_key']); ?>]" value="<?php echo $item['qty']; ?>" min="1" max="<?php echo (int)$p['stock']; ?>">
                            </td>
                            <td>Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <button type="submit" name="remove_item" value="<?php echo htmlspecialchars($item['cart_key']); ?>" class="remove-btn">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <p class="total-line">Total: Rs. <?php echo number_format($grand_total, 2); ?></p>
                <div class="cart-actions">
                    <a href="products.php" class="btn-secondary">Continue Shopping</a>
                    <button type="submit" name="update_cart" class="btn-secondary">Update Quantities</button>
                    <button type="submit" name="clear_cart" class="btn-secondary" onclick="return confirm('Clear entire cart?');">Clear Cart</button>
                    <a href="checkout.php" class="btn-primary">Proceed to Checkout</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>

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