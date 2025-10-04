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
    <title>Add Rental - Car Rentals</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="rentals.js"></script>
</head>

<body>
    <div class="form-container">
        <a href="../dashboard.php?table=Rentals" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Rentals List
        </a>

        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Rental
                </h1>
            </div>
            <div class="btn-container">
                <a href="customers.php?return=rentals" style="text-decoration: none;">
                    <button class="btn btn-primary"
                        style="background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%); border: none; padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(37, 99, 235, 0.6)'"
                        onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 4px 15px rgba(37, 99, 235, 0.4)'">
                        <i class="fas fa-user-plus"></i>
                        Add New Customer
                    </button>
                </a>
            </div>

            <?php
            include '../conn.php';

            $tableName = "Rentals";
            $pkey = "RentalID";

            $foreignKeys = [
                'CustomerID' => 'Customers',
                'VehicleID' => 'Vehicles'
            ];

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if (isset($_POST['PaymentMethod']) && $_POST['PaymentMethod'] === 'Downpayment') {
                    if (!isset($_POST['AmountToPay']) || empty($_POST['AmountToPay'])) {
                        echo "<div class='error-message'>";
                        echo "<i class='fas fa-exclamation-circle'></i>";
                        echo "Error: Amount to pay is required for downpayment option.";
                        echo "</div>";
                    } else {
                        $amountToPay = floatval($_POST['AmountToPay']);
                        if ($amountToPay <= 0) {
                            echo "<div class='error-message'>";
                            echo "<i class='fas fa-exclamation-circle'></i>";
                            echo "Error: Amount to pay must be greater than 0.";
                            echo "</div>";
                        }
                    }
                }

                $fields = [];
                $placeholders = [];
                $types = '';
                $values = [];

                if (isset($_FILES['CarImage']) && $_FILES['CarImage']['error'] == 0) {
                    $uploadDir = '../vehicles/';
                    $fileName = time() . '_' . $_FILES['CarImage']['name'];
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['CarImage']['tmp_name'], $uploadPath)) {
                        $_POST['CarImage'] = $fileName;
                    } else {
                        echo "<div class='error-message'>Error uploading car image.</div>";
                    }
                }

                $pickUpDate = $_POST['PickUpDate'];
                $toReturnDate = $_POST['ToReturnDate'];
                $vehicleID = $_POST['VehicleID'];

                $vehicleQuery = $conn->prepare("SELECT DailyRate FROM Vehicles WHERE VehicleID = ?");
                $vehicleQuery->bind_param('i', $vehicleID);
                $vehicleQuery->execute();
                $vehicleResult = $vehicleQuery->get_result();
                $vehicleData = $vehicleResult->fetch_assoc();
                $dailyRate = $vehicleData['DailyRate'];
                $vehicleQuery->close();

                $date1 = new DateTime($pickUpDate);
                $date2 = new DateTime($toReturnDate);
                $interval = $date1->diff($date2);
                $noOfDays = $interval->days + 1;

                                if ($noOfDays > 30) {
                    echo "<div class='error-message'>";
                    echo "<i class='fas fa-exclamation-circle'></i>";
                    echo "Error: Maximum rental period is 30 days. Current selection: " . $noOfDays . " days.";
                    echo "</div>";
                    $validationError = true;
                }

                $totalCost = $noOfDays * $dailyRate;

                if (isset($_POST['RentalTransport']) && $_POST['RentalTransport'] === 'Delivery') {
                    $totalCost += 500;
                }

                if (isset($_POST['PaymentMethod']) && $_POST['PaymentMethod'] === 'Downpayment') {
                    $totalCost += ($totalCost * 0.10);
                    if (isset($_POST['AmountToPay'])) {
                        $amountToPay = floatval($_POST['AmountToPay']);
                        $minimumDownpayment = $totalCost * 0.50;

                        if ($amountToPay < $minimumDownpayment) {
                            echo "<div class='error-message'>";
                            echo "<i class='fas fa-exclamation-circle'></i>";
                            echo "Error: Downpayment must be at least 50% of total cost (₱" . number_format($minimumDownpayment, 2) . ").";
                            echo "</div>";
                            $validationError = true;
                        } elseif ($amountToPay > $totalCost) {
                            echo "<div class='error-message'>";
                            echo "<i class='fas fa-exclamation-circle'></i>";
                            echo "Error: Amount cannot exceed total cost (₱" . number_format($totalCost, 2) . ").";
                            echo "</div>";
                            $validationError = true;
                        }
                    }
                }

                $_POST['NoOfDays'] = $noOfDays;
                $_POST['TotalCost'] = $totalCost;
                $_POST['DailyRate'] = $dailyRate;

                $kmBefore = isset($_POST['KmBefore']) ? $_POST['KmBefore'] : 0;

                if (isset($validationError) && $validationError) {
                } else {

                    $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
                    while ($fieldinfo = $fieldResult->fetch_field()) {
                        $fieldName = $fieldinfo->name;

                        $fields[] = $fieldName;
                        $placeholders[] = '?';

                        if (strpos($fieldName, 'Date') !== false) {
                            $types .= 's';
                        } elseif (in_array($fieldName, ['DailyRate', 'TotalCost'])) {
                            $types .= 'd';
                        } elseif (strpos($fieldName, 'ID') !== false || in_array($fieldName, ['NoOfDays'])) {
                            $types .= 'i';
                        } else {
                            $types .= 's';
                        }

                        if (isset($_POST[$fieldName])) {
                            $values[] = $_POST[$fieldName];
                        } else {
                            if ($fieldName == 'ReturnedDate') {
                                $values[] = '0000-00-00';
                            } elseif ($fieldName == 'Status') {
                                $values[] = 'Active';
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
                            $newRentalId = $conn->insert_id;

                            $updateVehicleQuery = $conn->prepare("UPDATE Vehicles SET Status = 'Rented' WHERE VehicleID = ?");
                            $updateVehicleQuery->bind_param('i', $vehicleID);
                            $updateVehicleQuery->execute();
                            $updateVehicleQuery->close();

                            if ($kmBefore > 0) {
                                $distanceQuery = $conn->prepare("INSERT INTO Distances (RentalID, KmBefore, KmAfter, KmUsed, DateRecorded) VALUES (?, ?, 0, 0, ?)");
                                $currentDate = date('Y-m-d');
                                $distanceQuery->bind_param('iis', $newRentalId, $kmBefore, $currentDate);
                                $distanceQuery->execute();
                                $distanceQuery->close();
                            }

                            if (isset($_POST['PaymentMethod']) && !empty($_POST['PaymentMethod'])) {
                                $paymentMethod = $_POST['PaymentMethod'];
                                $amountToPay = ($paymentMethod === 'Cash') ? $totalCost : (isset($_POST['AmountToPay']) ? $_POST['AmountToPay'] : $totalCost);
                                $remainingBalance = $totalCost - $amountToPay;

                                $dueAmount = 0;

                                $paymentDate = date('Y-m-d');
                                $paymentStatus = ($remainingBalance <= 0) ? 'Paid' : 'Partial';

                                $paymentQuery = $conn->prepare("INSERT INTO Payments (RentalID, Amount, RemainingBalance, DueAmount, PaymentDate, Method, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $paymentQuery->bind_param('idddsss', $newRentalId, $amountToPay, $remainingBalance, $dueAmount, $paymentDate, $paymentMethod, $paymentStatus);
                                $paymentQuery->execute();
                                $paymentQuery->close();
                            }

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
            }

            echo '<form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">';

            $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
            while ($fieldinfo = $fieldResult->fetch_field()) {
                $label = $fieldinfo->name;

                if ($label == $pkey)
                    continue;

                $skipFields = ['ReturnedDate', 'TotalCost', 'NoOfDays'];
                if (in_array($label, $skipFields))
                    continue;

                if ($label == 'DeliveryAddress') {
                    echo '<div class="form-group" id="delivery-address-group" style="display: none; opacity: 0;">';
                    echo '<label>' . $label . ':</label>';
                    echo '<input type="text" name="' . $label . '" placeholder="Enter delivery address...">';
                    echo '</div>';
                    continue;
                }

                echo '<div class="form-group">';
                echo '<label>' . $label . ':</label>';

                $isForeignKey = false;
                $fkTable = '';

                if (isset($foreignKeys[$label])) {
                    $isForeignKey = true;
                    $fkTable = $foreignKeys[$label];
                }

                if ($isForeignKey) {
                    if ($fkTable == 'Vehicles') {
                        $fkResult = $conn->query("SELECT * FROM $fkTable WHERE Status = 'Available'");
                    } else {
                        $fkResult = $conn->query("SELECT * FROM $fkTable");
                    }

                    $selectEvent = '';
                    if ($label == 'VehicleID') {
                        $selectEvent = ' onchange="carRate()"';
                    }

                    echo '<select name="' . $label . '" required' . $selectEvent . '>';
                    echo '<option value="">Select ' . ucfirst(str_replace('ID', '', $label)) . '...</option>';

                    while ($fkRow = $fkResult->fetch_assoc()) {
                        $displayField = '';
                        $dataAttributes = '';

                        if ($fkTable == 'Customers') {
                            $displayField = $fkRow['Name'] . ' (' . $fkRow['Email'] . ')';
                        } elseif ($fkTable == 'Vehicles') {
                            $displayField = $fkRow['Model'] . ' - ' . $fkRow['PlateNumber'] . ' (₱' . $fkRow['DailyRate'] . ')';
                            $dataAttributes = ' data-rate="' . $fkRow['DailyRate'] . '"';
                        }

                        $fkId = $fkRow[array_keys($fkRow)[0]];
                        echo '<option value="' . $fkId . '"' . $dataAttributes . '>' . htmlspecialchars($displayField) . '</option>';
                    }
                    echo '</select>';
                } else {
                    $inputType = 'text';
                    $onChangeEvent = '';

                    if ($label == 'PickUpDate' || $label == 'ToReturnDate') {
                        $inputType = 'date';
                        $onChangeEvent = ' onchange="calcRent()"';

                                                if ($label == 'PickUpDate') {
                            $onChangeEvent .= ' min="' . date('Y-m-d') . '"';
                        } elseif ($label == 'ToReturnDate') {
                            echo '<input type="' . $inputType . '" name="' . $label . '" required' . $onChangeEvent . '>';
                            echo '<div class="field-note" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">';
                            echo '<i class="fas fa-clock"></i> Maximum rental period is 30 days from pickup date.';
                            echo '</div>';
                            echo '</div>';
                            continue;
                        }
                    } elseif ($label == 'RentalTransport') {
                        echo '<select name="' . $label . '" required onchange="toggleDeliveryAddress()">';
                        echo '<option value="">Select Transport Option...</option>';
                        echo '<option value="PickUp">Pick Up</option>';
                        echo '<option value="Delivery">Delivery</option>';
                        echo '</select>';
                        echo '<div id="delivery-fee-note" style="display: none; margin-top: 10px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">';
                        echo '<i class="fas fa-info-circle" style="margin-right: 5px;"></i>';
                        echo '<strong>Note:</strong> Additional ₱500 delivery fee will be applied to the total cost.';
                        echo '</div>';
                        echo '</div>';
                        continue;

                    } elseif ($label == 'CarImage') {
                        echo '<label for="car-image-upload" class="image-upload" style="cursor: pointer; display: block;">';
                        echo '<i class="fas fa-upload" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>';
                        echo '<p id="car-image-upload-text" style="margin-bottom: 1rem; color: var(--text-secondary);">Click to upload car image</p>';
                        echo '<input type="file" id="car-image-upload" name="' . $label . '" accept="image/*" style="display: none;">';
                        echo '</label>';
                        echo '<div id="car-image-file-name" style="margin-top: 0.5rem; color: var(--success-color); font-size: 0.875rem; display: none;"></div>';
                        echo '</div>';
                        continue;
                    } elseif ($label == 'Status') {
                        echo '<select name="' . $label . '" required>';
                        $statusOptions = ['Active', 'Completed', 'Cancelled', 'Overdue'];
                        $defaultStatus = 'Active';

                        foreach ($statusOptions as $option) {
                            $selected = ($option == $defaultStatus) ? ' selected' : '';
                            echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
                        }

                        echo '</select>';
                        echo '</div>';
                        continue;
                    }

                    echo '<input type="' . $inputType . '" name="' . $label . '" required' . $onChangeEvent . '>';
                }
                echo '</div>';
            }

            echo '<div class="form-group">';
            echo '<label>KmBefore (Vehicle Odometer Reading):</label>';
            echo '<input type="number" name="KmBefore" min="0" placeholder="Enter current odometer reading" required>';
            echo '<div class="field-note" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">';
            echo '<i class="fas fa-info-circle"></i> Record the current kilometers on the vehicle odometer before rental starts.';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-group">';
            echo '<label>Payment Method:</label>';
            echo '<select name="PaymentMethod" required onchange="togglePaymentFields()">';
            echo '<option value="">Select Payment Method...</option>';
            echo '<option value="Cash">Cash (Full Payment)</option>';
            echo '<option value="Downpayment">Downpayment (Minimum 50%)</option>';
            echo '</select>';
            echo '<div class="field-note" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">';
            echo '<i class="fas fa-info-circle"></i> Downpayment option includes 10% additional fee and requires minimum 50% payment.';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-group" id="amount-to-pay-group" style="display: none;">';
            echo '<label>Amount to Pay:</label>';
            echo '<input type="number" name="AmountToPay" min="0" step="0.01" placeholder="Enter amount to pay" oninput="validateDownpayment()" onblur="validateDownpayment()">';
            echo '<div class="field-note" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">';
            echo '<i class="fas fa-exclamation-triangle"></i> Minimum 50% of total cost required for downpayment.';
            echo '</div>';
            echo '</div>';

            echo '<div class="cost-display">';
            echo '<div class="cost-label">Cost Breakdown:</div>';
            echo '<div class="cost-breakdown" id="cost-breakdown"></div>';
            echo '<div class="cost-total">';
            echo '<div class="cost-label">Total Cost:</div>';
            echo '<div class="cost-value" id="calculated-cost">₱0.00</div>';
            echo '</div>';
            echo '</div>';

            echo '<div class="form-actions">';
            echo '<input type="submit" value="Add Rental" class="btn btn-primary">';
            echo '</div>';
            echo '</form>';
            ?>
        </div>
    </div>



    <style>
        .cost-breakdown {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background-color: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .cost-item:last-child {
            margin-bottom: 0;
        }

        .cost-separator {
            height: 1px;
            background-color: #cbd5e1;
            margin: 0.75rem 0;
        }

        .cost-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            color: white;
            border-radius: 6px;
            font-weight: 600;
        }

        .cost-total .cost-label {
            font-size: 1rem;
        }

        .cost-total .cost-value {
            font-size: 1.125rem;
        }

        #amount-to-pay-group {
            transition: all 0.3s ease;
        }

        .field-note {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .field-note i {
            color: #64748b;
        }
    </style>
</body>

</html>