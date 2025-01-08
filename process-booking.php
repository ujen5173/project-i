<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db/config.php';
require_once 'EsewaPayment.php';

function validateBookingDates($conn, $listing_id, $check_in, $check_out, $requested_quantity) {
    $stmt = $conn->prepare("
        SELECT l.quantity - COALESCE(SUM(b.room_quantity), 0) as available_rooms
        FROM listings l
        LEFT JOIN bookings b ON l.id = b.listing_id
        AND (
            (b.check_in BETWEEN ? AND ?) OR
            (b.check_out BETWEEN ? AND ?) OR
            (? BETWEEN b.check_in AND b.check_out) OR
            (? BETWEEN b.check_in AND b.check_out)
        )
        WHERE l.id = ?
        GROUP BY l.id
    ");
    
    $stmt->bind_param("ssssssi", $check_in, $check_out, $check_in, $check_out, $check_in, $check_out, $listing_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $availability = $result->fetch_assoc();
    $stmt->close();
    
    return $availability && $availability['available_rooms'] >= $requested_quantity;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    $required_fields = ['room_id', 'check_in', 'check_out', 'amount', 'payment_method', 'room_quantity'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    $room_id = intval($data['room_id']);
    $user_id = intval($_SESSION['user_id']);
    $check_in = $data['check_in'];
    $check_out = $data['check_out'];
    $amount = floatval($data['amount']);
    $payment_method = $data['payment_method'];

    // Validate dates
    if (!validateBookingDates($conn, $room_id, $check_in, $check_out, $data['room_quantity'])) {
        throw new Exception('Selected dates are not available');
    }

    // For eSewa payments, only initialize payment without creating booking
    if ($payment_method === 'esewa') {
        $tempBookingId = uniqid('TEMP_');
        $esewa = new EsewaPayment($amount, $tempBookingId);
        
        // Store booking data in session for later use
        $_SESSION['pending_booking'] = [
            'room_id' => $room_id,
            'user_id' => $user_id,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'temp_id' => $tempBookingId
        ];

        echo json_encode([
            'success' => true,
            'payment_required' => true,
            'esewaForm' => $esewa->getPaymentForm()
        ]);
        exit();
    }

    // For cash payments, create booking directly
    $conn->begin_transaction();

    // Insert booking
    $insert_sql = "
        INSERT INTO bookings 
        (listing_id, guest_id, check_in, check_out, total_price, payment_method, room_quantity) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $stmt->bind_param(
        "iissdsi",
        $room_id,
        $user_id,
        $check_in,
        $check_out,
        $amount,
        $payment_method,
        $data['room_quantity']
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert booking: " . $stmt->error);
    }

    $booking_id = $conn->insert_id;
    $stmt->close();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    error_log("Booking Error: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}