<?php
include 'conn.php';

$tableName = $_GET['table'] ?? 'rentals';
$id = $_GET['id'] ?? null;
$pkey = substr($tableName, 0, -1) . 'ID';

$foreignKeys = [
    'Rentals' => ['CustomerID' => 'customers', 'VehicleID' => 'vehicles'],
    'Distances' => ['RentalID' => 'rentals', 'VehicleID' => 'vehicles'],
    'Issues' => ['RentalID' => 'rentals', 'VehicleID' => 'vehicles', 'CustomerID' => 'customers'],
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
    $fields = [];
    $types = '';
    $values = [];

    if ($tableName == 'rentals') {
        $pickUpDate = $_POST['PickUpDate'];
        $toReturnDate = $_POST['ToReturnDate'];
        $dailyRate = $_POST['DailyRate'];

        $date1 = new DateTime($pickUpDate);
        $date2 = new DateTime($toReturnDate);
        $interval = $date1->diff($date2);
        $noOfDays = $interval->days + 1;

        $totalCost = $noOfDays * $dailyRate;

        $_POST['NoOfDays'] = $noOfDays;
        $_POST['TotalCost'] = $totalCost;
    }

    if ($tableName == 'Distances' && isset($_POST['KmAfter']) && isset($_POST['KmBefore'])) {
        $kmAfter = (int)$_POST['KmAfter'];
        $kmBefore = (int)$_POST['KmBefore'];
        $kmUsed = $kmAfter - $kmBefore;
        $_POST['KmUsed'] = $kmUsed;
    }

    $imageFields = [
        'Vehicles' => 'Image',
        'Customers' => 'LicenseImg', 
        'Issues' => 'Proof'
    ];

    if (isset($imageFields[strtolower($tableName)])) {
        $imageField = $imageFields[strtolower($tableName)];
        $folderMap = [
            'Image' => 'vehicles/',
            'LicenseImg' => 'profiles/',
            'Proof' => 'issues/'
        ];
        
        $targetDir = $folderMap[$imageField];
        
        
        if (isset($_FILES[$imageField]) && $_FILES[$imageField]['error'] == 0) {
            $fileName = basename($_FILES[$imageField]["name"]);
            $targetFilePath = $targetDir . time() . "_" . $fileName;
            
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = array("jpg", "jpeg", "png", "gif");
            
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES[$imageField]["tmp_name"], $targetFilePath)) {
                    $_POST[$imageField] = $targetFilePath;
                } else {
                    echo "<p style='color:red;'>Error uploading file.</p>";
                }
            } else {
                echo "<p style='color:red;'>Only JPG, JPEG, PNG, GIF files are allowed.</p>";
            }
        } else {
            $_POST[$imageField] = $row[$imageField];
        }
    }

    foreach ($row as $label => $value) {
        if ($label == $pkey) continue;

        $fields[] = "$label = ?";

        if (strpos($label, 'Date') !== false) {
            $types .= 's';
        } elseif (in_array($label, ['DailyRate', 'TotalCost'])) {
            $types .= 'd';
        } elseif (strpos($label, 'ID') !== false || in_array($label, ['KmBefore', 'KmAfter', 'KmUsed', 'NoOfDays'])) {
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
        echo "<p style='color:green;'>Record updated successfully!</p>";

        $stmt2 = $conn->prepare("SELECT * FROM $tableName WHERE $pkey = ?");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $row = $result2->fetch_assoc();
        $stmt2->close();
    } else {
        echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Record - <?php echo ucfirst($tableName); ?></title>
    <link href="styles.css" rel="stylesheet">
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
    <h2>Edit <?php echo ucfirst(substr($tableName, 0, -1)); ?> Record</h2>

    <?php
    echo '<form method="POST" action="" enctype="multipart/form-data">';
    foreach ($row as $label => $value) {
        if ($label == $pkey) continue;

        $skipFields = [
            'Rentals' => ['TotalCost', 'NoOfDays'],
            'Distances' => ['KmUsed'],
        ];

        $tbSkipField = $skipFields[$tableName] ?? [];
        if (in_array($label, $tbSkipField)) {
            if ($tableName == 'Distances' && $label == 'KmUsed') {
                echo '<label>Km Used:</label>';
                echo '<div style="padding: 8px; background-color: #f0f0f0; border: 1px solid #ccc; display: inline-block; margin-bottom: 10px;">';
                echo '<span id="calculated-kmused">' . htmlspecialchars($value) . '</span> km';
                echo '</div><br>';
            }
            continue;
        }

        echo '<label>' . $label . ':</label>';

        $isForeignKey = false;
        $fkTable = '';

        if (isset($foreignKeys[$tableName]) && isset($foreignKeys[$tableName][$label])) {
            $isForeignKey = true;
            $fkTable = $foreignKeys[$tableName][$label];
        } elseif ($label == 'CustomerID') {
            $isForeignKey = true;
            $fkTable = 'customers';
        } elseif ($label == 'VehicleID') {
            $isForeignKey = true;
            $fkTable = 'vehicles';
        } elseif ($label == 'RentalID') {
            $isForeignKey = true;
            $fkTable = 'rentals';
        }

        if ($isForeignKey) {
            $fkResult = $conn->query("SELECT * FROM $fkTable");

            echo '<select name="' . $label . '" required>';
            while ($fkRow = $fkResult->fetch_assoc()) {
                $displayField = '';
                if ($fkTable == 'customers') {
                    $displayField = $fkRow['Name'] . ' (' . $fkRow['Email'] . ')';
                } elseif ($fkTable == 'vehicles') {
                    $displayField = $fkRow['Model'] . ' - ' . $fkRow['PlateNumber'];
                } elseif ($fkTable == 'rentals') {
                    $displayField = 'Rental #' . $fkRow['RentalID'] . ' (' . $fkRow['PickUpDate'] . ' - ' . $fkRow['ToReturnDate'] . ')';
                } else {
                    if (isset($fkRow['Name']))
                        $displayField = $fkRow['Name'];
                    elseif (isset($fkRow['Model']))
                        $displayField = $fkRow['Model'];
                    else {
                        $keys = array_keys($fkRow);
                        if (count($keys) > 1)
                            $displayField = $fkRow[$keys[1]];
                        else
                            $displayField = $fkRow[$keys[0]];
                    }
                }

                $fkId = $fkRow[array_keys($fkRow)[0]];
                $selected = ($fkId == $value) ? 'selected' : '';
                echo '<option value="' . $fkId . '" ' . $selected . '>' . htmlspecialchars($displayField) . '</option>';
            }
            echo '</select><br>';
        } else {
            $inputType = 'text';
            
            if (($tableName == 'Vehicles' && $label == 'Image') || 
                ($tableName == 'Customers' && $label == 'LicenseImg') || 
                ($tableName == 'Issues' && $label == 'Proof')) {
                
                if (!empty($value) && file_exists($value)) {
                    echo '<br><img src="' . htmlspecialchars($value) . '" alt="Current Image" style="width:100px; height:100px; object-fit:cover; border-radius:5px;"><br>';
                    echo '<small>Current image</small><br>';
                }
                echo '<input type="file" name="' . $label . '" accept="image/*"><br>';
                echo '<small>Leave empty to keep current image</small><br>';
                continue;
            }
            
            if (strpos($label, 'Date') !== false) {
                $inputType = 'date';
            } elseif (in_array($label, ['DailyRate', 'TotalCost'])) {
                $inputType = 'number';
                echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" step="0.01" required><br>';
                continue;
            } elseif (in_array($label, ['KmBefore', 'KmAfter', 'KmUsed', 'NoOfDays'])) {
                $inputType = 'number';
                $onChangeEvent = '';
                if ($tableName == 'Distances' && ($label == 'KmBefore' || $label == 'KmAfter')) {
                    $onChangeEvent = ' onchange="calculateKmUsed()" oninput="calculateKmUsed()"';
                }
                echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" required' . $onChangeEvent . '><br>';
                continue;
            } elseif ($label == 'Description') {
                echo '<textarea name="' . $label . '" rows="3" cols="30" required>' . htmlspecialchars($value) . '</textarea><br>';
                continue;
            }

            echo '<input type="' . $inputType . '" name="' . $label . '" value="' . htmlspecialchars($value) . '" required><br>';
        }
    }

    echo '<br><button type="submit">Save Changes</button>';
    echo '</form>';
    echo '<br><a href="dashboard.php?table=' . $tableName . '">← Back to ' . ucfirst($tableName) . ' List</a>';

    $conn->close();
    ?>

</body>

</html>