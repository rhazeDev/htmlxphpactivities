<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("No user ID provided.");
}

$tableName = $_GET['table'];
$id = intval($_GET['id']);

if ($tableName == 'Users') {
    $pkey = 'id';
} else {
    $pkey = substr($tableName, 0, -1) . 'ID';
}

if ($tableName == 'Users' && $id == 1) {
    echo "<p style='color:red;'>Error: Cannot delete the admin user!</p>";
    echo "<a href='dashboard.php?table=".$tableName."'>Back to ".$tableName." List</a>";
    exit;
}

$sql = "DELETE FROM ".$tableName." WHERE ".$pkey." = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<p style='color:green;'>Record deleted successfully!</p>";
    echo "<a href='dashboard.php?table=".$tableName."'>Back to ".$tableName." List</a>";
} else {
    echo "<p style='color:red;'>Error deleting: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
