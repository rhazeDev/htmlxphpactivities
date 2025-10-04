<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rentals Dashboard</title>
    <link href="styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </div>
                <h2 class="sidebar-title">
                    <i class="fas fa-car"></i>
                    <span class="sidebar-text">Car Rentals</span>
                </h2>
            </div>

            <div class="navigation">
                <button id="users-data" class="nav-button">
                    <i class="fas fa-user-cog"></i>
                    <span class="nav-text">Users</span>
                </button>
                <button id="vehicles-data" class="nav-button">
                    <i class="fas fa-car"></i>
                    <span class="nav-text">Vehicles</span>
                </button>
                <button id="customers-data" class="nav-button">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Customers</span>
                </button>
                <button id="rentals-data" class="nav-button">
                    <i class="fas fa-file-contract"></i>
                    <span class="nav-text">Rentals</span>
                </button>
                <button id="issues-data" class="nav-button">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="nav-text">Issues</span>
                </button>
                <button id="distance-data" class="nav-button">
                    <i class="fas fa-route"></i>
                    <span class="nav-text">Distances</span>
                </button>
                <button id="payments-data" class="nav-button">
                    <i class="fas fa-credit-card"></i>
                    <span class="nav-text">Payments</span>
                </button>
            </div>

            <div class="sidebar-footer">
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span class="welcome-text"><?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                </div>
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </div>

        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Management Dashboard
                </h1>
            </div>

            <script src="get_tb_name.js"></script>
            <?php
            $headersConfig = json_decode(file_get_contents('table_headers.json'), true);

            $tableName = $_GET['table'] ?? "Rentals";

            if ($tableName == 'Rentals') {
                $sql = "SELECT r.*, 
                           c.Name as CustomerName, 
                           v.Model as VehicleModel, 
                           v.PlateNumber as VehiclePlateNumber
                    FROM Rentals r
                    LEFT JOIN Customers c ON r.CustomerID = c.CustomerID
                    LEFT JOIN Vehicles v ON r.VehicleID = v.VehicleID";
            } elseif ($tableName == 'Payments') {
                $sql = "SELECT p.*, 
                           c.Name as CustomerName, 
                           v.Model as VehicleModel, 
                           v.PlateNumber as VehiclePlateNumber
                    FROM Payments p
                    LEFT JOIN Rentals r ON p.RentalID = r.RentalID
                    LEFT JOIN Customers c ON r.CustomerID = c.CustomerID
                    LEFT JOIN Vehicles v ON r.VehicleID = v.VehicleID";
            } elseif ($tableName == 'Issues') {
                $sql = "SELECT i.*, 
                           c.Name as CustomerName, 
                           v.Model as VehicleModel, 
                           v.PlateNumber as VehiclePlateNumber
                    FROM Issues i
                    LEFT JOIN Rentals r ON i.RentalID = r.RentalID
                    LEFT JOIN Customers c ON r.CustomerID = c.CustomerID
                    LEFT JOIN Vehicles v ON r.VehicleID = v.VehicleID";
            } elseif ($tableName == 'Distances') {
                $sql = "SELECT d.*, 
                           c.Name as CustomerName, 
                           v.Model as VehicleModel, 
                           v.PlateNumber as VehiclePlateNumber
                    FROM Distances d
                    LEFT JOIN Rentals r ON d.RentalID = r.RentalID
                    LEFT JOIN Customers c ON r.CustomerID = c.CustomerID
                    LEFT JOIN Vehicles v ON r.VehicleID = v.VehicleID";
            } elseif ($tableName == 'Users') {
                $sql = "SELECT * FROM Users ORDER BY id";
            } else {
                $sql = "SELECT * FROM " . $tableName;
            }

            $result = $conn->query($sql);
            ?>

            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">
                        <?php
                        $icons = [
                            'Vehicles' => 'fas fa-car',
                            'Customers' => 'fas fa-users',
                            'Rentals' => 'fas fa-file-contract',
                            'Issues' => 'fas fa-exclamation-triangle',
                            'Distances' => 'fas fa-route',
                            'Payments' => 'fas fa-credit-card',
                            'Users' => 'fas fa-user-cog'
                        ];
                        $icon = $icons[$tableName] ?? 'fas fa-table';
                        echo "<i class='$icon'></i> " . htmlspecialchars($tableName);
                        ?>
                    </h2>
                    <?php if ($tableName != 'Distances' && $tableName != 'Payments' && $tableName != 'Issues'): ?>
                        <a href="add_tables/<?php echo strtolower($tableName); ?>.php" class="add-button">
                            <i class="fas fa-plus"></i>
                            Add <?php echo $tableName == 'Users' ? 'User' : substr($tableName, 0, -1); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-wrapper">
                    <?php
                    if ($result->num_rows > 0) {
                        echo "<table>";
                        echo "<thead><tr>";

                        $helperColumns = ['CustomerName', 'VehicleModel', 'VehiclePlateNumber'];
                        $primaryKeys = ['CustomerID', 'VehicleID', 'RentalID', 'PaymentID', 'IssueID', 'DistanceID', 'id'];

                        while ($fieldinfo = $result->fetch_field()) {
                            $fieldName = $fieldinfo->name;

                            if (
                                in_array($fieldName, $helperColumns) ||
                                ($fieldName == 'CustomerID' && $tableName == 'Customers') ||
                                ($fieldName == 'VehicleID' && $tableName == 'Vehicles') ||
                                ($fieldName == 'RentalID' && $tableName == 'Rentals') ||
                                ($fieldName == 'PaymentID' && $tableName == 'Payments') ||
                                ($fieldName == 'IssueID' && $tableName == 'Issues') ||
                                ($fieldName == 'DistanceID' && $tableName == 'Distances') ||
                                ($fieldName == 'id' && $tableName == 'Users')
                            ) {
                                continue;
                            }

                            $headerName = $fieldName;

                            if (isset($headersConfig[$tableName][$fieldName])) {
                                $headerName = $headersConfig[$tableName][$fieldName];
                            } elseif (isset($headersConfig['default_headers'][$fieldName])) {
                                $headerName = $headersConfig['default_headers'][$fieldName];
                            } elseif ($fieldName == 'CustomerID' && $tableName != 'Customers') {
                                $headerName = $headersConfig['default_headers']['CustomerID'] ?? 'Customer';
                            } elseif ($fieldName == 'VehicleID' && $tableName != 'Vehicles') {
                                $headerName = $headersConfig['default_headers']['VehicleID'] ?? 'Vehicle';
                            } elseif ($fieldName == 'RentalID' && $tableName != 'Rentals') {
                                $headerName = $headersConfig['default_headers']['RentalID'] ?? 'Rental Info';
                            }

                            echo "<th>" . htmlspecialchars($headerName) . "</th>";
                        }

                        if ($tableName != 'Distances' && $tableName != 'Payments') {
                            echo "<th>Actions</th>";
                        }
                        echo "</tr></thead>";
                        echo "<tbody>";

                        $result->data_seek(0);

                        while ($row = $result->fetch_assoc()) {
                            $rowClass = "";
                            $isOverdue = false;
                            $hasAdditionalPayment = false;

                            if ($tableName == 'Rentals' && isset($row['ToReturnDate']) && isset($row['ReturnedDate'])) {
                                $toReturnDate = new DateTime($row['ToReturnDate']);
                                $currentDate = new DateTime();

                                if (empty($row['ReturnedDate']) || $row['ReturnedDate'] == '0000-00-00') {
                                    if ($currentDate > $toReturnDate) {
                                        $rowClass = ' class="due-rental"';
                                    } else {
                                        $rowClass = ' class="not-returned-rental"';
                                    }
                                } else {
                                    $returnedDate = new DateTime($row['ReturnedDate']);
                                    $isOverdue = $returnedDate > $toReturnDate;

                                    $rentalId = $row['RentalID'];
                                    $paymentCheck = $conn->query("SELECT DueAmount FROM Payments WHERE RentalID = $rentalId AND DueAmount > 0");
                                    $hasAdditionalPayment = $paymentCheck && $paymentCheck->num_rows > 0;
                                }
                            } elseif ($tableName == 'Payments' && isset($row['RentalID'])) {
                                $rentalId = $row['RentalID'];
                                $rentalQuery = $conn->query("SELECT ToReturnDate, ReturnedDate FROM Rentals WHERE RentalID = $rentalId");

                                if ($rentalQuery && $rentalQuery->num_rows > 0) {
                                    $rental = $rentalQuery->fetch_assoc();

                                    if (!empty($rental['ReturnedDate'])) {
                                        $toReturnDate = new DateTime($rental['ToReturnDate']);
                                        $returnedDate = new DateTime($rental['ReturnedDate']);

                                        $isOverdue = $returnedDate > $toReturnDate && $row['DueAmount'] <= 0;
                                    }
                                }
                            }

                            if ($tableName != 'Rentals') {
                                $rowClass = ($isOverdue && !$hasAdditionalPayment) || ($tableName == 'Payments' && $isOverdue) ? ' class="overdue-row"' : "";
                            }
                            echo "<tr$rowClass>";
                            foreach ($row as $fieldName => $data) {
                                if (
                                    in_array($fieldName, ['CustomerName', 'VehicleModel', 'VehiclePlateNumber']) ||
                                    ($fieldName == 'CustomerID' && $tableName == 'Customers') ||
                                    ($fieldName == 'VehicleID' && $tableName == 'Vehicles') ||
                                    ($fieldName == 'RentalID' && $tableName == 'Rentals') ||
                                    ($fieldName == 'PaymentID' && $tableName == 'Payments') ||
                                    ($fieldName == 'IssueID' && $tableName == 'Issues') ||
                                    ($fieldName == 'DistanceID' && $tableName == 'Distances') ||
                                    ($fieldName == 'id' && $tableName == 'Users')
                                ) {
                                    continue;
                                }

                                if (
                                    ($tableName == 'Vehicles' && $fieldName == 'Image') ||
                                    ($tableName == 'Customers' && $fieldName == 'LicenseImg') ||
                                    ($tableName == 'Issues' && $fieldName == 'Proof') ||
                                    ($tableName == 'Rentals' && $fieldName == 'CarImage')
                                ) {
                                    $imagePath = ($tableName == 'Rentals' && $fieldName == 'CarImage') ? 'vehicles/' . $data : $data;

                                    if (!empty($data) && file_exists($imagePath)) {
                                        echo "<td style='text-align: center; vertical-align: middle;'><img src='" . htmlspecialchars($imagePath) . "' alt='Image' style='width:60px; height:60px; object-fit:cover; border-radius:8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'></td>";
                                    } else {
                                        echo "<td style='text-align: center; vertical-align: middle;'><span style='color: #64748b; font-style: italic;'>No Image</span></td>";
                                    }
                                } elseif ($fieldName == 'Status') {
                                    $statusClass = 'status-' . strtolower(str_replace(' ', '-', $data));
                                    echo "<td><span class='status-badge $statusClass'>" . htmlspecialchars($data) . "</span></td>";
                                } elseif ($tableName == 'Users' && $fieldName == 'enabled') {
                                    $userId = $row['id'];
                                    $statusText = $data ? 'Enabled' : 'Disabled';
                                    $statusClass = $data ? 'status-active' : 'status-inactive';
                                    $newStatus = $data ? 0 : 1;
                                    if ($userId != 1) {
                                        echo "<td><a href='toggle_user_status.php?id=" . $userId . "&status=" . $newStatus . "' class='status-toggle' onclick='return confirm(\"Are you sure you want to " . ($data ? 'disable' : 'enable') . " this user?\")' style='text-decoration: none;'><span class='status-badge $statusClass status-clickable'>" . $statusText . "</span></a></td>";
                                    } else {
                                        echo "<td><span class='status-badge $statusClass'>" . $statusText . "</span></td>";
                                    }
                                } elseif ($tableName == 'Users' && $fieldName == 'password') {
                                    echo "<td>••••••••</td>";
                                } elseif ($tableName == 'Payments' && $fieldName == 'DueAmount') {
                                    if ($data > 0) {
                                        echo "<td><span class='late-payment'>" . number_format($data, 2) . "</span></td>";
                                    } else {
                                        echo "<td>" . number_format($data, 2) . "</td>";
                                    }
                                } elseif ($tableName == 'Payments' && ($fieldName == 'Amount' || stripos($fieldName, 'amount') !== false)) {
                                    echo "<td>" . number_format($data, 2) . "</td>";
                                } elseif ($fieldName == 'CustomerID' && $tableName != 'Customers') {
                                    $displayText = !empty($row['CustomerName']) ?
                                        htmlspecialchars($row['CustomerName']) :
                                    htmlspecialchars($data);
                                    echo "<td>" . $displayText . "</td>";
                                } elseif ($fieldName == 'VehicleID' && $tableName != 'Vehicles') {
                                    $displayText = (!empty($row['VehicleModel']) && !empty($row['VehiclePlateNumber'])) ?
                                        htmlspecialchars($row['VehicleModel']) . ' - ' . htmlspecialchars($row['VehiclePlateNumber']) :
                                    htmlspecialchars($data);
                                    echo "<td>" . $displayText . "</td>";
                                } elseif ($fieldName == 'RentalID' && $tableName != 'Rentals') {
                                    $displayText = (!empty($row['CustomerName']) && !empty($row['VehicleModel']) && !empty($row['VehiclePlateNumber'])) ?
                                        htmlspecialchars($row['CustomerName']) . ' - ' . htmlspecialchars($row['VehicleModel']) . ' (' . htmlspecialchars($row['VehiclePlateNumber']) . ')' :
                                    htmlspecialchars($data);
                                    echo "<td>" . $displayText . "</td>";
                                } else {
                                    echo "<td>" . htmlspecialchars($data) . "</td>";
                                }
                            }

                            if ($tableName != 'Distances' && $tableName != 'Payments') {
                                $pkey = ($tableName == 'Users') ? 'id' : substr($tableName, 0, -1) . 'ID';
                                echo "<td class='actions'>";

                                if ($tableName == 'Users') {
                                    $userId = $row['id'];
                                    $isEnabled = $row['enabled'];

                                    if ($userId != 1) {
                                        echo "<a href='deleterecord.php?table=" . $tableName . "&id=" . $userId . "' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this user? This action cannot be undone!\")' style='background-color: #dc2626; color: white; padding: 0.375rem 0.75rem; border-radius: 0.375rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.875rem;'>
                                        <i class='fas fa-trash'></i> Delete
                                    </a>";
                                    } else {
                                        echo "<span class='admin-badge' style='background-color: #7c3aed; color: white; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem;'>
                                        <i class='fas fa-crown'></i> Admin
                                    </span>";
                                    }
                                } elseif ($tableName == 'Rentals') {
                                    if (empty($row['ReturnedDate']) || $row['ReturnedDate'] == '0000-00-00') {
                                        echo "<a href='edit_tables/" . strtolower($tableName) . ".php?id=" . $row[$pkey] . "' class='return-btn' style='background-color: #059669; color: white; padding: 0.375rem 0.75rem; border-radius: 0.375rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.875rem;'>
                                        <i class='fas fa-undo'></i> Return
                                    </a>";
                                    } else {
                                        echo "<span class='status-badge status-returned' style='color: #059669; font-size: 0.875rem;'>
                                        <i class='fas fa-check-circle'></i> Returned
                                    </span>";
                                    }
                                } else {
                                    echo "<a href='edit_tables/" . strtolower($tableName) . ".php?id=" . $row[$pkey] . "' class='edit-btn'>
                                    <i class='fas fa-edit'></i> Edit
                                </a>";
                                }

                                echo "</td>";
                            }
                            echo "</tr>";
                        }

                        echo "</tbody></table>";
                    } else {
                        echo "<div class='no-records'>";
                        echo "<div class='no-records-icon'><i class='fas fa-inbox'></i></div>";
                        echo "No records found in " . htmlspecialchars($tableName) . ".";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>


    <?php
    $conn->close();
    ?>

</body>

</html>