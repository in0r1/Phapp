<?php
session_start();
include 'config.php';

$id = $_GET['id'] ?? 0;
$product_query = "SELECT * FROM products WHERE id = $id";
$product_result = mysqli_query($conn, $product_query);

if (!$product = mysqli_fetch_assoc($product_result)) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $comment_text = $_POST['comment'] ?? '';
    
    if (!empty($comment_text)) {
        $insert_query = "INSERT INTO comments (product_id, user_id, comment_text, created_at)
                         VALUES ($id, {$_SESSION['user_id']}, '$comment_text', NOW())";
        mysqli_query($conn, $insert_query);
    }
}

$comments_query = "SELECT c.*, u.username, u.avatar, u.role
                   FROM comments c
                   JOIN users u ON c.user_id = u.id
                   WHERE c.product_id = $id
                   ORDER BY c.created_at DESC";
$comments_result = mysqli_query($conn, $comments_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | PhaPP Shop</title>
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
        <div class="breadcrumb">
            <a href="index.php">Back to Products</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <span class="divider">|</span>
                <a href="admin/?action=edit&product=<?= $product['id'] ?>" class="admin-link-small">Edit Product</a>
            <?php endif; ?>
        </div>

        <div class="product-detail">
            <div class="product-image">
                <img src="images/<?= htmlspecialchars($product['image']) ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <div class="product-details">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                <p class="product-description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

                <div class="product-meta">
                    <span>ID: <?= $product['id'] ?></span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <span>Created by user ID: <?= $product['created_by'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="comments-section">
            <h2>User Comments (<?= mysqli_num_rows($comments_result) ?>)</h2>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" class="comment-form">
                    <textarea name="comment"
                              placeholder="Write your review..."
                              required></textarea>
                    <button type="submit" class="btn-submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">
                    <a href="login.php">Login</a> to leave a comment
                </p>
            <?php endif; ?>

            <div class="comments-list">
                <?php
                mysqli_data_seek($comments_result, 0);
                while ($comment = mysqli_fetch_assoc($comments_result)):
                    $comment_role = $comment['role'] ?? 'user';
                    ?>
                    <div class="comment">
                        <?php if ($comment['avatar']): ?>
                            <img src="uploads/<?= htmlspecialchars($comment['avatar']) ?>"
                                 class="comment-avatar"
                                 alt="Avatar">
                        <?php else: ?>
                            <div class="comment-avatar comment-avatar-default">
                                <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="comment-body">
                            <div class="comment-header">
                                <strong class="username">
                                    <?= htmlspecialchars($comment['username']) ?>
                                    <?php if ($comment_role === 'admin'): ?>
                                        <span class="badge-admin">Admin</span>
                                    <?php endif; ?>
                                </strong>
                                <span class="date">
                                    <?= date('M j, Y H:i', strtotime($comment['created_at'])) ?>
                                </span>
                            </div>
                            <div class="comment-text"><?= $comment['comment_text'] ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if (mysqli_num_rows($comments_result) === 0): ?>
                    <p class="no-comments">No comments yet. Be the first to review!</p>
                <?php endif; ?>
            </div>
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
