<?php
session_start();
include 'conn.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT ID, username FROM users WHERE username = ? AND password = ? AND enabled = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['username'];

            header("Location: dashboard.php?table=Rentals");
            exit;
        } else {
            $message = "❌ Invalid username or password.";
        }

        $stmt->close();
    } else {
        $message = "⚠️ Please enter both username and password.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Car Rentals</title>
    <link href="styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">
                    <i class="fas fa-car" style="color: var(--primary-color);"></i>
                    Car Rentals
                </h1>
                <p class="login-subtitle">Sign in administrator</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" id="username" name="username" required>
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                </div>

                <div class="input-group">
                    <input type="password" id="password" name="password" required>
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                </div>

                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    Log In
                </button>
            </form>
        </div>
    </div>
</body>
</html>