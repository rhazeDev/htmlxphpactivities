<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id)
    die('No record ID provided.');

$stmt = $conn->prepare("SELECT r.*, c.Name as CustomerName, v.Model as VehicleModel, v.PlateNumber as VehiclePlateNumber, v.DailyRate FROM Rentals r JOIN Customers c ON r.CustomerID = c.CustomerID JOIN Vehicles v ON r.VehicleID = v.VehicleID WHERE r.RentalID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$rental = $stmt->get_result()->fetch_assoc();
if (!$rental)
    die('Rental not found.');

$isReturned = !empty($rental['ReturnedDate']) && $rental['ReturnedDate'] != '0000-00-00';

$payment = $distance = null;
if (!$isReturned) {
    $stmt = $conn->prepare("SELECT * FROM Payments WHERE RentalID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT * FROM Distances WHERE RentalID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $distance = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$isReturned && isset($_POST['return_action'])) {
        $returnedDate = $_POST['ReturnedDate'];
        $kmAfter = $_POST['kmAfter'];
        $paymentConfirmed = isset($_POST['payment_confirmed']) && $_POST['payment_confirmed'] == '1';
        $conn->autocommit(FALSE);

        try {
            $conn->prepare("UPDATE Rentals SET ReturnedDate = ? WHERE RentalID = ?")->execute([$returnedDate, $id]);

            if ($distance) {
                $kmUsed = $kmAfter - $distance['KmBefore'];
                $conn->prepare("UPDATE Distances SET KmAfter = ?, KmUsed = ?, DateRecorded = ? WHERE DistanceID = ?")->execute([$kmAfter, $kmUsed, $returnedDate, $distance['DistanceID']]);
            } else {
                $kmBefore = $_POST['kmBefore'] ?? 0;
                $kmUsed = $kmAfter - $kmBefore;
                $conn->prepare("INSERT INTO Distances (RentalID, DateRecorded, KmBefore, KmAfter, KmUsed) VALUES (?, ?, ?, ?, ?)")->execute([$id, $returnedDate, $kmBefore, $kmAfter, $kmUsed]);
            }

            $hasIssue = false;
            if (isset($_POST['hasIssue']) && !empty($_POST['issueDescription'])) {
                $hasIssue = true;
                $proofPath = '';
                if (isset($_FILES['issueProof']) && $_FILES['issueProof']['error'] == 0) {
                    $uploadDir = '../issues/';
                    if (!file_exists($uploadDir))
                        mkdir($uploadDir, 0777, true);
                    $fileName = time() . '_' . $_FILES['issueProof']['name'];
                    if (move_uploaded_file($_FILES['issueProof']['tmp_name'], $uploadDir . $fileName)) {
                        $proofPath = 'issues/' . $fileName;
                    }
                }
                $conn->prepare("INSERT INTO Issues (RentalID, Description, DateReported, Proof, Status) VALUES (?, ?, ?, ?, 'Pending')")->execute([$id, $_POST['issueDescription'], $returnedDate, $proofPath]);
            }

            $totalDueAmount = 0;
            $lateCharge = 0;

            if (new DateTime($returnedDate) > new DateTime($rental['ToReturnDate'])) {
                $lateDays = (new DateTime($returnedDate))->diff(new DateTime($rental['ToReturnDate']))->days;
                $lateCharge = $lateDays * ($rental['DailyRate'] * 1.5);
                $totalDueAmount += $lateCharge;
            }

            if ($payment) {
                $existingDue = $payment['DueAmount'];
                $remainingBalance = 0;

                if (strtolower($payment['Method']) == 'downpayment' && $payment['RemainingBalance'] > 0) {
                    $remainingBalance = $payment['RemainingBalance'];
                    $totalDueAmount += $remainingBalance;
                }


                if ($paymentConfirmed && $totalDueAmount > 0) {
                    $totalDueAmount = 0;
                }

                $status = $totalDueAmount > 0 ? 'Partial' : 'Paid';
                $updatePaymentStmt = $conn->prepare("UPDATE Payments SET DueAmount = ?, RemainingBalance = 0.00, Status = ? WHERE PaymentID = ?");
                $updatePaymentStmt->bind_param("dsi", $totalDueAmount, $status, $payment['PaymentID']);
                $updatePaymentStmt->execute();
                $updatePaymentStmt->close();
            } else {
                if ($paymentConfirmed && $totalDueAmount > 0) {
                    $totalDueAmount = 0;
                }

                $status = $totalDueAmount > 0 ? 'Partial' : 'Paid';
                $insertPaymentStmt = $conn->prepare("INSERT INTO Payments (RentalID, Amount, RemainingBalance, DueAmount, PaymentDate, Method, Status) VALUES (?, ?, 0.00, ?, ?, 'Cash', ?)");
                $insertPaymentStmt->bind_param("iddss", $id, $rental['TotalCost'], $totalDueAmount, $returnedDate, $status);
                $insertPaymentStmt->execute();
                $insertPaymentStmt->close();
            }

            $existingIssuesStmt = $conn->prepare("SELECT COUNT(*) as issue_count FROM Issues WHERE RentalID = ? AND Status != 'Resolved'");
            $existingIssuesStmt->bind_param('i', $id);
            $existingIssuesStmt->execute();
            $existingIssuesResult = $existingIssuesStmt->get_result();
            $existingIssuesCount = $existingIssuesResult->fetch_assoc()['issue_count'];
            $existingIssuesStmt->close();

            $vehicleStatus = ($hasIssue || $existingIssuesCount > 0) ? 'Maintenance' : 'Available';
            $conn->prepare("UPDATE Vehicles SET Status = ? WHERE VehicleID = ?")->execute([$vehicleStatus, $rental['VehicleID']]);

            $conn->commit();

            error_log("Vehicle returned - RentalID: $id, TotalDueAmount: $totalDueAmount, PaymentConfirmed: " . ($paymentConfirmed ? 'Yes' : 'No'));

            echo "<script>window.location.href = '../dashboard.php?table=Rentals';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
        $conn->autocommit(TRUE);
    } else {
        $fields = [];
        $values = [];
        $types = '';

        foreach (['CustomerID' => 'i', 'VehicleID' => 'i', 'PickUpDate' => 's', 'ToReturnDate' => 's', 'ReturnedDate' => 's', 'TotalCost' => 'd', 'RentalTransport' => 's', 'DeliveryAddress' => 's'] as $field => $type) {
            if (isset($_POST[$field])) {
                $fields[] = "$field = ?";
                $values[] = $_POST[$field];
                $types .= $type;
            }
        }

        if ($fields) {
            $types .= 'i';
            $values[] = $id;
            $stmt = $conn->prepare("UPDATE Rentals SET " . implode(", ", $fields) . " WHERE RentalID = ?");
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                echo "<script>window.location.href = '../dashboard.php?table=Rentals';</script>";
                exit;
            } else {
                $error = "Error updating rental.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo $isReturned ? 'Edit' : 'Return'; ?> Rental</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="rental-modal.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        window.rentalData = {
            dueDate: '<?php echo $rental['ToReturnDate']; ?>',
            dailyRate: <?php echo $rental['DailyRate']; ?>,
            paymentMethod: '<?php echo $payment ? strtolower($payment['Method']) : ''; ?>',
            dueAmount: <?php echo $payment ? $payment['DueAmount'] : 0; ?>,
            remainingBalance: <?php echo $payment ? $payment['RemainingBalance'] : 0; ?>,
            isReturned: <?php echo $isReturned ? 'true' : 'false'; ?>
        };
    </script>
    <script src="edit-rentals.js"></script>
</head>

<body>
    <div class="form-container">
        <a href="../dashboard.php?table=Rentals" class="back-link"><i class="fas fa-arrow-left"></i> Back to Rentals</a>
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <i class="fas fa-<?php echo $isReturned ? 'edit' : 'undo'; ?>"></i>
                    <?php echo $isReturned ? 'Edit Rental' : 'Return Vehicle'; ?>
                </h1>
            </div>

            <?php if (isset($error))
                echo "<div class='rental-error'>$error</div>"; ?>

            <!-- Rental Info -->
            <div class="rental-info-section">
                <h3 class="rental-info-header"><i class="fas fa-info-circle"></i> Rental Information</h3>
                <div class="rental-info-content">
                    <div class="rental-info-highlight">
                        <?php echo htmlspecialchars($rental['CustomerName'] . ' - ' . $rental['VehicleModel'] . ' (' . $rental['VehiclePlateNumber'] . ')'); ?>
                    </div>
                </div>
                <div class="rental-info-grid">
                    <div><strong>Pickup Date:</strong> <?php echo $rental['PickUpDate']; ?></div>
                    <div><strong>Due Date:</strong> <?php echo $rental['ToReturnDate']; ?></div>
                    <div><strong>Total Cost:</strong> ₱<?php echo number_format($rental['TotalCost'], 2); ?></div>
                    <div><strong>Daily Rate:</strong> ₱<?php echo number_format($rental['DailyRate'], 2); ?></div>
                </div>

                <?php
                if (!$isReturned) {
                    $existingIssuesStmt = $conn->prepare("SELECT COUNT(*) as issue_count FROM Issues WHERE RentalID = ? AND Status != 'Resolved'");
                    $existingIssuesStmt->bind_param('i', $id);
                    $existingIssuesStmt->execute();
                    $existingIssuesResult = $existingIssuesStmt->get_result();
                    $existingIssuesCount = $existingIssuesResult->fetch_assoc()['issue_count'];
                    $existingIssuesStmt->close();

                    if ($existingIssuesCount > 0) {
                        echo '<div style="margin-top: 1rem; padding: 0.75rem; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 0.375rem; color: #991b1b;">';
                        echo '<i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>';
                        echo '<strong>Warning:</strong> This rental has ' . $existingIssuesCount . ' unresolved issue(s). Vehicle will be set to "Maintenance" status.';
                        echo '</div>';
                    }
                }
                ?>
            </div>

            <!-- Payment Info -->
            <?php if ($payment): ?>
                <div class="payment-info-section">
                    <h3 class="payment-info-header"><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <div class="payment-info-grid">
                        <div><strong>Type:</strong> <?php echo $payment['Method']; ?></div>
                        <div><strong>Paid:</strong> ₱<?php echo number_format($payment['Amount'], 2); ?></div>
                        <?php if (strtolower($payment['Method']) == 'downpayment'): ?>
                            <div><strong>Remaining:</strong> ₱<?php echo number_format($payment['RemainingBalance'], 2); ?>
                            </div>
                            <?php if ($payment['DueAmount'] > 0): ?>
                                <div class="payment-due-amount"><strong>Due:</strong>
                                    ₱<?php echo number_format($payment['DueAmount'], 2); ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($payment['DueAmount'] == 0): ?>
                                <div class="payment-status-paid"><i class="fas fa-check-circle"></i> Rental is fully paid</div>
                            <?php elseif ($payment['DueAmount'] > 0): ?>
                                <div class="payment-due-amount"><strong>Due:</strong>
                                    ₱<?php echo number_format($payment['DueAmount'], 2); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php if (!$isReturned): ?>
                    <input type="hidden" name="return_action" value="1">
                    <div class="form-group">
                        <label for="ReturnedDate">Return Date:</label>
                        <input type="date" id="ReturnedDate" name="ReturnedDate" required onchange="calculateLateFee()">
                        <div id="lateFeeInfo"></div>
                    </div>
                    <?php if (!$distance): ?>
                        <div class="form-group">
                            <label for="kmBefore">Starting KM:</label>
                            <input type="number" id="kmBefore" name="kmBefore" min="0" required>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="kmAfter">Final KM:</label>
                        <input type="number" id="kmAfter" name="kmAfter"
                            min="<?php echo $distance ? $distance['KmBefore'] : 0; ?>" required>
                        <?php if ($distance): ?><small>Starting: <?php echo number_format($distance['KmBefore']); ?>
                                KM</small><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <div class="issue-checkbox-container">
                            <label for="hasIssue" class="issue-checkbox-label">Report issue with the vehicle</label>
                            <input type="checkbox" id="hasIssue" name="hasIssue" value="1" onchange="toggleIssueFields()"
                                class="issue-checkbox">
                        </div>
                    </div>


                    <div id="issueFields" class="issue-fields-section">
                        <h4 class="issue-fields-header"><i class="fas fa-exclamation-triangle"></i> Report Issue</h4>
                        <div class="form-group">
                            <label for="issueDescription">Description:</label>
                            <textarea id="issueDescription" name="issueDescription" rows="3"
                                placeholder="Describe the issue with the vehicle..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="issueProof">Proof:</label>
                            <label for="issue-upload" class="image-upload"
                                style="cursor: pointer; display: block; text-align: center; padding: 2rem; border: 2px dashed #cbd5e1; border-radius: 0.5rem; background: #f8fafc; transition: all 0.2s ease;">
                                <i class="fas fa-upload" style="font-size: 2rem; color: #64748b; margin-bottom: 1rem;"></i>
                                <p id="issue-upload-text" style="margin-bottom: 1rem; color: #64748b;">Click to upload proof
                                    image</p>
                                <input type="file" id="issue-upload" name="issueProof" accept="image/*"
                                    style="display: none;">
                            </label>
                            <div id="issue-file-name"
                                style="margin-top: 0.5rem; color: #059669; font-size: 0.875rem; display: none;"></div>
                            <p style="margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">Upload an image as proof of
                                the issue (JPG, PNG, GIF)</p>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary return-btn" onclick="return confirmReturn()"><i
                                class="fas fa-check"></i> Complete Return</button>
                    </div>
                <?php else: ?>
                    <?php
                    $customers = $conn->query("SELECT * FROM Customers");
                    $vehicles = $conn->query("SELECT * FROM Vehicles");
                    ?>
                    <div class="form-group">
                        <label>Customer:</label>
                        <select name="CustomerID" required>
                            <?php while ($c = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $c['CustomerID']; ?>" <?php echo $c['CustomerID'] == $rental['CustomerID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['Name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vehicle:</label>
                        <select name="VehicleID" required>
                            <?php while ($v = $vehicles->fetch_assoc()): ?>
                                <option value="<?php echo $v['VehicleID']; ?>" <?php echo $v['VehicleID'] == $rental['VehicleID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v['Model'] . ' - ' . $v['PlateNumber']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Pickup Date:</label><input type="date" name="PickUpDate"
                            value="<?php echo $rental['PickUpDate']; ?>" required></div>
                    <div class="form-group"><label>Due Date:</label><input type="date" name="ToReturnDate"
                            value="<?php echo $rental['ToReturnDate']; ?>" required></div>
                    <div class="form-group"><label>Return Date:</label><input type="date" name="ReturnedDate"
                            value="<?php echo $rental['ReturnedDate']; ?>"></div>
                    <div class="form-group"><label>Total Cost:</label><input type="number" name="TotalCost"
                            value="<?php echo $rental['TotalCost']; ?>" step="0.01" required></div>
                    <div class="form-group">
                        <label>Transport:</label>
                        <select name="RentalTransport" required>
                            <option value="Pickup" <?php echo $rental['RentalTransport'] == 'Pickup' ? 'selected' : ''; ?>>
                                Pickup</option>
                            <option value="Delivery" <?php echo $rental['RentalTransport'] == 'Delivery' ? 'selected' : ''; ?>>Delivery</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Delivery Address:</label><textarea name="DeliveryAddress"
                            rows="2"><?php echo htmlspecialchars($rental['DeliveryAddress']); ?></textarea></div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>

</html>