<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$tableName = "Customers";
$id = $_GET['id'] ?? null;
$pkey = "CustomerID";

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

    $targetDir = '../profiles/';
    
    if (isset($_FILES['LicenseImg']) && $_FILES['LicenseImg']['error'] == 0) {
        $fileName = basename($_FILES['LicenseImg']["name"]);
        $targetFilePath = $targetDir . time() . "_" . $fileName;
        
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = array("jpg", "jpeg", "png", "gif");
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['LicenseImg']["tmp_name"], $targetFilePath)) {
                                $_POST['LicenseImg'] = 'profiles/' . time() . "_" . $fileName;
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
        $_POST['LicenseImg'] = $row['LicenseImg'];
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
        } elseif (strpos($label, 'ID') !== false || $label == 'Age') {
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
        $returnPage = isset($_GET['return']) ? $_GET['return'] : 'customers';
        if ($returnPage === 'rentals') {
            echo "<script>
                    window.location.href = '../add_tables/rentals.php';
                  </script>";
        } else {
            echo "<script>
                    window.location.href = '../dashboard.php?table=" . $tableName . "';
                  </script>";
        }
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
    <title>Edit Customer - <?php echo ucfirst($tableName); ?></title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function formatContactNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            if (value.length >= 4) {
                if (value.length >= 7) {
                    value = value.substring(0, 4) + '-' + value.substring(4, 7) + '-' + value.substring(7);
                } else {
                    value = value.substring(0, 4) + '-' + value.substring(4);
                }
            }
            input.value = value;
        }
        
        function validateContactNumber(input) {
            const phonePattern = /^09\d{2}-\d{3}-\d{4}$/;
            const rawNumber = input.value.replace(/\D/g, '');
            
            if (rawNumber.length !== 11) {
                input.setCustomValidity('Contact number must be exactly 11 digits');
            } else if (!rawNumber.startsWith('09')) {
                input.setCustomValidity('Contact number must start with 09');
            } else if (!phonePattern.test(input.value)) {
                input.setCustomValidity('Contact number format: 09XX-XXX-XXXX');
            } else {
                input.setCustomValidity('');
            }
        }
        
        function validateLicenseNumber(input) {
            let value = input.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            if (value.length >= 3) {
                if (value.length >= 5) {
                    value = value.substring(0, 3) + '-' + value.substring(3, 5) + '-' + value.substring(5);
                } else {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                }
            }
            input.value = value;
            
            const licensePattern = /^[A-Z0-9]{3}-[A-Z0-9]{2}-[A-Z0-9]{6}$/;
            if (value.length > 0 && !licensePattern.test(value)) {
                input.setCustomValidity('License number format: XXX-XX-XXXXXX (letters and numbers)');
            } else if (value.replace(/-/g, '').length < 10) {
                input.setCustomValidity('License number must be at least 10 characters');
            } else {
                input.setCustomValidity('');
            }
        }
        
        function validateEmail(input) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (input.value.length > 0 && !emailPattern.test(input.value)) {
                input.setCustomValidity('Please enter a valid email address');
            } else {
                input.setCustomValidity('');
            }
        }
        
        function validateName(input) {
            const namePattern = /^[A-Za-z\s\-\.]+$/;
            if (input.value.length > 0 && !namePattern.test(input.value)) {
                input.setCustomValidity('Name can only contain letters, spaces, hyphens, and periods');
            } else if (input.value.trim().length < 2) {
                input.setCustomValidity('Name must be at least 2 characters long');
            } else {
                input.setCustomValidity('');
            }
        }
        
        function validateAge(input) {
            const age = parseInt(input.value);
            if (age < 18) {
                input.setCustomValidity('Customer must be at least 18 years old');
            } else if (age > 100) {
                input.setCustomValidity('Please enter a valid age');
            } else {
                input.setCustomValidity('');
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <?php 
        $returnPage = isset($_GET['return']) ? $_GET['return'] : 'customers';
        if ($returnPage === 'rentals') {
            echo '<a href="../add_tables/rentals.php" class="back-link">';
            echo '<i class="fas fa-arrow-left"></i>';
            echo 'Back to Add Rental';
            echo '</a>';
        } else {
            echo '<a href="../dashboard.php?table=' . $tableName . '" class="back-link">';
            echo '<i class="fas fa-arrow-left"></i>';
            echo 'Back to ' . ucfirst($tableName) . ' List';
            echo '</a>';
        }
        ?>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-edit"></i>
                    Edit Customer Record
                </h1>
            </div>

    <?php
    echo '<form method="POST" action="" enctype="multipart/form-data">';
    foreach ($row as $label => $value) {
        if ($label == $pkey) continue;

        echo '<div class="form-group">';
        echo '<label>' . htmlspecialchars($label) . ':</label>';

        if ($label == 'LicenseImg') {
            if (!empty($value) && file_exists($value)) {
                echo '<div class="current-image" style="margin-bottom: 1rem;">';
                echo '<img src="' . htmlspecialchars($value) . '" alt="Current License Image">';
                echo '<p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Current license image</p>';
                echo '</div>';
            }
            echo '<label for="license-upload-edit" class="image-upload" style="cursor: pointer; display: block;">';
            echo '<i class="fas fa-upload" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>';
            echo '<p id="license-edit-upload-text" style="margin-bottom: 1rem; color: var(--text-secondary);">Click to upload a new license image</p>';
            echo '<input type="file" id="license-upload-edit" name="' . $label . '" accept="image/*" style="display: none;">';
            echo '</label>';
            echo '<div id="license-edit-file-name" style="margin-top: 0.5rem; color: var(--success-color); font-size: 0.875rem; display: none;"></div>';
            echo '<p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Leave empty to keep current image</p>';
            echo '</div>';
            continue;
        }
        
        $inputType = 'text';
        
        if (stripos($label, 'contact') !== false || stripos($label, 'phone') !== false || stripos($label, 'mobile') !== false) {
            echo '<input type="text" name="' . $label . '" value="' . htmlspecialchars($value) . '" maxlength="13" oninput="formatContactNumber(this)" onblur="validateContactNumber(this)" required>';
            echo '</div>';
            continue;
        } elseif (stripos($label, 'license') !== false && stripos($label, 'number') !== false) {
            echo '<input type="text" name="' . $label . '" value="' . htmlspecialchars($value) . '" maxlength="13" oninput="validateLicenseNumber(this)" onblur="validateLicenseNumber(this)" required>';
            echo '</div>';
            continue;
        } elseif (stripos($label, 'email') !== false) {
            echo '<input type="email" name="' . $label . '" value="' . htmlspecialchars($value) . '" oninput="validateEmail(this)" onblur="validateEmail(this)" required>';
            echo '</div>';
            continue;
        } elseif (stripos($label, 'name') !== false) {
            echo '<input type="text" name="' . $label . '" value="' . htmlspecialchars($value) . '" oninput="validateName(this)" onblur="validateName(this)" required>';
            echo '</div>';
            continue;
        } elseif (stripos($label, 'age') !== false) {
            echo '<input type="number" name="' . $label . '" value="' . htmlspecialchars($value) . '" min="18" max="100" oninput="validateAge(this)" onblur="validateAge(this)" required>';
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
        document.getElementById('license-upload-edit').addEventListener('change', function(e) {
            const fileInput = e.target;
            const fileName = fileInput.files[0]?.name;
            const uploadText = document.getElementById('license-edit-upload-text');
            const fileNameDiv = document.getElementById('license-edit-file-name');
            
            if (fileName) {
                uploadText.textContent = 'New license image selected:';
                fileNameDiv.textContent = fileName;
                fileNameDiv.style.display = 'block';
            } else {
                uploadText.textContent = 'Click to upload a new license image';
                fileNameDiv.style.display = 'none';
            }
        });
    </script>

    <?php $conn->close(); ?>
</body>
</html>
