<?php
// delete_listing.php
session_start();
require_once '../db/config.php';

// Check if user is logged in and is a host
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'host') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_POST['listing_id']) || !is_numeric($_POST['listing_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid listing ID']);
    exit;
}

$listing_id = intval($_POST['listing_id']);
$user_id = $_SESSION['user_id'];

try {
    // First verify that the listing belongs to this host
    $stmt = $conn->prepare("SELECT host_id FROM listings WHERE id = ?");
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $listing = $result->fetch_assoc();
    
    if (!$listing || $listing['host_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }
    
    // Delete the listing (cascade will handle related records)
    $stmt = $conn->prepare("DELETE FROM listings WHERE id = ? AND host_id = ?");
    $stmt->bind_param("ii", $listing_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete listing']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>