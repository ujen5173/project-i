<?php
// esewa-success.php
session_start();
require_once __DIR__ . '/db/config.php';
require_once 'EsewaPayment.php';

try {
    if (!isset($_GET['oid']) || !isset($_GET['refId']) || !isset($_SESSION['pending_booking'])) {
        throw new Exception('Invalid payment response or missing booking data');
    }

    $oid = $_GET['oid'];
    $refId = $_GET['refId'];
    $pendingBooking = $_SESSION['pending_booking'];
    
    // Verify payment
    $esewa = new EsewaPayment($pendingBooking['amount'], $oid);
    $verified = $esewa->verifyPayment($refId, $oid);
    
    if ($verified) {
        $conn->begin_transaction();
        
        $insert_sql = "
            INSERT INTO bookings 
            (listing_id, guest_id, check_in, check_out, total_price, payment_method, payment_reference, room_quantity) 
            VALUES (?, ?, ?, ?, ?, 'esewa', ?, ?)
        ";
        
        $stmt = $conn->prepare($insert_sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare booking statement");
        }
        
        $stmt->bind_param(
            "iissdsi",
            $pendingBooking['room_id'],
            $pendingBooking['user_id'],
            $pendingBooking['check_in'],
            $pendingBooking['check_out'],
            $pendingBooking['amount'],
            $refId,
            $pendingBooking['room_quantity']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create booking");
        }

        $booking_id = $conn->insert_id;
        $stmt->close();
        $conn->commit();

        // Clear pending booking data
        unset($_SESSION['pending_booking']);
        
        header("Location: booking-success.php?id=" . $booking_id);
        exit();
    } else {
        throw new Exception('Payment verification failed');
    }
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    error_log("eSewa Payment Error: " . $e->getMessage());
    header("Location: booking-failed.php?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

?>