<?php
session_start();
require_once '../db/config.php';

// Check if user is logged in and is a host
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'host') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['listing_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

$listing_id = $_POST['listing_id'];
$status = $_POST['status'];
$host_id = $_SESSION['user_id'];

// Verify the listing belongs to the host
$stmt = $conn->prepare("SELECT id FROM listings WHERE id = ? AND host_id = ?");
$stmt->bind_param("ii", $listing_id, $host_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Listing not found or unauthorized']);
    exit();
}

// Update the listing status
$stmt = $conn->prepare("UPDATE listings SET status = ? WHERE id = ? AND host_id = ?");
$stmt->bind_param("sii", $status, $listing_id, $host_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close(); 