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
    <title>Add Vehicle - Car Rentals</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function formatPlateNumber(input) {
            let value = input.value.replace(/[^A-Za-z0-9]/g, '');
            
            if (value.length > 7) {
                value = value.substring(0, 7);
            }
            
            let letters = value.substring(0, 3).replace(/[^A-Za-z]/g, '').toUpperCase();
            
            let numbers = value.substring(3).replace(/[^0-9]/g, '');
            if (numbers.length > 4) {
                numbers = numbers.substring(0, 4);
            }
            
            if (letters.length > 0 && numbers.length > 0) {
                input.value = letters + ' ' + numbers;
            } else if (letters.length > 0) {
                input.value = letters;
            }
        }
        
        function validatePlateNumber(input) {
            const platePattern = /^[A-Z]{3} [0-9]{4}$/;
            const isValid = platePattern.test(input.value);
            
            if (!isValid && input.value.length > 0) {
                input.setCustomValidity('Plate number must be in format: ABC 123 (3 letters, space, 4 numbers)');
            } else {
                input.setCustomValidity('');
            }
        }
        
        function validateDailyRate(input) {
            const rate = parseFloat(input.value);
            if (rate <= 0) {
                input.setCustomValidity('Daily rate must be greater than 0');
            } else if (rate > 50000) {
                input.setCustomValidity('Daily rate seems too high. Please check the amount');
            } else {
                input.setCustomValidity('');
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <a href="../dashboard.php?table=Vehicles" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Vehicles List
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Vehicle
                </h1>
            </div>

    <?php
    include '../conn.php';

    $tableName = "Vehicles";
    $pkey = "VehicleID";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fields = [];
        $placeholders = [];
        $types = '';
        $values = [];

        $targetDir = '../vehicles/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if (isset($_FILES['Image']) && $_FILES['Image']['error'] == 0) {
            $fileName = basename($_FILES['Image']["name"]);
            $targetFilePath = $targetDir . time() . "_" . $fileName;
            
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = array("jpg", "jpeg", "png", "gif");
            
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['Image']["tmp_name"], $targetFilePath)) {
                                        $_POST['Image'] = 'vehicles/' . time() . "_" . $fileName;
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
            } elseif ($fieldName == 'DailyRate') {
                $types .= 'd';
            } elseif (strpos($fieldName, 'ID') !== false) {
                $types .= 'i';
            } else {
                $types .= 's';
            }

            if (isset($_POST[$fieldName])) {
                $values[] = $_POST[$fieldName];
            } else {
                if ($fieldName == 'Status') {
                    $values[] = 'Available';
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

        echo '<div class="form-group">';
        echo '<label>' . $label . ':</label>';

        if ($label == 'Image') {
            echo '<label for="image-upload" class="image-upload" style="cursor: pointer; display: block;">';
            echo '<i class="fas fa-upload" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>';
            echo '<p id="upload-text" style="margin-bottom: 1rem; color: var(--text-secondary);">Click to upload an image</p>';
            echo '<input type="file" id="image-upload" name="' . $label . '" accept="image/*" style="display: none;" required>';
            echo '</label>';
            echo '<div id="file-name" style="margin-top: 0.5rem; color: var(--success-color); font-size: 0.875rem; display: none;"></div>';
            echo '</div>';
            continue;
        }
        
        if ($label == 'Status') {
            echo '<select name="' . $label . '" required>';
            $statusOptions = ['Available', 'Rented', 'Maintenance', 'Out of Service'];
            $defaultStatus = 'Available';
            
            foreach ($statusOptions as $option) {
                $selected = ($option == $defaultStatus) ? ' selected' : '';
                echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
            }
            
            echo '</select>';
            echo '</div>';
            continue;
        }
        
        $inputType = 'text';
        $extraAttributes = '';
        $placeholder = '';
        
        if ($label == 'PlateNumber') {
            echo '<input type="text" name="' . $label . '" maxlength="8" placeholder="ABC 1234" pattern="[A-Z]{3} [0-9]{4}" oninput="formatPlateNumber(this)" onblur="validatePlateNumber(this)" required>';
            echo '</div>';
            continue;
        } elseif ($label == 'DailyRate') {
            $inputType = 'number';
            echo '<input type="' . $inputType . '" name="' . $label . '" step="0.01" min="1" max="50000" oninput="validateDailyRate(this)" onblur="validateDailyRate(this)" required>';
            echo '</div>';
            continue;
        } elseif (stripos($label, 'model') !== false) {
            $placeholder = 'placeholder="Vehicle Model"';
        } elseif (stripos($label, 'brand') !== false) {
            $placeholder = 'placeholder="Vehicle Brand"';
        } elseif (stripos($label, 'year') !== false) {
            $inputType = 'number';
            $extraAttributes = 'min="1990" max="' . (date('Y') + 1) . '"';
            $placeholder = 'placeholder="Year"';
        }

        echo '<input type="' . $inputType . '" name="' . $label . '" ' . $placeholder . ' ' . $extraAttributes . ' required>';
        echo '</div>';
    }

    echo '<div class="form-actions">';
    echo '<input type="submit" value="Add Vehicle" class="btn btn-primary">';
    echo '</div>';
    echo '</form>';
    ?>
        </div>
    </div>
    
    <script>
        document.getElementById('image-upload').addEventListener('change', function(e) {
            const fileInput = e.target;
            const fileName = fileInput.files[0]?.name;
            const uploadText = document.getElementById('upload-text');
            const fileNameDiv = document.getElementById('file-name');
            
            if (fileName) {
                uploadText.textContent = 'Image selected:';
                fileNameDiv.textContent = fileName;
                fileNameDiv.style.display = 'block';
            } else {
                uploadText.textContent = 'Click to upload an image';
                fileNameDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
