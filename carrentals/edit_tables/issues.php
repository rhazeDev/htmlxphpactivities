<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$tableName = "Issues";
$id = $_GET['id'] ?? null;
$pkey = "IssueID";

$foreignKeys = [
    'RentalID' => 'Rentals'
];

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
        $allowedFields = ['DateResolved', 'Status'];
    $fields = [];
    $types = '';
    $values = [];

        $previousStatus = $row['Status'];
    $newStatus = $_POST['Status'];

    foreach ($allowedFields as $fieldName) {
        if (isset($_POST[$fieldName])) {
            $fields[] = "$fieldName = ?";

            if (strpos($fieldName, 'Date') !== false) {
                $types .= 's';
            } else {
                $types .= 's';
            }

            $values[] = $_POST[$fieldName];
        }
    }

        if ($newStatus == 'Resolved' && $previousStatus != 'Resolved') {
                $vehicleQuery = $conn->prepare("SELECT r.VehicleID FROM Rentals r WHERE r.RentalID = ?");
        $vehicleQuery->bind_param('i', $row['RentalID']);
        $vehicleQuery->execute();
        $vehicleResult = $vehicleQuery->get_result();
        $vehicleData = $vehicleResult->fetch_assoc();
        $vehicleQuery->close();

        if ($vehicleData) {
            $vehicleID = $vehicleData['VehicleID'];

                        $updateVehicleQuery = $conn->prepare("UPDATE Vehicles SET Status = 'Available' WHERE VehicleID = ? AND Status = 'Maintenance'");
            $updateVehicleQuery->bind_param('i', $vehicleID);
            $updateVehicleQuery->execute();
            $updateVehicleQuery->close();
        }
    }

    if (!empty($fields)) {
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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Issue Status - <?php echo ucfirst($tableName); ?></title>
    <link href="../styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <i class="fas fa-tasks"></i>
                    Update Issue Status
                </h1>
            </div>

            <?php
                        echo '<div class="info-section" style="background-color: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 2rem; border: 1px solid #e9ecef;">';
            echo '<h3 style="margin-bottom: 1rem; color: var(--text-primary);">Issue Information</h3>';

                        $displayQuery = "SELECT i.*, r.RentalID, c.Name as CustomerName, v.Model, v.PlateNumber 
                     FROM Issues i 
                     JOIN Rentals r ON i.RentalID = r.RentalID 
                     JOIN Customers c ON r.CustomerID = c.CustomerID 
                     JOIN Vehicles v ON r.VehicleID = v.VehicleID 
                     WHERE i.IssueID = ?";
            $displayStmt = $conn->prepare($displayQuery);
            $displayStmt->bind_param('i', $id);
            $displayStmt->execute();
            $displayResult = $displayStmt->get_result();
            $displayData = $displayResult->fetch_assoc();
            $displayStmt->close();

            if ($displayData) {
                echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">';
                echo '<div><strong>Customer:</strong> ' . htmlspecialchars($displayData['CustomerName']) . '</div>';
                echo '<div><strong>Vehicle:</strong> ' . htmlspecialchars($displayData['Model']) . ' (' . htmlspecialchars($displayData['PlateNumber']) . ')</div>';
                echo '<div><strong>Date Reported:</strong> ' . htmlspecialchars($displayData['DateReported']) . '</div>';
                echo '<div><strong>Current Status:</strong> <span style="color: ' . ($displayData['Status'] == 'Resolved' ? '#28a745' : '#ffc107') . '; font-weight: bold;">' . htmlspecialchars($displayData['Status']) . '</span></div>';
                echo '</div>';
                echo '<div style="margin-bottom: 1rem;"><strong>Description:</strong><br>' . htmlspecialchars($displayData['Description']) . '</div>';

                if (!empty($displayData['Proof']) && file_exists('../' . $displayData['Proof'])) {
                    echo '<div><strong>Proof:</strong><br>';
                    echo '<img src="../' . htmlspecialchars($displayData['Proof']) . '" alt="Issue Proof" style="max-width: 300px; max-height: 200px; border-radius: 0.5rem; margin-top: 0.5rem;">';
                    echo '</div>';
                }
            }
            echo '</div>';

                        echo '<form method="POST" action="">';

                        echo '<div class="form-group">';
            echo '<label>Status:</label>';
            echo '<select name="Status" required>';
            $statusOptions = ['Pending', 'In Progress', 'Resolved'];

            foreach ($statusOptions as $option) {
                $selected = ($option == $row['Status']) ? ' selected' : '';
                echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
            }
            echo '</select>';
            echo '</div>';

                        echo '<div class="form-group">';
            echo '<label>Date Resolved:</label>';
            echo '<input type="date" name="DateResolved" value="' . htmlspecialchars($row['DateResolved']) . '">';
            echo '<p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">Leave empty if not yet resolved</p>';
            echo '</div>';

            echo '<div class="form-actions">';
            echo '<button type="submit" class="btn btn-primary">';
            echo '<i class="fas fa-save"></i>';
            echo 'Update Issue Status';
            echo '</button>';
            echo '</div>';
            echo '</form>';
            ?>
        </div>
    </div>

    <script>
                document.querySelector('select[name="Status"]').addEventListener('change', function (e) {
            const dateResolvedInput = document.querySelector('input[name="DateResolved"]');
            if (e.target.value === 'Resolved' && !dateResolvedInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateResolvedInput.value = today;
            } else if (e.target.value !== 'Resolved') {
                dateResolvedInput.value = '';
            }
        });
    </script>

    <?php $conn->close(); ?>
</body>

</html>