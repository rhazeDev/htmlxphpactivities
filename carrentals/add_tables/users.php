<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

        if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
                $check_stmt = $conn->prepare("SELECT id FROM Users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
                        $stmt = $conn->prepare("INSERT INTO Users (username, password, enabled) VALUES (?, ?, 1)");
            $stmt->bind_param("ss", $username, $password);

            if ($stmt->execute()) {
                $success = 'User added successfully!';
                                $username = $password = $confirm_password = '';
            } else {
                $error = 'Error adding user: ' . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Car Rentals</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="form-container">
        <a href="../dashboard.php?table=Users" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Users List
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </h1>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required minlength="3" maxlength="30"
                           placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="password" name="password" 
                           required minlength="6" maxlength="30"
                           placeholder="Enter password">
                </div>

                <div class="form-group">
                    <label>Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required minlength="6" maxlength="30"
                           placeholder="Confirm password">
                </div>

                <div class="form-actions">
                    <input type="submit" value="Add User" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>

    <script>
                document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>