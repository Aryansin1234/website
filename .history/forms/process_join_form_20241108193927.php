<?php
// process_join_form.php

// Database configuration
$host = 'localhost';
$dbname = 'forms_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['formType'] == 'joinForm') {
        // Updated sanitization for PHP 8.2
        $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $contact = htmlspecialchars(trim($_POST['contact'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $houseNumber = htmlspecialchars(trim($_POST['houseNumber'] ?? ''), ENT_QUOTES, 'UTF-8');
        $lane = htmlspecialchars(trim($_POST['lane'] ?? ''), ENT_QUOTES, 'UTF-8');
        $city = htmlspecialchars(trim($_POST['city'] ?? ''), ENT_QUOTES, 'UTF-8');
        $state = htmlspecialchars(trim($_POST['state'] ?? ''), ENT_QUOTES, 'UTF-8');
        $pincode = htmlspecialchars(trim($_POST['pincode'] ?? ''), ENT_QUOTES, 'UTF-8');
        $interest = htmlspecialchars(trim($_POST['interest'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bloodGroup = htmlspecialchars(trim($_POST['bloodGroup'] ?? ''), ENT_QUOTES, 'UTF-8');
        $otherInterest = isset($_POST['otherInterest']) ? 
            htmlspecialchars(trim($_POST['otherInterest']), ENT_QUOTES, 'UTF-8') : null;
        $profession = htmlspecialchars(trim($_POST['profession'] ?? ''), ENT_QUOTES, 'UTF-8');

        // Input validation
        if (empty($name) || empty($contact) || empty($email) || empty($houseNumber) || 
            empty($lane) || empty($city) || empty($state) || empty($pincode) || 
            empty($interest) || empty($bloodGroup) || empty($profession)) {
            echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
            exit;
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email format']);
            exit;
        }

        // Contact number validation
        if (!preg_match("/^[0-9]{10}$/", $contact)) {
            echo json_encode(['success' => false, 'error' => 'Invalid contact number']);
            exit;
        }

        // Pincode validation
        if (!preg_match("/^[0-9]{6}$/", $pincode)) {
            echo json_encode(['success' => false, 'error' => 'Invalid pincode']);
            exit;
        }

        // Combine address components
        $address = sprintf("House No: %s, %s, %s, %s - %s",
            $houseNumber, $lane, $city, $state, $pincode);

        // Database insertion with prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO members (
                name, contact, email, address, house_number, 
                lane, city, state, pincode, interest, 
                blood_group, other_interest, profession
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?
            )
        ");

        $success = $stmt->execute([
            $name, $contact, $email, $address, $houseNumber,
            $lane, $city, $state, $pincode, $interest,
            $bloodGroup, $otherInterest, $profession
        ]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save data']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A database error occurred']);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}
?>