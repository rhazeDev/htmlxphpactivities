<!DOCTYPE html>
<html lang="en">

<head>
    <title>Process</title>
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

<body>
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
                $fullname = $_POST["fullname"];
                $email = $_POST["email"];
                $age = $_POST["age"];
                $gender = $_POST["gender"];
                $color = $_POST["color"];
                $message = $_POST["message"];

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
</body>

</html>
