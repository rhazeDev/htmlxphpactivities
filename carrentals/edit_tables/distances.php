<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$tableName = "Distances";
$id = $_GET['id'] ?? null;
$pkey = "DistanceID";

$foreignKeys = [];

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

    if (isset($_POST['RentalID'])) {
        $rentalID = $_POST['RentalID'];
        $rentalQuery = $conn->prepare("SELECT VehicleID FROM Rentals WHERE RentalID = ?");
        $rentalQuery->bind_param('i', $rentalID);
        $rentalQuery->execute();
        $rentalResult = $rentalQuery->get_result();
        $rentalData = $rentalResult->fetch_assoc();
        
        if ($rentalData) {
            $_POST['VehicleID'] = $rentalData['VehicleID'];
        }
        $rentalQuery->close();
    }

    if (isset($_POST['KmAfter']) && isset($_POST['KmBefore'])) {
        $kmAfter = (int)$_POST['KmAfter'];
        $kmBefore = (int)$_POST['KmBefore'];
        $kmUsed = $kmAfter - $kmBefore;
        $_POST['KmUsed'] = $kmUsed;
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
        } elseif (strpos($label, 'ID') !== false || in_array($label, ['KmBefore', 'KmAfter', 'KmUsed'])) {
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
    <title>Edit Distance - <?php echo ucfirst($tableName); ?></title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function calculateKmUsed() {
            const kmBefore = document.querySelector('input[name="KmBefore"]');
            const kmAfter = document.querySelector('input[name="KmAfter"]');
            const kmUsedDisplay = document.getElementById('calculated-kmused');
            
            if (kmBefore && kmAfter && kmUsedDisplay) {
                const before = parseInt(kmBefore.value) || 0;
                const after = parseInt(kmAfter.value) || 0;
                const used = after - before;
                
                kmUsedDisplay.textContent = used >= 0 ? used : 0;
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
                    Edit Distance Record
                </h1>
            </div>

    <?php
    echo '<form method="POST" action="" enctype="multipart/form-data">';
    foreach ($row as $label => $value) {
        if ($label == $pkey) continue;

        $skipFields = ['KmUsed', 'VehicleID', 'RentalID'];
        if (in_array($label, $skipFields)) {
            if ($label == 'KmUsed') {
                echo '<div class="form-group">';
                echo '<label>Km Used:</label>';
                echo '<div class="cost-display">';
                echo '<div class="cost-value" id="calculated-kmused">' . htmlspecialchars($value) . '</div>';
                echo '<div class="cost-label">kilometers</div>';
                echo '</div>';
                echo '</div>';
            }
            continue;
        }

        echo '<div class="form-group">';
        echo '<label>' . htmlspecialchars($label) . ':</label>';

        $isForeignKey = false;
        $fkTable = '';

        if (isset($foreignKeys[$label])) {
            $isForeignKey = true;
            $fkTable = $foreignKeys[$label];
        }

        if ($isForeignKey) {
            $fkResult = $conn->query("SELECT * FROM $fkTable");

            echo '<select name="' . $label . '" required>';
            
            while ($fkRow = $fkResult->fetch_assoc()) {
                $displayField = '';
                
                if ($fkTable == 'Rentals') {
                    $displayField = 'Rental #' . $fkRow['RentalID'] . ' (' . $fkRow['PickUpDate'] . ' - ' . $fkRow['ToReturnDate'] . ')';
                }

                $fkId = $fkRow[array_keys($fkRow)[0]];
                $selected = ($fkId == $value) ? 'selected' : '';
                echo '<option value="' . $fkId . '" ' . $selected . '>' . htmlspecialchars($displayField) . '</option>';
            }
            echo '</select>';
        } else {
            $inputType = 'text';
            $onChangeEvent = '';
            
            if (in_array($label, ['KmBefore', 'KmAfter'])) {
                $inputType = 'number';
                $onChangeEvent = ' onchange="calculateKmUsed()" oninput="calculateKmUsed()"';
                echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" min="0" required' . $onChangeEvent . '>';
                echo '</div>';
                continue;
            } elseif (stripos($label, 'Date') !== false || $label == 'DateRecorded') {
                $inputType = 'date';
                echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" required>';
                echo '</div>';
                continue;
            }

            echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" required>';
        }
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

    <?php $conn->close(); ?>
</body>
</html>
