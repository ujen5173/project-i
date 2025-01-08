<?php
// esewa-success.php
session_start();
require_once __DIR__ . '/db/config.php';
require_once 'EsewaPayment.php';

try {
    if (!isset($_GET['oid']) || !isset($_GET['refId'])) {
        throw new Exception('Invalid payment response');
    }

    $oid = $_GET['oid'];
    $refId = $_GET['refId'];
    
    // Extract booking ID from oid (removes 'BOOK' prefix)
    $booking_id = substr($oid, 4);
    
    // Verify payment
    $esewa = new EsewaPayment(0, $oid); // Amount not needed for verification
    $verified = $esewa->verifyPayment($refId, $oid);
    
    if ($verified) { 
        
        header("Location: booking-success.php?id=" . $booking_id);
        exit();
    } else {
        throw new Exception('Payment verification failed');
    }
    
} catch (Exception $e) {
    error_log("eSewa Payment Error: " . $e->getMessage());
    header("Location: booking-failed.php?error=" . urlencode($e->getMessage()));
    exit();
}

?>