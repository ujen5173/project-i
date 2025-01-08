<?php
session_start();
require_once __DIR__ . '/db/config.php';

try {
if (!isset($_GET['oid'])) {
throw new Exception('Invalid payment response');
}

$oid = $_GET['oid'];
$booking_id = substr($oid, 4);


header("Location: booking-failed.php?id=" . $booking_id);
exit();

} catch (Exception $e) {
error_log("eSewa Payment Error: " . $e->getMessage());
header("Location: booking-failed.php?error=" . urlencode($e->getMessage()));
exit();
}
?>