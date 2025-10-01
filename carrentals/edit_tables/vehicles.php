<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$tableName = "Vehicles";
$id = $_GET['id'] ?? null;
$pkey = "VehicleID";

if (!$id) {
    die('No record ID provided.');
}

$sql = "SELECT * FROM $tableName WHERE $pkey = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    die('Record not found.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = [];
    $types = '';
    $values = [];

    $targetDir = '../vehicles/';
    
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
            }
        } else {
            echo "<div class='error-message'>";
            echo "<i class='fas fa-exclamation-circle'></i>";
            echo "Only JPG, JPEG, PNG, GIF files are allowed.";
            echo "</div>";
        }
    } else {
        $_POST['Image'] = $row['Image'];
    }

    foreach ($row as $fieldName => $currentValue) {
        if ($fieldName != $pkey && !isset($_POST[$fieldName])) {
            $_POST[$fieldName] = $currentValue;
        }
    }

    foreach ($row as $label => $value) {
        if ($label == $pkey) continue;

        $fields[] = "$label = ?";

        if (strpos($label, 'Date') !== false) {
            $types .= 's';
        } elseif ($label == 'DailyRate') {
            $types .= 'd';
        } elseif (strpos($label, 'ID') !== false) {
            $types .= 'i';
        } else {
            $types .= 's';
        }

        $values[] = $_POST[$label];
    }

    $sql = "UPDATE $tableName SET " . implode(", ", $fields) . " WHERE $pkey = ?";
    $types .= 'i';
    $values[] = $id;

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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle - <?php echo ucfirst($tableName); ?></title>
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
                input.setCustomValidity('Plate number must be in format: ABC 1234 (3 letters, space, 4 numbers)');
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
        <a href="../dashboard.php?table=<?php echo $tableName; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to <?php echo ucfirst($tableName); ?> List
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-edit"></i>
                    Edit Vehicle Record
                </h1>
            </div>

    <?php
    echo '<form method="POST" action="" enctype="multipart/form-data">';
    foreach ($row as $label => $value) {
        if ($label == $pkey) continue;

        echo '<div class="form-group">';
        echo '<label>' . htmlspecialchars($label) . ':</label>';

        if ($label == 'Image') {
            if (!empty($value) && file_exists($value)) {
                echo '<div class="current-image" style="margin-bottom: 1rem;">';
                echo '<img src="' . htmlspecialchars($value) . '" alt="Current Image">';
                echo '<p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Current image</p>';
                echo '</div>';
            }
            echo '<label for="image-upload-edit" class="image-upload" style="cursor: pointer; display: block;">';
            echo '<i class="fas fa-upload" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>';
            echo '<p id="edit-upload-text" style="margin-bottom: 1rem; color: var(--text-secondary);">Click to upload a new image</p>';
            echo '<input type="file" id="image-upload-edit" name="' . $label . '" accept="image/*" style="display: none;">';
            echo '</label>';
            echo '<div id="edit-file-name" style="margin-top: 0.5rem; color: var(--success-color); font-size: 0.875rem; display: none;"></div>';
            echo '<p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Leave empty to keep current image</p>';
            echo '</div>';
            continue;
        }
        
        if ($label == 'Status') {
            echo '<select name="' . $label . '" required>';
            $statusOptions = ['Available', 'Rented', 'Maintenance', 'Out of Service'];
            
            foreach ($statusOptions as $option) {
                $selected = ($option == $value) ? ' selected' : '';
                echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
            }
            
            echo '</select>';
            echo '</div>';
            continue;
        }
        
        $inputType = 'text';
        
        if ($label == 'PlateNumber') {
            echo '<input type="text" name="' . $label . '" value="' . htmlspecialchars($value) . '" maxlength="8" pattern="[A-Z]{3} [0-9]{4}" oninput="formatPlateNumber(this)" onblur="validatePlateNumber(this)" required>';
            echo '</div>';
            continue;
        } elseif ($label == 'DailyRate') {
            echo '<input type="number" name="' . $label . '" value="' . htmlspecialchars($value) . '" step="0.01" min="1" max="50000" oninput="validateDailyRate(this)" onblur="validateDailyRate(this)" required>';
            echo '</div>';
            continue;
        } elseif (stripos($label, 'year') !== false) {
            $inputType = 'number';
            echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" min="1990" max="' . (date('Y') + 1) . '" required>';
            echo '</div>';
            continue;
        }

        echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" required>';
        echo '</div>';
    }

    echo '<div class="form-actions">';
    echo '<button type="submit" class="btn btn-primary">';
    echo '<i class="fas fa-save"></i>';
    echo 'Save Changes';
    echo '</button>';
    echo '</div>';
    echo '</form>';
    ?>
        </div>
    </div>

    <script>
        document.getElementById('image-upload-edit').addEventListener('change', function(e) {
            const fileInput = e.target;
            const fileName = fileInput.files[0]?.name;
            const uploadText = document.getElementById('edit-upload-text');
            const fileNameDiv = document.getElementById('edit-file-name');
            
            if (fileName) {
                uploadText.textContent = 'New image selected:';
                fileNameDiv.textContent = fileName;
                fileNameDiv.style.display = 'block';
            } else {
                uploadText.textContent = 'Click to upload a new image';
                fileNameDiv.style.display = 'none';
            }
        });
    </script>

    <?php $conn->close(); ?>
</body>
</html>
