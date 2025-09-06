<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "onlyfans_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $name = $_POST["name"];
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $sql = "INSERT INTO registers(name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            echo "<h3>Thank you!</h3>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $sql = "INSERT INTO logins(email, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        
        if ($stmt->execute()) {
            echo "<h3>Thank you!</h3>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>


