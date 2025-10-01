<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';
$user_id = $_GET['id'] ?? 0;

if (isset($_GET['action']) && in_array($_GET['action'], ['enable', 'disable'])) {
    $action = $_GET['action'];
    
        if ($user_id == 1 && $action == 'disable') {
        header("Location: ../dashboard.php?table=Users&error=Cannot disable admin user");
        exit;
    }
    
    $enabled = ($action == 'enable') ? 1 : 0;
    $stmt = $conn->prepare("UPDATE Users SET enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $enabled, $user_id);
    
    if ($stmt->execute()) {
        $action_text = ($action == 'enable') ? 'enabled' : 'disabled';
        header("Location: ../dashboard.php?table=Users&success=User $action_text successfully");
    } else {
        header("Location: ../dashboard.php?table=Users&error=Failed to $action user");
    }
    $stmt->close();
    exit;
}

$stmt = $conn->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../dashboard.php?table=Users&error=User not found");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $update_password = !empty($new_password);

        if (empty($username)) {
        $error = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif ($update_password && strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($update_password && $new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
                $check_stmt = $conn->prepare("SELECT id FROM Users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $username, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
                        if ($update_password) {
                $update_stmt = $conn->prepare("UPDATE Users SET username = ?, password = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $username, $new_password, $user_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE Users SET username = ? WHERE id = ?");
                $update_stmt->bind_param("si", $username, $user_id);
            }

            if ($update_stmt->execute()) {
                $success = 'User updated successfully!';
                $user['username'] = $username;
                if ($update_password) {
                    $user['password'] = $new_password;
                }
            } else {
                $error = 'Error updating user: ' . $conn->error;
            }
            $update_stmt->close();
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
    <title>Edit User - Car Rentals</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2>
                <i class="fas fa-user-edit"></i>
                Edit User: <?php echo htmlspecialchars($user['username']); ?>
                <?php if ($user_id == 1): ?>
                    <span class="admin-badge" style="background-color: #7c3aed; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-left: 0.5rem;">
                        <i class="fas fa-crown"></i> ADMIN
                    </span>
                <?php endif; ?>
            </h2>
            <a href="../dashboard.php?table=Users" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Users
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username *
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required minlength="3" maxlength="30"
                           placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-info-circle"></i>
                        User Status
                    </label>
                    <div style="padding: 0.75rem; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.375rem;">
                        <?php if ($user['enabled']): ?>
                            <span class="status-badge status-active">
                                <i class="fas fa-check-circle"></i> Enabled
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-inactive">
                                <i class="fas fa-times-circle"></i> Disabled
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($user_id != 1): ?>
                            <div style="margin-top: 0.5rem;">
                                <?php if ($user['enabled']): ?>
                                    <a href="?action=disable&id=<?php echo $user_id; ?>" 
                                       onclick="return confirm('Are you sure you want to disable this user?')"
                                       class="disable-btn" style="background-color: #dc2626; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; text-decoration: none; font-size: 0.875rem;">
                                        <i class="fas fa-ban"></i> Disable User
                                    </a>
                                <?php else: ?>
                                    <a href="?action=enable&id=<?php echo $user_id; ?>" 
                                       class="enable-btn" style="background-color: #059669; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; text-decoration: none; font-size: 0.875rem;">
                                        <i class="fas fa-check"></i> Enable User
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p style="margin-top: 0.5rem; color: #7c3aed; font-size: 0.875rem;">
                                <i class="fas fa-info-circle"></i> Admin user cannot be disabled
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-key"></i> Change Password (Optional)</h3>
                <p class="form-help">Leave password fields empty if you don't want to change the password.</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i>
                            New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" 
                               minlength="6" maxlength="30"
                               placeholder="Enter new password (optional)">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Confirm New Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               minlength="6" maxlength="30"
                               placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-button">
                    <i class="fas fa-save"></i>
                    Update User
                </button>
                <a href="../dashboard.php?table=Users" class="cancel-button">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
                document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });

                document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (this.value) {
                confirmPassword.setAttribute('required', 'required');
            } else {
                confirmPassword.removeAttribute('required');
            }
        });
    </script>
</body>
</html>