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
    <title>Add Distance - Car Rentals</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function calcKmUsed() {
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
        <a href="../dashboard.php?table=Distances" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Distances List
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Distance Record
                </h1>
            </div>

    <?php
    include '../conn.php';

    $tableName = "Distances";
    $pkey = "DistanceID";

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

        $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
        while ($fieldinfo = $fieldResult->fetch_field()) {
            $fieldName = $fieldinfo->name;

            $fields[] = $fieldName;
            $placeholders[] = '?';

            if (strpos($fieldName, 'Date') !== false) {
                $types .= 's';
            } elseif (strpos($fieldName, 'ID') !== false || in_array($fieldName, ['KmBefore', 'KmAfter', 'KmUsed'])) {
                $types .= 'i';
            } else {
                $types .= 's';
            }

            if (isset($_POST[$fieldName])) {
                $values[] = $_POST[$fieldName];
            } else {
                if ($fieldName == 'KmAfter') {
                    $values[] = 0;
                } elseif ($fieldName == 'KmUsed') {
                    $values[] = 0;
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

        $skipFields = ['KmAfter', 'KmUsed', 'VehicleID'];
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
            $fkResult = $conn->query("SELECT * FROM $fkTable");

            echo '<select name="' . $label . '" required>';
            echo '<option value="">Select ' . ucfirst(str_replace('ID', '', $label)) . '...</option>';
            
            while ($fkRow = $fkResult->fetch_assoc()) {
                $displayField = '';
                
                if ($fkTable == 'Rentals') {
                    $displayField = 'Rental #' . $fkRow['RentalID'] . ' (' . $fkRow['PickUpDate'] . ' - ' . $fkRow['ToReturnDate'] . ')';
                }

                $fkId = $fkRow[array_keys($fkRow)[0]];
                echo '<option value="' . $fkId . '">' . htmlspecialchars($displayField) . '</option>';
            }
            echo '</select>';
        } else {
            $inputType = 'text';
            $onChangeEvent = '';
            
            if ($label == 'KmBefore') {
                $inputType = 'number';
                $onChangeEvent = ' onchange="calcKmUsed()" oninput="calcKmUsed()"';
                echo '<input type="' . $inputType . '" name="' . $label . '" min="0" placeholder="Enter kilometers before" required' . $onChangeEvent . '>';
                echo '</div>';
                continue;
            } elseif (stripos($label, 'Date') !== false || $label == 'DateRecorded') {
                $inputType = 'date';
                echo '<input type="' . $inputType . '" name="' . $label . '" required>';
                echo '</div>';
                continue;
            }

            echo '<input type="' . $inputType . '" name="' . $label . '" required>';
        }
        echo '</div>';
    }

    echo '<div class="form-group">';
    echo '<label>KmAfter:</label>';
    echo '<input type="number" name="KmAfter" min="0" placeholder="Enter kilometers after" onchange="calcKmUsed()" oninput="calcKmUsed()">';
    echo '</div>';

    echo '<div class="form-group">';
    echo '<label>KmUsed:</label>';
    echo '<div class="cost-display">';
    echo '<div class="cost-value" id="calculated-kmused">0</div>';
    echo '<div class="cost-label">kilometers</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="form-actions">';
    echo '<input type="submit" value="Add Distance Record" class="btn btn-primary">';
    echo '</div>';
    echo '</form>';
    ?>
        </div>
    </div>
</body>
</html>
