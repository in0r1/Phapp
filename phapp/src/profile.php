<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

$uid = $_GET['user_id'] ?? $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $uid";
$user = mysqli_fetch_assoc(mysqli_query($conn, $user_query));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $detected_mime = mime_content_type($_FILES['avatar']['tmp_name']);
            $allowed_mimes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/bmp'
            ];

            if (in_array($detected_mime, $allowed_mimes)) {
                $new_name = uniqid('avatar_') . '.' . $ext;
                $dest = 'uploads/' . $new_name;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                    if (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) {
                        unlink('uploads/' . $user['avatar']);
                    }
                    mysqli_query($conn, "UPDATE users SET avatar = '$new_name' WHERE id = $uid");
                    $message = "Avatar updated successfully.";
                    $user['avatar'] = $new_name;
                } else {
                    $error = "Failed to upload avatar. Check folder permissions.";
                }
            } else {
                $error = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF";
            }
        } else {
            $error = "Please select a file to upload.";
        }
    }

    if ($_POST['action'] === 'remove_avatar') {
        if (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) {
            unlink('uploads/' . $user['avatar']);
        }
        mysqli_query($conn, "UPDATE users SET avatar = NULL WHERE id = $uid");
        $message = "Avatar removed.";
        $user['avatar'] = null;
    }

    if ($_POST['action'] === 'change_password') {
        #$current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        #$current_hash = md5($current);

        #$check = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid AND password = '$current_hash'");
        #if (mysqli_num_rows($check) === 1) {
            if (strlen($new_pass) < 6) {
                $error = "New password must be at least 6 characters.";
            } elseif ($new_pass !== $confirm) {
                $error = "New passwords do not match.";
            } else {
                $new_hash = md5($new_pass);
                mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE id = $uid");
                $message = "Password changed successfully.";
            }
        #} 
        #else {
        #    $error = "Current password is incorrect.";
        #}
    }

    if ($_POST['action'] === 'change_username') {
        $new_username = trim($_POST['new_username'] ?? '');

        if (strlen($new_username) < 3) {
            $error = "Username must be at least 3 characters.";
        } else {
            $exists = mysqli_query($conn, "SELECT id FROM users WHERE username = '$new_username' AND id != $uid");
            if (mysqli_num_rows($exists) > 0) {
                $error = "Username already taken.";
            } else {
                mysqli_query($conn, "UPDATE users SET username = '$new_username' WHERE id = $uid");
                $_SESSION['username'] = $new_username;
                $user['username'] = $new_username;
                $message = "Username changed successfully.";
            }
        }
    }
}

$my_comments = mysqli_query($conn, "SELECT c.*, p.name as product_name, p.id as pid
                                    FROM comments c
                                    LEFT JOIN products p ON c.product_id = p.id
                                    WHERE c.user_id = $uid
                                    ORDER BY c.created_at DESC
                                    LIMIT 10");

$my_comment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM comments WHERE user_id = $uid"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | PhaPP Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="index.php">PhaPP Shop</a>
        </div>
        <div class="nav-menu">
            <span class="nav-user">
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="profile.php" class="admin-link">Profile</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/" class="admin-link">Admin</a>
                <?php endif; ?>
            </span>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="divider">/</span>
            <span>Profile</span>
        </div>

        <!-- Profile Info Card -->
        <div class="product-detail" style="grid-template-columns: 1fr 2fr; align-items: start;">
            <div class="product-image">
                <?php if ($user['avatar']): ?>
                    <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" class="profile-avatar-large" alt="Avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-details">
                <h1><?= htmlspecialchars($user['username']) ?></h1>
                <p class="text-muted">User ID: #<?= $user['id'] ?> | Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
                
                <div class="product-meta">
                    <span><strong>Status:</strong>
                        <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                    </span>
                    <span><strong>Comments:</strong> <?= $my_comment_count ?></span>
                </div>
            </div>
        </div>

        <!-- Avatar Settings -->
        <div class="comments-section">
            <h2>Avatar Settings</h2>

            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                <input type="hidden" name="action" value="update_avatar">

                <div class="file-upload-area" id="uploadArea" onclick="document.getElementById('avatarInput').click()">
                    <div class="upload-icon">&#x1F4E4;</div>
                    <p>Click to upload new avatar<br><small>JPG, PNG, GIF allowed • Max 2MB</small></p>
                </div>
                
                <input type="file" name="avatar" id="avatarInput" class="file-input-hidden"
                       accept="image/*">
                
                <div id="previewContainer" style="display:none;">
                    <img id="previewImg" class="image-preview" alt="Preview">
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn-submit">Save New Avatar</button>
                    
                    <?php if ($user['avatar']): ?>
                        <form method="POST" class="inline-form" style="display:inline;">
                            <input type="hidden" name="action" value="remove_avatar">
                            <button type="submit" class="btn-danger"
                                    onclick="return confirm('Remove your avatar?')">Remove Avatar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Change Username -->
        <div class="comments-section">
            <h2>Change Username</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="change_username">
                
                <div class="form-group">
                    <label>Current Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>New Username *</label>
                    <input type="text" name="new_username" required minlength="3" maxlength="50"
                           placeholder="Enter new username">
                </div>
                
                <button type="submit" class="btn-submit">Update Username</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="comments-section">
            <h2>Change Password</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="change_password">
                <!--
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" required>
                </div>
                    -->
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required minlength="6">
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>

        <!-- My Comments -->
        <div class="comments-section">
            <h2>My Recent Comments</h2>

            <?php if (mysqli_num_rows($my_comments) > 0): ?>
                <div class="comments-list">
                    <?php while ($mc = mysqli_fetch_assoc($my_comments)): ?>
                        <div class="comment">
                            <div class="comment-body">
                                <div class="comment-header">
                                    <strong class="username">
                                        <?= htmlspecialchars($mc['product_name'] ?? 'Deleted Product') ?>
                                    </strong>
                                    <span class="date">
                                        <?= date('M j, Y H:i', strtotime($mc['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="comment-text">
                                    <?= $mc['comment_text'] ?>
                                </div>
                                <?php if ($mc['pid']): ?>
                                    <a href="product.php?id=<?= $mc['pid'] ?>" class="comment-link">
                                        View on product &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-comments">You have not posted any comments yet.</p>
            <?php endif; ?>
        </div>

    </div>

    <footer>
        <div class="footer-content">
            <p>PhaPP Shop - Educational Project Only</p>
            <p><small>This site contains intentional security vulnerabilities for learning purposes.</small></p>
        </div>
    </footer>

    <script>
        const avatarInput = document.getElementById('avatarInput');
        const uploadArea = document.getElementById('uploadArea');
        const previewImg = document.getElementById('previewImg');
        const previewContainer = document.getElementById('previewContainer');

        if (avatarInput && previewContainer) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadArea.querySelector('p').innerHTML = '<strong>' + file.name + '</strong><br><small>' + Math.round(file.size / 1024) + ' KB selected</small>';
                    
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        previewImg.src = ev.target.result;
                        previewImg.style.display = 'block';
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
</body>
</html>