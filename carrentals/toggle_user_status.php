<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: dashboard.php?table=Users&error=Invalid parameters");
    exit;
}

$userId = intval($_GET['id']);
$newStatus = intval($_GET['status']);

if ($userId == 1) {
    header("Location: dashboard.php?table=Users&error=Cannot modify admin user status");
    exit;
}

if ($newStatus !== 0 && $newStatus !== 1) {
    header("Location: dashboard.php?table=Users&error=Invalid status value");
    exit;
}

$stmt = $conn->prepare("UPDATE Users SET enabled = ? WHERE id = ?");
$stmt->bind_param("ii", $newStatus, $userId);

if ($stmt->execute()) {
    $statusText = $newStatus ? 'enabled' : 'disabled';
    header("Location: dashboard.php?table=Users&success=User $statusText successfully");
} else {
    header("Location: dashboard.php?table=Users&error=Failed to update user status");
}

$stmt->close();
$conn->close();
?>