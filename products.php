<?php
include 'includes/db.php';

$selected_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$selected_size = isset($_GET['size']) ? trim($_GET['size']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

$categories = $conn->query("SELECT * FROM categories");

// Build the WHERE clause dynamically based on active filters
$where_parts = [];
$params = [];
$types = "";

if ($selected_category_id > 0) {
    $where_parts[] = "products.category_id = ?";
    $params[] = $selected_category_id;
    $types .= "i";
}

if (!empty($selected_size)) {
    $where_parts[] = "FIND_IN_SET(?, REPLACE(products.sizes, ', ', ',')) > 0";
    $params[] = $selected_size;
    $types .= "s";
}

if ($min_price !== null) {
    $where_parts[] = "products.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== null) {
    $where_parts[] = "products.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$sql = "SELECT products.*, categories.category_name 
        FROM products 
        LEFT JOIN categories ON products.category_id = categories.category_id";

if (!empty($where_parts)) {
    $sql .= " WHERE " . implode(" AND ", $where_parts);
}
$sql .= " ORDER BY products.product_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$selected_category_name = "";
if ($selected_category_id > 0) {
    $name_stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    $name_stmt->bind_param("i", $selected_category_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    if ($name_row = $name_result->fetch_assoc()) {
        $selected_category_name = $name_row['category_name'];
    }
}

// Collect all distinct sizes across products, for the size filter dropdown
$all_sizes = [];
$size_result = $conn->query("SELECT sizes FROM products WHERE sizes IS NOT NULL AND sizes != ''");
while ($row = $size_result->fetch_assoc()) {
    $parts = array_map('trim', explode(',', $row['sizes']));
    foreach ($parts as $part) {
        if ($part !== '' && !in_array($part, $all_sizes)) {
            $all_sizes[] = $part;
        }
    }
}
sort($all_sizes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop | Velvet Vogue</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.refine-bar {
    max-width: 1000px;
    margin: 0 auto 20px auto;
    padding: 15px 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.refine-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.refine-group label {
    font-size: 12px;
    font-weight: 700;
    color: #555;
}
.refine-group input,
.refine-group select {
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}
.refine-group input[type="number"] {
    width: 100px;
}
.refine-apply-btn {
    padding: 9px 22px;
    background: #9B59B6;
    color: #fff;
    border: none;
    border-radius: 20px;
    font-weight: 700;
    cursor: pointer;
    font-size: 14px;
}
.refine-apply-btn:hover {
    background: #7d3c98;
}
.refine-clear {
    font-size: 13px;
    color: #888;
    text-decoration: underline;
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
    <h1>
        <?php
        if (!empty($selected_category_name)) {
            echo "Shop " . htmlspecialchars($selected_category_name);
        } else {
            echo "All Products";
        }
        ?>
    </h1>
</section>

<section class="filter-bar">
    <a href="products.php" class="filter-link <?php echo $selected_category_id == 0 ? 'active' : ''; ?>">All</a>
    <?php if ($categories && $categories->num_rows > 0): ?>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <a href="products.php?category_id=<?php echo $cat['category_id']; ?>" 
               class="filter-link <?php echo ($selected_category_id == $cat['category_id']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </a>
        <?php endwhile; ?>
    <?php endif; ?>
</section>

<form class="refine-bar" method="GET" action="products.php">
    <?php if ($selected_category_id > 0): ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category_id; ?>">
    <?php endif; ?>

    <div class="refine-group">
        <label for="size">Size</label>
        <select name="size" id="size">
            <option value="">Any</option>
            <?php foreach ($all_sizes as $s): ?>
                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($selected_size === $s) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="refine-group">
        <label for="min_price">Min Price (Rs.)</label>
        <input type="number" name="min_price" id="min_price" min="0" step="1" value="<?php echo $min_price !== null ? htmlspecialchars($min_price) : ''; ?>">
    </div>

    <div class="refine-group">
        <label for="max_price">Max Price (Rs.)</label>
        <input type="number" name="max_price" id="max_price" min="0" step="1" value="<?php echo $max_price !== null ? htmlspecialchars($max_price) : ''; ?>">
    </div>

    <button type="submit" class="refine-apply-btn">Apply Filters</button>

    <?php if (!empty($selected_size) || $min_price !== null || $max_price !== null): ?>
        <a href="products.php<?php echo $selected_category_id > 0 ? '?category_id=' . $selected_category_id : ''; ?>" class="refine-clear">Clear filters</a>
    <?php endif; ?>
</form>

<section class="featured-products">
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
            <p class="no-products">No products found matching these filters.</p>
        <?php endif; ?>
    </div>
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