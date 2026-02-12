<?php
include 'config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // In a real app, use password_verify() with hashed passwords
    $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($password === $user['password']) { // Basic check for now
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            
            // Update last login
            $conn->query("UPDATE admin_users SET last_login = NOW() WHERE id = " . $user['id']);
            
            header("Location: admin.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ZifTech Africa</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-color);
        }
        .login-card {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .login-card h2 {
            margin-bottom: 20px;
            text-align: center;
            color: var(--secondary-color);
        }
        .error-msg {
            color: #ff4444;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="light-mode">
    <div class="login-container">
        <div class="login-card">
            <h2>Admin Login</h2>
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            <div style="margin-top: 20px; text-align: center;">
                <a href="index.html" style="color: var(--primary-color);">Back to Site</a>
            </div>
        </div>
    </div>
</body>
</html>
