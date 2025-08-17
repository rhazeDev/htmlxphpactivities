<!DOCTYPE html>
<html lang="en">
<head>
    <title>Process</title>
    <style>
        table, td, th {
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
                $name = $_POST["name"];
                $email = $_POST["email"];
                $age = $_POST["age"];
                $gender = $_POST["gender"];
                $fcolor = $_POST["fcolor"];
                $message = $_POST["message"];

                echo "<td>".$name."</td>";
                echo "<td>".$email."</td>";
                echo "<td>".$age."</td>";
                echo "<td>".$gender."</td>";
                echo "<td>".$fcolor."</td>";
                echo "<td>".$message."</td>";
            ?>
        </tr>
    </tbody>
   </table> 
</body>
</html>