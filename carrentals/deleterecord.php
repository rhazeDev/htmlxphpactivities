<?php
// Include database connection
include 'conn.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    die("No user ID provided.");
}

// Delete query
$tableName = $_GET['table'];
$id = intval($_GET['id']);
$pkey = substr($tableName, 0, -1) . 'ID';

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
