<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$tableName = "Payments";
$id = $_GET['id'] ?? null;
$pkey = "PaymentID";

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
        } elseif (in_array($label, ['Amount', 'DueAmount'])) {
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
    <title>Edit Payment - <?php echo ucfirst($tableName); ?></title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function validatePaymentAmount(input) {
            const amount = parseFloat(input.value);
            if (amount <= 0) {
                input.setCustomValidity('Amount must be greater than 0');
            } else if (amount > 1000000) {
                input.setCustomValidity('Amount seems too high. Please check the amount');
            } else {
                input.setCustomValidity('');
            }
        }
        
        function validateDueAmount(input) {
            const amount = parseFloat(input.value);
            if (amount < 0) {
                input.setCustomValidity('Due amount cannot be negative');
            } else if (amount > 1000000) {
                input.setCustomValidity('Amount seems too high. Please check the amount');
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
                    Edit Payment Record
                </h1>
            </div>

    <?php
    echo '<form method="POST" action="" enctype="multipart/form-data">';
    foreach ($row as $label => $value) {
        if ($label == $pkey || $label == 'RentalID') continue;

        echo '<div class="form-group">';
        echo '<label>' . htmlspecialchars($label) . ':</label>';

        $isForeignKey = false;
        $fkTable = '';

        if (isset($foreignKeys[$label])) {
            $isForeignKey = true;
            $fkTable = $foreignKeys[$label];
        }

        if ($isForeignKey) {
            $currentRentalId = $value ?? 0;
            $fkResult = $conn->query("SELECT * FROM $fkTable WHERE RentalID NOT IN (SELECT RentalID FROM Payments WHERE RentalID IS NOT NULL AND RentalID != $currentRentalId) OR RentalID = $currentRentalId");

            echo '<select name="' . $label . '" required>';
            
            while ($fkRow = $fkResult->fetch_assoc()) {
                $totalCost = $fkRow['TotalCost'];
                $dueAmount = 0;
                
                if (!empty($fkRow['ReturnedDate'])) {
                    $returnedDate = new DateTime($fkRow['ReturnedDate']);
                    $toReturnDate = new DateTime($fkRow['ToReturnDate']);
                    
                    if ($returnedDate > $toReturnDate) {
                        $lateDays = $returnedDate->diff($toReturnDate)->days;
                        $dailyRate = $fkRow['DailyRate'];
                        $dueAmount = $lateDays * ($dailyRate * 0.5);
                    }
                }
                
                $finalTotal = $totalCost + $dueAmount;
                $displayField = 'Rental #' . $fkRow['RentalID'] . ' (' . $fkRow['PickUpDate'] . ' - ' . $fkRow['ToReturnDate'] . ') - ₱' . number_format($finalTotal, 2);

                $fkId = $fkRow[array_keys($fkRow)[0]];
                $selected = ($fkId == $value) ? 'selected' : '';
                echo '<option value="' . $fkId . '" ' . $selected . '>' . htmlspecialchars($displayField) . '</option>';
            }
            echo '</select>';
        } else {
            if ($label == 'Amount') {
                echo '<input type="number" name="' . $label . '" value="' . htmlspecialchars($value) . '" min="0.01" step="0.01" oninput="validatePaymentAmount(this)" onblur="validatePaymentAmount(this)" required>';
                echo '</div>';
                continue;
            } elseif ($label == 'DueAmount') {
                echo '<input type="number" name="' . $label . '" value="' . htmlspecialchars($value) . '" min="0" step="0.01" oninput="validateDueAmount(this)" onblur="validateDueAmount(this)">';
                echo '</div>';
                continue;
            } elseif ($label == 'Status') {
                echo '<select name="' . $label . '" required>';
                
                $statusOptions = ['Paid', 'Partial', 'Unpaid'];
                
                foreach ($statusOptions as $option) {
                    $selected = ($option == $value) ? ' selected' : '';
                    echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
                }
                
                echo '</select>';
                echo '<div class="field-note" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">';
                echo '<i class="fas fa-info-circle"></i> Manual status override allowed. Your selection will be saved regardless of additional amounts.';
                echo '</div>';
                echo '</div>';
                continue;
            } elseif (strpos($label, 'Date') !== false) {
                echo '<input type="date" name="' . $label . '" value="' . htmlspecialchars($value) . '" required>';
                echo '</div>';
                continue;
            }

            echo '<input type="text" name="' . $label . '" value="' . htmlspecialchars($value) . '" required>';
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
