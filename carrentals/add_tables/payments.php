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
    <title>Add Payment - Car Rentals</title>
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
        
        function showRentalCostInfo(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            
            const existingInfo = document.getElementById('rental-cost-info');
            if (existingInfo) {
                existingInfo.remove();
            }
            
            if (selectedOption.value) {
                const totalCost = parseFloat(selectedOption.dataset.totalCost) || 0;
                const dueAmount = parseFloat(selectedOption.dataset.additional) || 0;
                const finalTotal = parseFloat(selectedOption.dataset.finalTotal) || 0;
                
                const amountField = document.querySelector('input[name="Amount"]');
                if (amountField) {
                    amountField.value = totalCost.toFixed(2);
                }
                
                const dueField = document.querySelector('input[name="DueAmount"]');
                if (dueField) {
                    dueField.value = dueAmount.toFixed(2);
                }
                
                const costInfoDiv = document.createElement('div');
                costInfoDiv.id = 'rental-cost-info';
                costInfoDiv.style.cssText = 'margin-top: 1rem; padding: 1rem; background-color: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 4px;';
                
                let infoHTML = '<h4 style="margin: 0 0 0.5rem 0; color: #0369a1;"><i class="fas fa-calculator"></i> Payment Breakdown</h4>';
                infoHTML += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.875rem;">';
                infoHTML += '<div><strong>Base Amount:</strong> ₱' + totalCost.toFixed(2) + '</div>';
                infoHTML += '<div><strong>Due Amount:</strong> ₱' + dueAmount.toFixed(2) + '</div>';
                infoHTML += '</div>';
                infoHTML += '<div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #bae6fd; font-weight: bold; color: #0369a1;">';
                infoHTML += '<strong>Total Amount to Collect: ₱' + finalTotal.toFixed(2) + '</strong>';
                infoHTML += '</div>';
                
                if (dueAmount > 0) {
                    infoHTML += '<div style="margin-top: 0.5rem; color: #dc2626; font-size: 0.875rem;"><i class="fas fa-exclamation-triangle"></i> Late return charges included</div>';
                }
                
                costInfoDiv.innerHTML = infoHTML;
                
                selectElement.parentNode.insertAdjacentElement('afterend', costInfoDiv);
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <a href="../dashboard.php?table=Payments" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Payments List
        </a>
        
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Payment
                </h1>
            </div>

    <?php
    include '../conn.php';

    $tableName = "Payments";
    $pkey = "PaymentID";

    $foreignKeys = [
        'RentalID' => 'Rentals'
    ];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fields = [];
        $placeholders = [];
        $types = '';
        $values = [];

        $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
        while ($fieldinfo = $fieldResult->fetch_field()) {
            $fieldName = $fieldinfo->name;

            $fields[] = $fieldName;
            $placeholders[] = '?';

            if (strpos($fieldName, 'Date') !== false) {
                $types .= 's';
            } elseif (in_array($fieldName, ['Amount', 'DueAmount'])) {
                $types .= 'd';
            } elseif (strpos($fieldName, 'ID') !== false) {
                $types .= 'i';
            } else {
                $types .= 's';
            }

            if (isset($_POST[$fieldName])) {
                                if ($fieldName == 'Status') {
                                                            $values[] = $_POST[$fieldName];
                } else {
                    $values[] = $_POST[$fieldName];
                }
            } else {
                $values[] = '';
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

        $isForeignKey = false;
        $fkTable = '';

        if (isset($foreignKeys[$label])) {
            $isForeignKey = true;
            $fkTable = $foreignKeys[$label];
        }

        if ($isForeignKey) {
            $fkResult = $conn->query("SELECT * FROM $fkTable WHERE RentalID NOT IN (SELECT RentalID FROM Payments WHERE RentalID IS NOT NULL)");

            $selectEvent = '';
            if ($label == 'RentalID') {
                $selectEvent = ' onchange="showRentalCostInfo(this)"';
            }

            echo '<select name="' . $label . '" required' . $selectEvent . '>';
            echo '<option value="">Select ' . ucfirst(str_replace('ID', '', $label)) . '...</option>';
            
            if ($fkResult->num_rows == 0) {
                echo '<option value="" disabled>No rentals available for payment (all rentals already have payments)</option>';
                echo '</select>';
                echo '<div class="no-rentals-message" style="margin-top: 0.5rem; padding: 0.75rem; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; color: #92400e;">';
                echo '<i class="fas fa-info-circle"></i> ';
                echo '<strong>No Rentals Available:</strong> All existing rentals already have payment records. Please ensure there are completed rentals without payments before adding a new payment.';
                echo '</div>';
            } else {
                while ($fkRow = $fkResult->fetch_assoc()) {
                $totalCost = $fkRow['TotalCost'];
                $dueAmount = 0;                    if (!empty($fkRow['ReturnedDate'])) {
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
                    
                    $dataAttributes = ' data-total-cost="' . $totalCost . '" data-additional="' . $dueAmount . '" data-final-total="' . $finalTotal . '"';

                    $fkId = $fkRow[array_keys($fkRow)[0]];
                    echo '<option value="' . $fkId . '"' . $dataAttributes . '>' . htmlspecialchars($displayField) . '</option>';
                }
            }
            echo '</select>';
        } else {
            if ($label == 'Method') {
                echo '<select name="' . $label . '" required>';
                
                $methodOptions = ['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'GCash', 'PayMaya', 'Online Banking'];
                $defaultMethod = 'Cash';
                
                foreach ($methodOptions as $option) {
                    $selected = ($option == $defaultMethod) ? ' selected' : '';
                    echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
                }
                
                echo '</select>';
                echo '</div>';
                continue;
            } elseif ($label == 'Amount') {
                echo '<input type="number" name="' . $label . '" min="0.01" step="0.01" placeholder="0.00" oninput="validatePaymentAmount(this)" onblur="validatePaymentAmount(this)" required>';
                echo '</div>';
                continue;
            } elseif ($label == 'DueAmount') {
                echo '<input type="number" name="' . $label . '" min="0" step="0.01" placeholder="0.00" oninput="validateDueAmount(this)" onblur="validateDueAmount(this)">';
                echo '</div>';
                continue;
            } elseif ($label == 'Status') {
                echo '<select name="' . $label . '" required>';
                
                $statusOptions = ['Paid', 'Unpaid'];
                $defaultStatus = 'Paid';
                
                foreach ($statusOptions as $option) {
                    $selected = ($option == $defaultStatus) ? ' selected' : '';
                    echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
                }
                
                echo '</select>';
                echo '<div class="field-note" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">';
                echo '<i class="fas fa-info-circle"></i> Select payment status manually. Your selection will be saved as-is.';
                echo '</div>';
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
    echo '<input type="submit" value="Add Payment" class="btn btn-primary">';
    echo '</div>';
    echo '</form>';
    ?>
        </div>
    </div>
</body>
</html>
