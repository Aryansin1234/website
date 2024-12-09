<?php
header('Content-Type: application/json');

try {
    // Database configuration
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'forms_db',
        'username' => 'root',
        'password' => ''
    ];

    // Create PDO connection
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Validate form type
    if (!isset($_POST['formType']) || $_POST['formType'] !== 'joinForm') {
        throw new Exception('Invalid form submission.');
    }

    // Required fields validation
    $required_fields = ['name', 'contact', 'email', 'bloodGroup', 'profession', 
                       'houseNumber', 'lane', 'city', 'state', 'pincode', 'interest'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required.");
        }
    }

    // Handle the 'others' interest case
    $interest = $_POST['interest'];
    if ($interest === 'others' && !empty($_POST['otherInterest'])) {
        $interest = $_POST['otherInterest'];
    }

    // Prepare the full address
    $address = implode(', ', [
        $_POST['houseNumber'],
        $_POST['lane'],
        $_POST['city'],
        $_POST['state'],
        $_POST['pincode']
    ]);

    // Generate a unique member ID (you can modify this format as needed)
    $member_id = 'GACT' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Prepare and execute the SQL query
    $sql = "INSERT INTO members (
        member_id, name, contact, email, blood_group, profession, 
        address, interest, created_at
    ) VALUES (
        :member_id, :name, :contact, :email, :blood_group, :profession, 
        :address, :interest, NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'member_id' => $member_id,
        'name' => $_POST['name'],
        'contact' => $_POST['contact'],
        'email' => $_POST['email'],
        'blood_group' => $_POST['bloodGroup'],
        'profession' => $_POST['profession'],
        'address' => $address,
        'interest' => $interest
    ]);

    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'member_id' => $member_id
    ]);

} catch (Exception $e) {
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}