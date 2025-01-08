<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db/config.php';
require_once 'EsewaPayment.php';

function validateBookingDates($conn, $listing_id, $check_in, $check_out) {
    // Check for conflicting bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE listing_id = ?
        AND (
            (check_in BETWEEN ? AND ?) OR
            (check_out BETWEEN ? AND ?) OR
            (? BETWEEN check_in AND check_out) OR
            (? BETWEEN check_in AND check_out)
        )
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare validation statement: " . $conn->error);
    }
    
    $stmt->bind_param("issssss", $listing_id, $check_in, $check_out, $check_in, $check_out, $check_in, $check_out);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    
    return $count === 0;
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
    $required_fields = ['room_id', 'check_in', 'check_out', 'amount', 'payment_method'];
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
    if (!validateBookingDates($conn, $room_id, $check_in, $check_out)) {
        throw new Exception('Selected dates are not available');
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert booking
    $insert_sql = "
        INSERT INTO bookings 
        (listing_id, guest_id, check_in, check_out, total_price, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $stmt->bind_param(
        "iissds",
        $room_id,
        $user_id,
        $check_in,
        $check_out,
        $amount,
        $payment_method
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert booking: " . $stmt->error);
    }

    $booking_id = $conn->insert_id;
    $stmt->close();

    // Generate response based on payment method
    if ($payment_method === 'esewa') {
        try {
            $esewa = new EsewaPayment($amount, "BOOK" . $booking_id);
            $response = [
                'success' => true,
                'booking_id' => $booking_id,
                'esewaForm' => $esewa->getPaymentForm()
            ];
            // Only commit if payment initialization is successful
            $conn->commit();
        } catch (Exception $e) {
            // If payment initialization fails, rollback the booking
            $conn->rollback();
            throw new Exception("Payment initialization failed: " . $e->getMessage());
        }
    } else {
        // For other payment methods
        $conn->commit();
        $response = [
            'success' => true,
            'booking_id' => $booking_id
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        // Ensure any pending transaction is rolled back
        $conn->rollback();
        
        // If a booking ID exists, explicitly delete the booking
        if (isset($booking_id)) {
            $delete_sql = "DELETE FROM bookings WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            if ($stmt) {
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    error_log("Payment Processing Error: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}