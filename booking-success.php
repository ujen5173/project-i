<?php
session_start();
require_once __DIR__ . '/db/config.php';

// Assuming booking details are stored in session
$bookingDetails = $_SESSION['booking_details'] ?? null;

// Function to get user details
function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userDetails = null;

if ($isLoggedIn) {
    $userDetails = getUserDetails($conn, $_SESSION['user_id']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Confirmation - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/bookings.css">
</head>

<body>
  <header class="bg-white border-b border-slate-200">
    <nav class="container mx-auto px-4">
      <div class="flex items-center justify-between h-16 w-full">
        <div class="flex items-center gap-8">
          <a href="/stayHaven/index.php" class="text-xl font-bold text-rose-600">
            StayHaven
          </a>
          <div class="hidden md:block">
            <ul class="flex items-center gap-6">
              <li>
                <a href="/stayhaven/index.php" class="text-slate-600 hover:text-slate-900">Home</a>
              </li>
              <li>
                <a href="/stayhaven/listings.php" class="text-slate-600 hover:text-slate-900">Listings</a>
              </li>
            </ul>
          </div>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="flex items-center gap-4">
          <a href="/stayhaven/login.php" class="text-slate-600 hover:text-slate-900">Login</a>
          <a href="/stayhaven/signup.php"
            class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg transition-colors">Register</a>
        </div>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <main class="bg-gray-50 h-[60vh] flex items-center justify-center">
    <div class="max-w-2xl mx-auto p-6 text-center">
      <h1 class="text-4xl font-bold text-green-600 mb-4">Booking Confirmed!</h1>

      <?php if (isset($_SESSION['success'])): ?>
      <div class="bg-green-100 text-green-700 p-6 rounded mb-4">
        <!-- Increased padding for height -->
        <?php 
          echo $_SESSION['success'];
          unset($_SESSION['success']);
        ?>
      </div>
      <?php endif; ?>

      <?php if ($userDetails): ?>
      <div class="bg-white shadow-md border border-border rounded-lg p-4 mb-6">
        <h2 class="text-xl font-semibold">Hello, <?= htmlspecialchars($userDetails['name']); ?>!</h2>
        <p class="text-sm text-gray-600">Your email: <?= htmlspecialchars($userDetails['email']); ?></p>
      </div>
      <?php endif; ?>

      <?php if ($bookingDetails): ?>
      <div class="bg-white shadow-md rounded-lg p-4 mb-6 border border-border">
        <h2 class="text-xl font-semibold">Booking Summary</h2>
        <p class="text-sm text-gray-600">Property: <?= htmlspecialchars($bookingDetails['property_name']); ?></p>
        <p class="text-sm text-gray-600">Check-in: <?= htmlspecialchars($bookingDetails['check_in']); ?></p>
        <p class="text-sm text-gray-600">Check-out: <?= htmlspecialchars($bookingDetails['check_out']); ?></p>
        <p class="text-sm text-gray-600">Guests: <?= htmlspecialchars($bookingDetails['guests']); ?></p>
      </div>
      <?php endif; ?>

      <p class="mb-6">Thank you for choosing StayHaven!</p>

      <div class="space-y-4">
        <a href="index.php" class="inline-block bg-rose-600 text-white py-2 px-6 rounded hover:bg-rose-700">
          Return to Home
        </a>

        <a href="bookings.php" class="block text-rose-600 hover:underline">
          View My Bookings
        </a>
      </div>

  </main>
  <footer class="footer">
    <div class="footer__wrapper container">
      <div class="footer_grid">
        <div class="grid-child child-lg">
          <h1 class="footer_logo">StayHaven</h1>
          <p class="footer_description">Explore unique accommodations around the world, tailored to your style and
            budget. Book with ease, stay with joy.</p>
        </div>
        <div class="grid-child">
          <h1 class="footer_nav_list_header">Company</h1>
          <ul>
            <li>About</li>
            <li>Privacy Policy</li>
            <li>Terms and Conditions</li>
          </ul>
        </div>
        <div class="grid-child">
          <h1 class="footer_nav_list_header">Links</h1>
          <ul>
            <li>Listings</li>
            <li>Orders</li>
          </ul>
        </div>
        <div class="grid-child">
          <h1 class="footer_logo">Contact</h1>
          <p class="footer_description">stayhaven@company.me</p>
        </div>
      </div>
    </div>
    <div class="copyright__wrapper">
      <p class="copyright">&copy; 2025 StayHaven. All rights reserved.</p>
    </div>
  </footer>
</body>

</html>