<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $page_title ?? 'PhaPP Shop' ?></title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <nav class="navbar">
            <div class="nav-brand">
                <a href="index.php">phapp Shop</a>
            </div>
            <div class="nav-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="nav-user">
                        <?= htmlspecialchars($_SESSION['username']) ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin.php" class="admin-link">Admin</a>
                            <?php endif; ?>
                        </span>
                        <a href="logout.php" class="nav-link">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="nav-link">Login</a>
                            <a href="register.php" class="nav-btn">Register</a>
                            <?php endif; ?>
                        </div>
                    </nav>
                    <main>
