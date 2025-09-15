<!DOCTYPE html>
<html>

<head>
    <title>Add Record</title>
    <link href="styles.css" rel="stylesheet">
    <script>
        function calcRent() {
            const pickUpDate = document.querySelector('input[name="PickUpDate"]');
            const toReturnDate = document.querySelector('input[name="ToReturnDate"]');
            const vehicleSelect = document.querySelector('select[name="VehicleID"]');
            
            if (pickUpDate && toReturnDate && vehicleSelect && 
                pickUpDate.value && toReturnDate.value && vehicleSelect.value) {
                
                const pickUp = new Date(pickUpDate.value);
                const returnDate = new Date(toReturnDate.value);
                
                const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
                const rate = selectedOption && selectedOption.dataset.rate ? parseFloat(selectedOption.dataset.rate) : 0;
                
                
                if (pickUp && returnDate && pickUp <= returnDate && rate > 0) {
                    const timeDiff = returnDate.getTime() - pickUp.getTime();
                    const noOfDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    const totalCost = noOfDays * rate;
                    
                    const costDisplay = document.getElementById('calculated-cost');
                    if (costDisplay) {
                        costDisplay.textContent = '₱' + totalCost.toFixed(2);
                    }
                }
            }
        }
        
        function carRate() {
            calcRent();
        }
        
        function formatPlateNumber(input) {
            let value = input.value.replace(/[^A-Za-z0-9]/g, '');
            
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            
            let letters = value.substring(0, 3).replace(/[^A-Za-z]/g, '').toUpperCase();
            
            let numbers = value.substring(3, 6).replace(/[^0-9]/g, '');
            
            if (letters.length > 0 && numbers.length > 0) {
                input.value = letters + ' ' + numbers;
            } else if (letters.length > 0) {
                input.value = letters;
            }
        }
        
        function validatePlateNumber(input) {
            const platePattern = /^[A-Z]{3} [0-9]{3}$/;
            const isValid = platePattern.test(input.value);
            
            if (!isValid && input.value.length > 0) {
                input.setCustomValidity('Plate number must be in format: ABC 123 (3 letters, space, 3 numbers)');
            } else {
                input.setCustomValidity('');
            }
        }
    </script>
</head>

<body>
    <h2>Add New Record</h2>

    <?php
    include 'conn.php';

    $tableName = $_GET['table'] ?? "Rentals";
    $pkey = substr($tableName, 0, -1) . 'ID';

    $foreignKeys = [
        'Rentals' => ['CustomerID' => 'customers', 'VehicleID' => 'vehicles'],
        'Distances' => ['RentalID' => 'Rentals', 'VehicleID' => 'vehicles'],
        'Issues' => ['RentalID' => 'Rentals', 'VehicleID' => 'vehicles', 'CustomerID' => 'customers'],
    ];

    $sql = "SELECT * FROM " . $tableName . " LIMIT 1";
    $result = $conn->query($sql);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fields = [];
        $placeholders = [];
        $types = '';
        $values = [];

        if ($tableName == 'Rentals') {
            $pickUpDate = $_POST['PickUpDate'];
            $toReturnDate = $_POST['ToReturnDate'];
            $vehicleID = $_POST['VehicleID'];

            $vehicleQuery = $conn->prepare("SELECT DailyRate FROM vehicles WHERE VehicleID = ?");
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

            $totalCost = $noOfDays * $dailyRate;

            $_POST['NoOfDays'] = $noOfDays;
            $_POST['TotalCost'] = $totalCost;
            $_POST['DailyRate'] = $dailyRate;
        }

        $imageFields = [
            'vehicles' => 'Image',
            'customers' => 'LicenseImg', 
            'issues' => 'Proof'
        ];

        if (isset($imageFields[strtolower($tableName)])) {
            $imageField = $imageFields[strtolower($tableName)];
            $folderMap = [
                'Image' => 'vehicles/',
                'LicenseImg' => 'profiles/',
                'Proof' => 'issues/'
            ];
            
            $targetDir = $folderMap[$imageField];
            
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
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
                        exit();
                    }
                } else {
                    echo "<p style='color:red;'>Only JPG, JPEG, PNG, GIF files are allowed.</p>";
                    exit();
                }
            }
        }

        $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
        while ($fieldinfo = $fieldResult->fetch_field()) {
            $fieldName = $fieldinfo->name;

            $fields[] = $fieldName;
            $placeholders[] = '?';

            if (strpos($fieldName, 'Date') !== false) {
                $types .= 's';
            } elseif (in_array($fieldName, ['DailyRate', 'TotalCost'])) {
                $types .= 'd';
            } elseif (strpos($fieldName, 'ID') !== false || in_array($fieldName, ['KmBefore', 'KmAfter', 'KmUsed', 'NoOfDays'])) {
                $types .= 'i';
            } else {
                $types .= 's';
            }

            if (isset($_POST[$fieldName])) {
                $values[] = $_POST[$fieldName];
            } else {
                if ($tableName == 'Rentals') {
                    if ($fieldName == 'ReturnedDate') {
                        $values[] = '0000-00-00';
                    } elseif ($fieldName == 'TotalCost') {
                        $values[] = 0;
                    } elseif ($fieldName == 'NoOfDays') {
                        $values[] = 1;
                    } else {
                        $values[] = '';
                    }
                } elseif ($tableName == 'vehicles') {
                    if ($fieldName == 'Status') {
                        $values[] = 'Available';
                    } else {
                        $values[] = '';
                    }
                } elseif ($tableName == 'issues') {
                    if ($fieldName == 'DateResolved') {
                        $values[] = '0000-00-00';
                    } elseif ($fieldName == 'Status') {
                        $values[] = 'Pending';
                    } else {
                        $values[] = '';
                    }
                } elseif ($tableName == 'distance') {
                    if ($fieldName == 'KmAfter') {
                        $values[] = 0;
                    } elseif ($fieldName == 'KmUsed') {
                        $values[] = 0;
                    } else {
                        $values[] = '';
                    }
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
                echo "<p style='color:green;'>New record added successfully!</p>";
                
                if ($tableName == 'Rentals' && isset($_POST['VehicleID'])) {
                    $vehicleID = $_POST['VehicleID'];
                    $updateVehicleQuery = $conn->prepare("UPDATE vehicles SET Status = 'Rented' WHERE VehicleID = ?");
                    $updateVehicleQuery->bind_param('i', $vehicleID);
                    $updateVehicleQuery->execute();
                    $updateVehicleQuery->close();
                }
            } else {
                echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
            }

            $stmt->close();
        } else {
            echo "<p style='color:red;'>No fields to insert.</p>";
        }
    }

    if ($result && $result->num_rows >= 0) {
        echo '<form method="POST" action="" enctype="multipart/form-data">';

        $fieldResult = $conn->query("SELECT * FROM " . $tableName . " LIMIT 1");
        while ($fieldinfo = $fieldResult->fetch_field()) {
            $label = $fieldinfo->name;

            if ($label == $pkey)
                continue;

            $skipFields = [
                'Rentals' => ['ReturnedDate', 'TotalCost', 'NoOfDays'],
                'Vehicles' => ['Status'],
                'Issues' => ['DateResolved', 'Status'],
                'Distance' => ['KmAfter', 'KmUsed'],
            ];

            $tbSkipField = $skipFields[$tableName] ?? [];
            if (in_array($label, $tbSkipField)) continue;

            $pk = ['CustomerID', 'VehicleID', 'DistanceID', 'IsssueID', 'RentalID'];
            if (in_array($label, $pk)) {
                echo '<label>' . substr($label, 0, -2) . ':</label>';
            } else {
                echo '<label>' . $label . ':</label>';
            }

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
                $fkTable = 'Rentals';
            }

            if ($isForeignKey) {
                if ($tableName == 'Rentals' && $fkTable == 'vehicles') {
                    $fkResult = $conn->query("SELECT * FROM $fkTable WHERE Status = 'Available'");
                } else {
                    $fkResult = $conn->query("SELECT * FROM $fkTable");
                }

                $selectEvent = '';
                if ($tableName == 'Rentals' && $label == 'VehicleID') {
                    $selectEvent = ' onchange="carRate()"';
                }

                echo '<select name="' . $label . '" required' . $selectEvent . '>';
                echo '<option value="">Select ' . ucfirst(str_replace('ID', '', $label)) . '...</option>';
                while ($fkRow = $fkResult->fetch_assoc()) {
                    $displayField = '';
                    $dataAttributes = '';
                    
                    if ($fkTable == 'customers') {
                        $displayField = $fkRow['Name'] . ' (' . $fkRow['Email'] . ')';
                    } elseif ($fkTable == 'vehicles') {
                        $displayField = $fkRow['Model'] . ' - ' . $fkRow['PlateNumber'] . ' (₱' . $fkRow['DailyRate'] . ')';
                        $dataAttributes = ' data-rate="' . $fkRow['DailyRate'] . '"';
                    } elseif ($fkTable == 'Rentals') {
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
                    echo '<option value="' . $fkId . '"' . $dataAttributes . '>' . htmlspecialchars($displayField) . '</option>';
                }
                echo '</select><br>';
                } else {
                $inputType = 'text';
                $onChangeEvent = '';
                
                if ($tableName == 'Rentals' && ($label == 'PickUpDate' || $label == 'ToReturnDate')) {
                    $onChangeEvent = ' onchange="calcRent()"';
                }
                
                if (($tableName == 'Vehicles' && $label == 'Image') || 
                    ($tableName == 'Customers' && $label == 'LicenseImg') || 
                    ($tableName == 'Issues' && $label == 'Proof')) {
                    echo '<input type="file" name="' . $label . '" accept="image/*" required><br>';
                    continue;
                }
                
                if (strpos($label, 'Date') !== false) {
                    $inputType = 'date';
                } elseif ($label == 'PlateNumber' && $tableName == 'Vehicles') {
                    echo '<input type="text" name="' . $label . '" maxlength="7" placeholder="ABC 123" pattern="[A-Z]{3} [0-9]{3}" oninput="formatPlateNumber(this)" onblur="validatePlateNumber(this)" required><br>';
                    continue;
                } elseif ($label == 'DailyRate') {
                    $inputType = 'number';
                    echo '<input type="' . $inputType . '" name="' . $label . '" step="0.01" required' . $onChangeEvent . '><br>';
                    continue;
                } elseif (in_array($label, ['KmBefore', 'KmAfter', 'KmUsed'])) {
                    $inputType = 'number';
                } elseif ($label == 'Description') {
                    echo '<textarea name="' . $label . '" rows="3" cols="30" required></textarea><br>';
                    continue;
                }

                echo '<input type="' . $inputType . '" name="' . $label . '" required' . $onChangeEvent . '><br>';
            }
        }

        if ($tableName == 'Rentals') {
            echo '<p><strong>Total Cost: <span id="calculated-cost">₱0.00</span></strong></p>';
        }

        echo '<br><input type="submit" value="Add Record">';
        echo '</form>';
        echo '<br><a href="dashboard.php?table=' . $tableName . '">← Back to ' . ucfirst($tableName) . ' List</a>';
    } else {
        echo "Error accessing table: " . $tableName;
    }

    $conn->close();
    ?>

</body>

</html>