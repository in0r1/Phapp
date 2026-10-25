<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['redirect_after_login'] = $_SERVER['PHP_SELF'];
    header('Location: ../login.php');
    exit;
}

$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

switch ($action) {
    case 'delete_product':
        $pid = $_GET['id'] ?? 0;
        mysqli_query($conn, "DELETE FROM products WHERE id = $pid");
        mysqli_query($conn, "DELETE FROM comments WHERE product_id = $pid");
        $message = "Product deleted";
        break;

    case 'delete_user':
        $uid = $_GET['id'] ?? 0;
        if ($uid != $_SESSION['user_id']) {
            mysqli_query($conn, "DELETE FROM users WHERE id = $uid");
        }
        $message = "User deleted";
        break;

    case 'add_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $desc = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;

            $image_name = 'placeholder.jpg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($ext, $allowed)) {
                    $new_name = uniqid('prod_') . '.' . $ext;
                    $dest = '../images/' . $new_name;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        $image_name = $new_name;
                    } else {
                        $error = "Failed to upload image. Check folder permissions.";
                    }
                } else {
                    $error = "Invalid file type. Allowed: JPG, PNG, GIF";
                }
            }

            if (empty($error)) {
                $sql = "INSERT INTO products (name, description, price, image, created_by)
                        VALUES ('$name', '$desc', '$price', '$image_name', {$_SESSION['user_id']})";

                if (mysqli_query($conn, $sql)) {
                    $message = "Product added successfully.";
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
        break;

    case 'promote_user':
        $uid = $_GET['id'] ?? 0;
        mysqli_query($conn, "UPDATE users SET role = 'admin' WHERE id = $uid");
        $message = "User promoted to admin.";
        break;

    case 'demote_user':
        $uid = $_GET['id'] ?? 0;
        if ($uid != $_SESSION['user_id']) {
            mysqli_query($conn, "UPDATE users SET role = 'user' WHERE id = $uid");
            $message = "User demoted to regular user.";
        }
        break;
}

$user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'];
$comment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM comments"))['cnt'];

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$recent_comments = mysqli_query($conn, "SELECT c.*, u.username, p.name as product_name
                                          FROM comments c
                                          JOIN users u ON c.user_id = u.id
                                          LEFT JOIN products p ON c.product_id = p.id
                                          ORDER BY c.created_at DESC
                                          LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Prod Has Another Prayer Pending Shop</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="../index.php">Prod Has Another Prayer Pending Shop</a>
        </div>
        <div class="nav-menu">
            <span class="nav-user">
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="../profile.php" class="admin-link">Profile</a>
                <a href="#" class="admin-link">Admin</a>
            </span>
            <a href="../logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="container admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> | IP: <?= $_SERVER['REMOTE_ADDR'] ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-value"><?= $user_count ?></p>
            </div>
            <div class="stat-card">
                <h3>Products</h3>
                <p class="stat-value"><?= $product_count ?></p>
            </div>
            <div class="stat-card">
                <h3>Comments</h3>
                <p class="stat-value"><?= $comment_count ?></p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-nav">
            <a href="#dashboard" class="active">Overview</a>
            <a href="#users-section">Users</a>
            <a href="#products-section">Products</a>
            <a href="#add-product">Add Product</a>
            <a href="#comments">Recent Comments</a>
        </div>

        <section id="dashboard" class="admin-section">
            <h2>System Overview</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Database Status</td>
                        <td>Connected</td>
                        <td><span class="badge badge-success">OK</span></td>
                    </tr>
                    <tr>
                        <td>Uploads Directory</td>
                        <td>/uploads/</td>
                        <td><span class="badge badge-warning">Unrestricted</span></td>
                    </tr>
                    <tr>
                        <td>Images Directory</td>
                        <td>/images/</td>
                        <td><span class="badge badge-warning">Minimal Checks</span></td>
                    </tr>
                    <tr>
                        <td>Input Validation</td>
                        <td>None</td>
                        <td><span class="badge badge-error">Disabled</span></td>
                    </tr>
                </tbody>
            </table>
            <p class="text-muted" style="margin-top:1rem;"><small>These vulnerability indicators are intentionally shown for educational purposes.</small></p>
        </section>

        <section id="users-section" class="admin-section">
            <h2>Manage Users</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Avatar</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <?php if ($u['avatar']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($u['avatar']) ?>"
                                         style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <div class="comment-avatar-default" style="width:35px;height:35px;font-size:0.9rem;">
                                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <span class="badge badge-<?= $u['role'] ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($u['role'] === 'user'): ?>
                                        <a href="?action=promote_user&id=<?= $u['id'] ?>"
                                           class="btn-small btn-promote"
                                           onclick="return confirm('Promote <?= htmlspecialchars($u['username']) ?> to admin?')">Promote</a>
                                    <?php else: ?>
                                        <a href="?action=demote_user&id=<?= $u['id'] ?>"
                                           class="btn-small btn-view"
                                           onclick="return confirm('Demote <?= htmlspecialchars($u['username']) ?>?')">Demote</a>
                                    <?php endif; ?>
                                    <a href="?action=delete_user&id=<?= $u['id'] ?>"
                                       class="btn-small btn-delete"
                                       onclick="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>?')">Delete</a>
                                <?php else: ?>
                                    <span class="text-muted">(Current User)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <section id="products-section" class="admin-section">
            <h2>Manage Products</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Image File</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($products, 0); ?>
                    <?php while ($p = mysqli_fetch_assoc($products)): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>
                                <img src="../images/<?= htmlspecialchars($p['image']) ?>"
                                     style="width:50px;height:50px;object-fit:cover;border-radius:4px;"
                                     onerror="this.src='../images/placeholder.jpg';this.onerror=null;">
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>$<?= number_format($p['price'], 2) ?></td>
                            <td><code><?= htmlspecialchars($p['image']) ?></code></td>
                            <td>
                                <a href="../product.php?id=<?= $p['id'] ?>"
                                   class="btn-small btn-view" target="_blank">View</a>
                                <a href="?action=delete_product&id=<?= $p['id'] ?>"
                                   class="btn-small btn-delete"
                                   onclick="return confirm('Delete <?= htmlspecialchars($p['name']) ?> and all comments?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <section id="add-product" class="admin-section">
            <h2>Add New Product</h2>
            <form method="POST" action="?action=add_product" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required placeholder="e.g., Developer T-Shirt">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" required placeholder="Describe the product..."></textarea>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" min="0" required placeholder="19.99">
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <div class="file-upload-area" id="uploadArea" onclick="document.getElementById('imageInput').click()">
                        <div class="upload-icon">&#x1F4F7;</div>
                        <p>Click to select an image<br><small>JPG, PNG, GIF allowed</small></p>
                    </div>
                    <input type="file" name="image" id="imageInput" class="file-input-hidden"
                           accept=".jpg,.jpeg,.png,.gif" required>
                    <img id="previewImg" class="image-preview" style="display:none;" alt="Preview">
                    <small class="text-muted">Image will be stored in <code>/images/</code></small>
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </section>

        <section id="comments" class="admin-section">
            <h2>Recent Comments</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Product</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = mysqli_fetch_assoc($recent_comments)): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['username']) ?></td>
                            <td><?= htmlspecialchars($c['product_name'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars(substr($c['comment_text'], 0, 60)) ?>...</td>
                            <td><?= date('M j, H:i', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="../product.php?id=<?= $c['product_id'] ?>" class="btn-small btn-view" target="_blank">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>

    <footer>
        <div class="footer-content">
            <p>Prod Has Another Prayer Pending Shop - Educational Project Only</p>
            <p><small>This site contains intentional security vulnerabilities for learning purposes.</small></p>
        </div>
    </footer>

    <script>
        const imageInput = document.getElementById('imageInput');
        const uploadArea = document.getElementById('uploadArea');
        const previewImg = document.getElementById('previewImg');

        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadArea.classList.add('has-file');
                    uploadArea.querySelector('.upload-icon').textContent = '&#x2705;';
                    uploadArea.querySelector('p').innerHTML = '<strong>' + file.name + '</strong><br><small>' + (file.size / 1024).toFixed(1) + ' KB</small>';

                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        previewImg.src = ev.target.result;
                        previewImg.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
</body>
</html>