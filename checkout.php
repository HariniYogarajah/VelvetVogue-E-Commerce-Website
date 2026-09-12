<?php
session_start();
include 'includes/db.php';

$logged_in_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$order_placed = false;
$order_id = 0;
$error = "";

// Build cart items and total (same logic as cart.php)
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

// Handle order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($cart_items)) {
    $name = trim($_POST['customer_name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];

    if (!empty($name) && !empty($email) && !empty($address) && !empty($city) && !empty($phone)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, email, address, city, phone, payment_method, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssd", $logged_in_user_id, $name, $email, $address, $city, $phone, $payment_method, $grand_total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal, size, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $p = $item['product'];
                $item_stmt->bind_param(
                    "iisdidss",
                    $order_id,
                    $p['product_id'],
                    $p['product_name'],
                    $p['price'],
                    $item['qty'],
                    $item['subtotal'],
                    $item['size'],
                    $item['color']
                );
                $item_stmt->execute();

                // Reduce stock
                $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                $stock_stmt->bind_param("ii", $item['qty'], $p['product_id']);
                $stock_stmt->execute();
                $stock_stmt->close();
            }
            $item_stmt->close();

            $conn->commit();

            // Clear the cart now that the order is placed
            $_SESSION['cart'] = [];
            $order_placed = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "There was a problem placing your order. Please try again.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Velvet Vogue</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.checkout-section {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}
.checkout-form-box {
    flex: 1 1 400px;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.checkout-form-box h2 {
    margin-top: 0;
}
.checkout-form-box label {
    display: block;
    font-weight: 600;
    margin: 14px 0 6px;
}
.checkout-form-box input,
.checkout-form-box select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
    box-sizing: border-box;
}
.checkout-summary-box {
    flex: 1 1 300px;
    background: #fafafa;
    padding: 25px;
    border-radius: 10px;
    height: fit-content;
}
.checkout-summary-box h2 {
    margin-top: 0;
}
.summary-line {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}
.summary-total {
    display: flex;
    justify-content: space-between;
    padding-top: 14px;
    font-size: 18px;
    font-weight: 700;
}
.place-order-btn {
    margin-top: 20px;
    width: 100%;
    padding: 14px;
    background: #8b3fd1;
    color: #fff;
    border: none;
    border-radius: 25px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
}
.place-order-btn:hover {
    background: #7530b8;
}
.order-confirmation {
    max-width: 600px;
    margin: 60px auto;
    text-align: center;
    padding: 40px 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.order-confirmation h1 {
    color: #1e7a3c;
}
.error-msg {
    max-width: 900px;
    margin: 0 auto 15px auto;
    padding: 12px 16px;
    background: #fdecec;
    color: #b3261e;
    border: 1px solid #f5c2c0;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
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

<?php if ($order_placed): ?>
    <div class="order-confirmation">
        <h1>✔ Order Placed!</h1>
        <p>Thank you for shopping with Velvet Vogue.</p>
        <p>Your order number is <strong>#<?php echo $order_id; ?></strong>.</p>
        <p>We've noted your order and will process it shortly.</p>
        <a href="products.php" class="place-order-btn" style="display:inline-block; text-decoration:none; width:auto; padding:12px 28px;">Continue Shopping</a>
    </div>
<?php elseif (empty($cart_items)): ?>
    <section class="shop-title">
        <h1>Checkout</h1>
    </section>
    <div class="order-confirmation">
        <p>Your cart is empty, so there's nothing to check out.</p>
        <a href="products.php" class="place-order-btn" style="display:inline-block; text-decoration:none; width:auto; padding:12px 28px;">Go Shopping</a>
    </div>
<?php else: ?>
    <section class="shop-title">
        <h1>Checkout</h1>
    </section>

    <?php if (!empty($error)): ?>
        <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <section class="checkout-section">
        <div class="checkout-form-box">
            <h2>Shipping Details</h2>
            <form method="POST" action="checkout.php">
                <label for="customer_name">Full Name</label>
                <input type="text" id="customer_name" name="customer_name" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>

                <label for="city">City</label>
                <input type="text" id="city" name="city" required>

                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" required>

                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                    <option value="Card Payment">Card Payment</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>

                <button type="submit" class="place-order-btn">Place Order</button>
            </form>
        </div>

        <div class="checkout-summary-box">
            <h2>Order Summary</h2>
            <?php foreach ($cart_items as $item): ?>
                <div class="summary-line">
                    <span>
                        <?php echo htmlspecialchars($item['product']['product_name']); ?> × <?php echo $item['qty']; ?>
                        <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                            <br><small style="color:#888;">
                                <?php echo !empty($item['size']) ? 'Size: ' . htmlspecialchars($item['size']) : ''; ?>
                                <?php echo (!empty($item['size']) && !empty($item['color'])) ? ' | ' : ''; ?>
                                <?php echo !empty($item['color']) ? 'Color: ' . htmlspecialchars($item['color']) : ''; ?>
                            </small>
                        <?php endif; ?>
                    </span>
                    <span>Rs. <?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-total">
                <span>Total</span>
                <span>Rs. <?php echo number_format($grand_total, 2); ?></span>
            </div>
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