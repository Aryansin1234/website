<?php
declare(strict_types=1);

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
    if (!isset($_POST['formType']) || $_POST['formType'] !== 'passForm') {
        throw new Exception('Invalid form submission.');
    }

    // Validate and sanitize input data
    $data = [
        'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
        'contact' => filter_input(INPUT_POST, 'contact', FILTER_SANITIZE_STRING),
        'houseNumber' => filter_input(INPUT_POST, 'houseNumber', FILTER_SANITIZE_STRING),
        'lane' => filter_input(INPUT_POST, 'lane', FILTER_SANITIZE_STRING),
        'city' => filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING),
        'state' => filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING),
        'pincode' => filter_input(INPUT_POST, 'pincode', FILTER_SANITIZE_STRING)
    ];

    // Validate required fields
    foreach ($data as $key => $value) {
        if (empty($value)) {
            throw new Exception("$key is required.");
        }
    }

    // Validate contact number (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $data['contact'])) {
        throw new Exception('Invalid contact number format.');
    }

    // Validate pincode (6 digits)
    if (!preg_match('/^[0-9]{6}$/', $data['pincode'])) {
        throw new Exception('Invalid pincode format.');
    }

    // Generate a unique pass ID
    $pass_id = 'PASS' . date('Y') . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    // Prepare and execute the SQL query
    $sql = "INSERT INTO free_passes (
        pass_id, full_name, contact_number, house_number, 
        lane, city, state, pincode
    ) VALUES (
        :pass_id, :full_name, :contact_number, :house_number, 
        :lane, :city, :state, :pincode
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'pass_id' => $pass_id,
        'full_name' => $data['name'],
        'contact_number' => $data['contact'],
        'house_number' => $data['houseNumber'],
        'lane' => $data['lane'],
        'city' => $data['city'],
        'state' => $data['state'],
        'pincode' => $data['pincode']
    ]);

    // Generate pass card HTML
    $passCard = <<<HTML
    <div class="pass-card bg-white p-6 rounded-lg shadow-lg">
        <div class="text-center mb-4">
            <h3 class="text-2xl font-bold text-[#ed1f24]">Free Pass</h3>
            <p class="text-gray-600">Pass ID: {$pass_id}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="font-semibold">Name:</p>
                <p>{$data['name']}</p>
            </div>
            <div>
                <p class="font-semibold">Contact:</p>
                <p>{$data['contact']}</p>
            </div>
            <div class="col-span-2">
                <p class="font-semibold">Address:</p>
                <p>{$data['houseNumber']}, {$data['lane']}</p>
                <p>{$data['city']}, {$data['state']} - {$data['pincode']}</p>
            </div>
        </div>
        <div class="mt-4 text-center text-sm text-gray-500">
            <p>Valid for one-time use only</p>
            <p>Issue Date: " . date('d-m-Y') . "</p>
        </div>
    </div>
    HTML;

    // Send success response with pass card
    echo json_encode([
        'success' => true,
        'message' => 'Free pass generated successfully!',
        'passCard' => $passCard,
        'passId' => $pass_id
    ]);

} catch (Exception $e) {
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}