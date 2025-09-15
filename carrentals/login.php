<?php
session_start();
include 'conn.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT ID, username FROM users WHERE username = ? AND password = ?";
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
<html>

<head>
    <title>Login</title>
    <link href="styles.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="inner-container">
            <h3>User Login</h3>
            <?php if (!empty($message))
                echo "<p style='color:red;'>$message</p>"; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" id="username" name="username" required>
                    <label for="username">Username</label>
                </div>


               <div class="input-group">
                    <input type="password" id="password" name="password" required>
                    <label for="password">Password</label>
                </div>


                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</body>

</html>