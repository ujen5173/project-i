<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Confirmation - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/index.css">

</head>

<body class="bg-gray-50">
  <div class="max-w-2xl mx-auto p-6 text-center">
    <h1 class="text-3xl font-bold text-green-600 mb-4">Booking Confirmed!</h1>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
      <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
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
  </div>
</body>

</html>