<?php
session_start();
include 'config.php';

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM products WHERE name LIKE '%$search%' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhaPP Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="index.php">PhaPP Shop</a>
        </div>
        <div class="nav-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-user">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="profile.php" class="admin-link">Profile</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/" class="admin-link">Admin</a>
                    <?php endif; ?>
                </span>
                <a href="logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-btn">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="shop-header">
            <h1>PhaPP Shop</h1>
            <p class="tagline">Gadgets for the coding lifestyle</p>

            <form method="GET" class="search-form">
                <input type="text" name="search"
                       placeholder="Search products..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="products-grid">
            <?php if (mysqli_num_rows($result) === 0): ?>
                <p class="no-results">No products found. Try a different search term.</p>
            <?php else: ?>
                <?php while ($product = mysqli_fetch_assoc($result)): ?>
                    <div class="product-card">
                        <img src="images/<?= htmlspecialchars($product['image']) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="price">$<?= number_format($product['price'], 2) ?></p>
                            <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>PhaPP Shop - Educational Project Only</p>
            <p><small>This site contains intentional security vulnerabilities for learning purposes.</small></p>
        </div>
    </footer>
</body>
</html>