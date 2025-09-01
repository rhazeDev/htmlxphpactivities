<style>
    table,
    td,
    th {
        border: 1px solid #212121;
        border-collapse: collapse;
        padding: 5px;
        font-size: 16px;
    }
</style>
<?php
// Database connection settings
$servername = "localhost";
$username = "root";   // default XAMPP username
$password = "";       // default XAMPP password is blank
$dbname = "customer_db"; //db name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $gender = $_POST['gender'] ?? null;
    $color = $_POST['color'];
    $message = $_POST['message'];

    // SQL Insert statement
    $sql = "INSERT INTO customers (fullname, email, age, gender, color, message)
            VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisss", $fullname, $email, $age, $gender, $color, $message);

    if ($stmt->execute()) {
        echo "<h3>Customer information saved successfully!</h3>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Favorite Color</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php
            echo "<td>" . htmlspecialchars($fullname) . "</td>";
            echo "<td>" . htmlspecialchars($email) . "</td>";
            echo "<td>" . htmlspecialchars($age) . "</td>";
            echo "<td>" . htmlspecialchars($gender) . "</td>";
            echo "<td>" . htmlspecialchars($color) . "</td>";
            echo "<td>" . htmlspecialchars($message) . "</td>";
            ?>
        </tr>
    </tbody>
</table>
<br><br>
<a href='form.html'>Back to Form</a>
