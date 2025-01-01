<?php 
session_start();
require_once __DIR__ . '/db/config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['room_id'])) {
    header("Location: login.php");
    exit();
}

$room_id = intval($_POST['room_id']);
$guest_id = $_SESSION['user_id'];
$check_in = $_POST['check_in'];
$check_out = $_POST['check_out'];

$stmt = $conn->prepare("SELECT price FROM listings WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$check_in_date = new DateTime($check_in);
$check_out_date = new DateTime($check_out);
$nights = $check_out_date->diff($check_in_date)->days;
$total_price = $listing['price'] * $nights;

$stmt = $conn->prepare("SELECT id FROM bookings WHERE listing_id = ? AND 
    ((check_in BETWEEN ? AND ?) OR (check_out BETWEEN ? AND ?))
    AND status != 'cancelled'");
$stmt->bind_param("issss", $room_id, $check_in, $check_out, $check_in, $check_out);
$stmt->execute();
$existing_booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing_booking) {
    $_SESSION['error'] = "Selected dates are not available";
    header("Location: booking-form.php?id=" . $room_id);
    exit();
}

$stmt = $conn->prepare("INSERT INTO bookings (listing_id, guest_id, check_in, check_out, total_price, status) 
    VALUES (?, ?, ?, ?, ?, 'confirmed')");
$stmt->bind_param("iissd", $room_id, $guest_id, $check_in, $check_out, $total_price);

if ($stmt->execute()) {
    $_SESSION['success'] = "Booking confirmed! Please pay $" . number_format($total_price, 2) . " in cash upon arrival.";
    header("Location: booking-success.php");
} else {
    $_SESSION['error'] = "Error creating booking";
    header("Location: booking-form.php?id=" . $room_id);
}
$stmt->close();
?>