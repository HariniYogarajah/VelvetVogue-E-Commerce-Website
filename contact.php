<?php
session_start();
include 'includes/db.php';

$logged_in_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $sql = "INSERT INTO contacts (user_id, name, email, message) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $logged_in_user_id, $name, $email, $message);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
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
<title>Contact Us | Velvet Vogue</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.contact-section {
    max-width: 600px;
    margin: 40px auto;
    padding: 0 20px;
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.contact-form label {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: -10px;
}

.contact-form input,
.contact-form textarea {
    padding: 12px 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
}

.contact-form input:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: #8b3fd1;
}

.contact-form textarea {
    resize: vertical;
    min-height: 140px;
}

.contact-form button {
    align-self: flex-start;
    padding: 12px 28px;
    background: #8b3fd1;
    color: #fff;
    border: none;
    border-radius: 25px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.contact-form button:hover {
    background: #7530b8;
}

.success-msg {
    max-width: 600px;
    margin: 40px auto;
    padding: 18px 20px;
    background: #e6f7ec;
    color: #1e7a3c;
    border: 1px solid #b7e3c6;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
}

.error-msg {
    max-width: 600px;
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
        <a href="login.php" title="Account">👤</a>
        <a href="cart.php" title="Cart">🛒</a>
    </div>
</header>

<section class="shop-title">
    <h1>Contact Us</h1>
</section>

<section class="contact-section">
    <?php if ($success): ?>
        <p class="success-msg">Thanks for reaching out! We'll get back to you soon.</p>
    <?php else: ?>
        <?php if (!empty($error)): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form class="contact-form" method="POST" action="contact.php">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="6" required></textarea>

            <button type="submit" class="btn-primary">Send Message</button>
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