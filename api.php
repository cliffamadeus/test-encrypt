<?php
// api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

define('SECRET_KEY', 'your-secret-key');
define('SECRET_IV', 'your-secret-iv');
define('API_TOKEN', 'sample-api-token'); // Token for API access

// Database credentials
$host = 'localhost';
$dbname = 'api_db';
$username = 'root';
$password = '';

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Encryption and decryption functions
function encryptData($data) {
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv));
}

function decryptData($encryptedData) {
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    return openssl_decrypt(base64_decode($encryptedData), 'AES-256-CBC', $key, 0, $iv);
}

// Token validation function
function validateToken() {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_TOKEN) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// Handle requests
$method = $_SERVER['REQUEST_METHOD'];
validateToken(); // Validate token for all requests

if ($method === 'POST') { // Create
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? null;
    $data = $input['data'] ?? null;

    if ($name && $data) {
        $encryptedData = encryptData($data);

        $stmt = $pdo->prepare("INSERT INTO items (name, encrypted_data) VALUES (:name, :data)");
        $stmt->execute(['name' => $name, 'data' => $encryptedData]);

        echo json_encode(['id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
} elseif ($method === 'GET') { // Read
    $stmt = $pdo->query("SELECT * FROM items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['data'] = decryptData($item['encrypted_data']);
        unset($item['encrypted_data']);
    }

    echo json_encode($items);
} elseif ($method === 'PUT') { // Update
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $name = $input['name'] ?? null;
    $data = $input['data'] ?? null;

    if ($id && $name && $data) {
        $encryptedData = encryptData($data);

        $stmt = $pdo->prepare("UPDATE items SET name = :name, encrypted_data = :data WHERE id = :id");
        $stmt->execute(['id' => $id, 'name' => $name, 'data' => $encryptedData]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
} elseif ($method === 'DELETE') { // Delete
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
        $stmt->execute(['id' => $id]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
