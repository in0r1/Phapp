<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

include 'config.php';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $avatar = $_FILES['avatar']['name'] ?? '';
    
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match";
    }
    
    $hashed_password = md5($password);
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $errors[] = "Username already taken";
    }
    
    $avatar_path = '';
    if (!empty($avatar)) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($avatar, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('avatar_') . '.' . $ext;
            $upload_path = 'uploads/' . $new_name;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $avatar_path = $new_name;
            } else {
                $errors[] = "Failed to upload avatar";
            }
        } else {
            $errors[] = "Invalid file type. Allowed: JPG, PNG, GIF";
        }
    }
    
    if (empty($errors)) {
        $insert = "INSERT INTO users (username, password, avatar, role, created_at) 
                   VALUES ('$username', '$hashed_password', '$avatar_path', 'user', NOW())";
        
        if (mysqli_query($conn, $insert)) {
            $success = "Account created successfully. <a href='login.php'>Click here to login</a>";
        } else {
            $errors[] = "Registration failed: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PhaPP Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>Create Account</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            
            <form method="POST" enctype="multipart/form-data" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required 
                           minlength="3"
                           maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           minlength="6">
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="avatar">Profile Avatar (Optional)</label>
                    <input type="file" 
                           id="avatar" 
                           name="avatar" 
                           accept=".jpg,.jpeg,.png,.gif">
                    <small class="text-muted">Max 2MB. JPG, PNG, GIF only</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    Create Account
                </button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="index.php">Back to Shop</a></p>
            </div>
        </div>
    </div>
</body>
</html>