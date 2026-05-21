<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Connection Settings
$host = 'sql111.infinityfree.com';
$user = 'if0_41972701';
$pass = '5LA9TX6GtjBPlc'; 
$dbname = 'if0_41972701_amj_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database Connection Failed: " . $conn->connect_error]);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Simple Routing Setup
if (strpos($request_uri, 'api.php/enquiries') !== false) {
    if ($method === 'GET') {
        // Fetch all enquiries from the newly updated table name
        $result = $conn->query("SELECT * FROM amj_enquiries ORDER BY date DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
    } 
    elseif ($method === 'POST') {
        // FIXED: Added missing POST endpoint block to receive website enquiries
        $input = json_decode(file_get_contents('php://input'), true);
        
        $type = $input['type'] ?? 'general';
        $part = $input['part'] ?? '';
        $name = $input['name'] ?? '';
        $phone = $input['phone'] ?? '';
        $email = $input['email'] ?? '';
        $message = $input['message'] ?? '';
        $status = 'new'; // Default incoming enquiry state
        
        // Use current timestamp if client didn't supply an explicit date string
        $date = $input['date'] ?? date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO amj_enquiries (type, part, name, phone, email, message, status, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $type, $part, $name, $phone, $email, $message, $status, $date);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
    }
    elseif ($method === 'PUT') {
        // Update enquiry status in amj_enquiries
        preg_match('/enquiries\/(\d+)\/status/', $request_uri, $matches);
        $id = $matches[1] ?? null;
        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? 'new';
        
        if ($id) {
            $stmt = $conn->prepare("UPDATE amj_enquiries SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Enquiry identifier not resolved."]);
        }
    }
} 
elseif (strpos($request_uri, 'api.php/parts') !== false) {
    if ($method === 'GET') {
        // Fetch all spare parts
        $result = $conn->query("SELECT * FROM parts ORDER BY id DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
    } 
    elseif ($method === 'POST') {
        // Add new spare part
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO parts (name, category, price, badge, description, image) VALUES (?, ?, ?, ?, ?, ?)");
        
        $name = $input['name'] ?? '';
        $category = $input['category'] ?? '';
        $price = $input['price'] ?? 'TBD';
        $badge = $input['badge'] ?? '';
        $description = $input['description'] ?? '';
        $image = $input['image'] ?? '';

        $stmt->bind_param("ssssss", $name, $category, $price, $badge, $description, $image);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
    } 
    elseif ($method === 'DELETE') {
        // Delete a spare part
        $parts = explode('/', strtok($request_uri, '?'));
        $id = end($parts);
        if (is_numeric($id)) {
            $stmt = $conn->prepare("DELETE FROM parts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Invalid Part ID format"]);
        }
    }
}

$conn->close();
?>