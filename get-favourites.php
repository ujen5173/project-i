<?php
// get-favorites.php
session_start();
require_once __DIR__ . '/db/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$favorites = $data['favorites'] ?? [];

if (empty($favorites)) {
    echo json_encode([]);
    exit();
}

// Convert array of IDs to string for SQL IN clause
$ids = implode(',', array_map('intval', $favorites));

// Fetch listings
$query = "SELECT id, title, location, price, image_url FROM listings WHERE id IN ($ids)";
$result = $conn->query($query);

if ($result) {
    $listings = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($listings);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch favorites']);
}

$conn->close();
?>