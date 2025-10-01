<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Issue - Car Rentals</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="form-container">
        <a href="../dashboard.php?table=Issues" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Issues List
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Issue
                </h1>
            </div>

    <?php
    include '../conn.php';

    $tableName = "Issues";
    $pkey = "IssueID";

    $foreignKeys = [
        'RentalID' => 'Rentals'
    ];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fields = [];
        $placeholders = [];
        $types = '';
        $values = [];

        if (isset($_POST['RentalID'])) {
            $rentalID = $_POST['RentalID'];
            $rentalQuery = $conn->prepare("SELECT VehicleID, CustomerID FROM Rentals WHERE RentalID = ?");
            $rentalQuery->bind_param('i', $rentalID);
            $rentalQuery->execute();
            $rentalResult = $rentalQuery->get_result();
            $rentalData = $rentalResult->fetch_assoc();
            
            if ($rentalData) {
                $_POST['VehicleID'] = $rentalData['VehicleID'];
                $_POST['CustomerID'] = $rentalData['CustomerID'];
            }
            $rentalQuery->close();
        }

        $targetDir = '../issues/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if (isset($_FILES['Proof']) && $_FILES['Proof']['error'] == 0) {
            $fileName = basename($_FILES['Proof']["name"]);
            $targetFilePath = $targetDir . time() . "_" . $fileName;
            
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = array("jpg", "jpeg", "png", "gif");
            
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['Proof']["tmp_name"], $targetFilePath)) {
                                        $_POST['Proof'] = 'issues/' . time() . "_" . $fileName;
                } else {
                    echo "<div class='error-message'>";
                    echo "<i class='fas fa-exclamation-circle'></i>";
                    echo "Error uploading file.";
                    echo "</div>";
                    exit();
                }
            } else {
                echo "<div class='error-message'>";
                echo "<i class='fas fa-exclamation-circle'></i>";
                echo "Only JPG, JPEG, PNG, GIF files are allowed.";
                echo "</div>";
                exit();
            }
        }

        $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
        while ($fieldinfo = $fieldResult->fetch_field()) {
            $fieldName = $fieldinfo->name;

            $fields[] = $fieldName;
            $placeholders[] = '?';

            if (strpos($fieldName, 'Date') !== false) {
                $types .= 's';
            } elseif (strpos($fieldName, 'ID') !== false) {
                $types .= 'i';
            } else {
                $types .= 's';
            }

            if (isset($_POST[$fieldName])) {
                $values[] = $_POST[$fieldName];
            } else {
                if ($fieldName == 'DateResolved') {
                    $values[] = '0000-00-00';
                } elseif ($fieldName == 'Status') {
                    $values[] = 'Pending';
                } else {
                    $values[] = '';
                }
            }
        }

        if (!empty($fields)) {
            $sql = "INSERT INTO " . $tableName . " (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                echo "<script>
                        window.location.href = '../dashboard.php?table=" . $tableName . "';
                      </script>";
                exit();
            } else {
                echo "<div class='error-message'>";
                echo "<i class='fas fa-exclamation-circle'></i>";
                echo "Error: " . $stmt->error;
                echo "</div>";
            }

            $stmt->close();
        } else {
            echo "<p style='color:red;'>No fields to insert.</p>";
        }
    }

    echo '<form method="POST" action="" enctype="multipart/form-data">';

    $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
    while ($fieldinfo = $fieldResult->fetch_field()) {
        $label = $fieldinfo->name;

        if ($label == $pkey)
            continue;

        $skipFields = ['DateResolved', 'VehicleID', 'CustomerID'];
        if (in_array($label, $skipFields)) continue;

        echo '<div class="form-group">';
        echo '<label>' . $label . ':</label>';

        $isForeignKey = false;
        $fkTable = '';

        if (isset($foreignKeys[$label])) {
            $isForeignKey = true;
            $fkTable = $foreignKeys[$label];
        }

        if ($isForeignKey) {
            echo '<select name="' . $label . '" required>';
            echo '<option value="">Select ' . ucfirst(str_replace('ID', '', $label)) . '...</option>';
            
            if ($fkTable == 'Rentals') {
                                                $fkQuery = "SELECT r.RentalID, c.Name, v.Model, v.PlateNumber, r.ReturnedDate
                           FROM Rentals r 
                           JOIN Customers c ON r.CustomerID = c.CustomerID 
                           JOIN Vehicles v ON r.VehicleID = v.VehicleID 
                           WHERE (r.ReturnedDate = '0000-00-00' OR r.ReturnedDate IS NULL)
                           ORDER BY c.Name, v.Model";
                $fkResult = $conn->query($fkQuery);
                
                                if (!$fkResult) {
                    echo "<!-- Debug: SQL Error: " . $conn->error . " -->";
                }
                
                
                while ($fkRow = $fkResult->fetch_assoc()) {
                    $customerName = $fkRow['Name'];
                    $displayField = $customerName . ' - ' . $fkRow['Model'] . ' (' . $fkRow['PlateNumber'] . ')';
                    echo '<option value="' . $fkRow['RentalID'] . '">' . htmlspecialchars($displayField) . '</option>';
                }
            } else {
                $fkResult = $conn->query("SELECT * FROM $fkTable");
                while ($fkRow = $fkResult->fetch_assoc()) {
                    $fkId = $fkRow[array_keys($fkRow)[0]];
                    echo '<option value="' . $fkId . '">' . htmlspecialchars($fkId) . '</option>';
                }
            }
            echo '</select>';
        } else {
            if ($label == 'Proof') {
                echo '<label for="proof-upload" class="image-upload" style="cursor: pointer; display: block;">';
                echo '<i class="fas fa-upload" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>';
                echo '<p id="proof-upload-text" style="margin-bottom: 1rem; color: var(--text-secondary);">Click to upload proof image</p>';
                echo '<input type="file" id="proof-upload" name="' . $label . '" accept="image/*" style="display: none;" required>';
                echo '</label>';
                echo '<div id="proof-file-name" style="margin-top: 0.5rem; color: var(--success-color); font-size: 0.875rem; display: none;"></div>';
                echo '</div>';
                continue;
            } elseif ($label == 'Status') {
                echo '<select name="' . $label . '" required>';
                $statusOptions = ['Pending', 'In Progress', 'Resolved', 'Closed'];
                $defaultStatus = 'Pending';
                
                foreach ($statusOptions as $option) {
                    $selected = ($option == $defaultStatus) ? ' selected' : '';
                    echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
                }
                
                echo '</select>';
                echo '</div>';
                continue;
            } elseif ($label == 'Description') {
                echo '<textarea name="' . $label . '" rows="3" minlength="10" maxlength="500" required placeholder="Enter description (at least 10 characters)..."></textarea>';
                echo '</div>';
                continue;
            } elseif (strpos($label, 'Date') !== false) {
                echo '<input type="date" name="' . $label . '" required>';
                echo '</div>';
                continue;
            }

            echo '<input type="text" name="' . $label . '" required>';
        }
        echo '</div>';
    }

    echo '<div class="form-actions">';
    echo '<input type="submit" value="Add Issue" class="btn btn-primary">';
    echo '</div>';
    echo '</form>';
    ?>
        </div>
    </div>
    
    <script>
        document.getElementById('proof-upload').addEventListener('change', function(e) {
            const fileInput = e.target;
            const fileName = fileInput.files[0]?.name;
            const uploadText = document.getElementById('proof-upload-text');
            const fileNameDiv = document.getElementById('proof-file-name');
            
            if (fileName) {
                uploadText.textContent = 'Proof image selected:';
                fileNameDiv.textContent = fileName;
                fileNameDiv.style.display = 'block';
            } else {
                uploadText.textContent = 'Click to upload proof image';
                fileNameDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
