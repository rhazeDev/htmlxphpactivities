<!DOCTYPE html>
<html>
<head>
    <title>Car Rentals Dashboard</title>
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <h2>Car Rentals Management System</h2>
    
    <div class="navigation">
        <button id="vehicles-data" class="nav-button">Vehicles</button>
        <button id="customers-data" class="nav-button">Customers</button>
        <button id="rentals-data" class="nav-button">Rentals</button>
        <button id="issues-data" class="nav-button">Issues</button>
        <button id="distance-data" class="nav-button">Distances</button>
    </div>

<script src="get_tb_name.js"></script>
<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tableName = $_GET['table'] ?? "rentals";

$sql = "SELECT * FROM " . $tableName;
$result = $conn->query($sql);

echo "<h2>Welcome, " . htmlspecialchars($_SESSION['username']) . "!</h2>";
echo "<a href='addrecord.php?table=" . $tableName . "'>➕ Add " . substr($tableName, 0, -1) . "</a> | ";
echo "<a href='logout.php' style='color:red;'>🚪 Logout</a><br><br>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr>";
    
    while ($fieldinfo = $result->fetch_field()) {
        echo "<th>" . $fieldinfo->name . "</th>";
    }
    echo "<th>ACTIONS</th>";
    
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $fieldName => $data) {
            if (($tableName == 'Vehicles' && $fieldName == 'Image') || 
                ($tableName == 'Customers' && $fieldName == 'LicenseImg') || 
                ($tableName == 'Issues' && $fieldName == 'Proof')) {
                
                if (!empty($data) && file_exists($data)) {
                    echo "<td><img src='" . htmlspecialchars($data) . "' alt='Image' style='width:80px; height:80px; object-fit:cover; border-radius:5px;'></td>";
                } else {
                    echo "<td>No Image</td>";
                }
            } else {
                echo "<td>" . htmlspecialchars($data) . "</td>";
            }
        }
        
        // Example buttons for actions (Edit/Delete)
        // <a href='deleterecord.php?table=" . $tableName . "&id=" . $row[$pkey] . "' onclick=\"return confirm('Are you sure?');\">Delete</a>
        $pkey = substr($tableName, 0, -1) . 'ID';
        echo "<td class='actions'>
                <a href='editrecord.php?table=" . $tableName . "&id=" . $row[$pkey] . "'>Edit</a>  
              </td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No records found in " . $tableName . ".";
}

$conn->close();
?>

</body>
</html>
