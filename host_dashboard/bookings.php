<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Check if user is logged in and is a host
if (!isset($_SESSION['user_id'])) {
    header("Location: /stayhaven/login.php");
    exit();
}

// Get user details and verify host role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['role'] !== 'host') {
    header("Location: /stayhaven/index.php");
    exit();
}

// Fetch ongoing bookings for the host's listings
$query = "
    SELECT 
        b.id as booking_id,
        b.check_in,
        b.check_out,
        b.total_price,
        b.payment_method,
        b.payment_reference,
        b.number_of_guests,
        b.room_quantity,
        b.created_at,
        l.title as room_title,
        l.room_type,
        l.quantity as number_of_rooms,
        u.name as guest_name,
        u.email as guest_email,
        u.phone as guest_phone
    FROM bookings b
    JOIN listings l ON b.listing_id = l.id
    JOIN users u ON b.guest_id = u.id
    WHERE l.host_id = ? 
    AND b.check_out >= CURRENT_DATE()
    ORDER BY b.check_in ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Host Dashboard - Bookings</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <style>
  body {
    font-family: 'Inter', sans-serif;
  }

  .stat-card {
    transition: transform 0.2s ease-in-out;
  }

  .stat-card:hover {
    transform: translateY(-2px);
  }

  .sidebar-link {
    transition: all 0.2s ease-in-out;
  }

  .sidebar-link:hover {
    background-color: rgba(239, 68, 68, 0.1);
  }

  .sidebar-link.active {
    background-color: rgba(239, 68, 68, 0.1);
  }
  </style>
</head>

<body class="bg-gray-50">
  <div class="flex h-screen">
    <aside class="w-64 bg-white border-r border-gray-200 px-4 py-6">
      <div class="flex items-center mb-8">
        <a href="/stayhaven/index.php">

          <h1 class="text-2xl font-bold text-red-600">StayHaven</h1>
        </a>
      </div>
      <nav>
        <ul class="space-y-2">
          <li>
            <a href="index.php" class="sidebar-link flex  items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;" width="18" height="20"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
              </svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="check-available.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-calendar-check">
                <path d="M8 2v4" />
                <path d="M16 2v4" />
                <rect width="18" height="18" x="3" y="4" rx="2" />
                <path d="M3 10h18" />
                <path d="m9 16 2 2 4-4" />
              </svg>
              Check Availability
            </a>
          </li>
          <li>
            <a href="add_listing.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add New Listing
            </a>
          </li>
          <li>
            <a href="bookings.php" class="sidebar-link active flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Bookings
            </a>
          </li>
          <li>
            <a href="/stayhaven/logout.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <main class="flex-1 overflow-y-auto">
      <div class="bg-white border-b border-gray-200 px-8 py-4">
        <div class="flex justify-end items-center">
          <div class="flex items-center">
            <span class="text-gray-700 mr-4"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <img
              src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=ef4444&color=fff"
              alt="Profile" class="w-8 h-8 rounded-full">
          </div>
        </div>
      </div>

      <main class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-4xl font-semibold mb-4">Bookings:</h1>
        <?php if (empty($bookings)): ?>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-500">No ongoing bookings found.</p>
        </div>
        <?php else: ?>
        <div class="grid gap-6">
          <?php foreach ($bookings as $booking): ?>
          <div class="bg-white rounded-lg shadow p-6">
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($booking['room_title']); ?>
                </h2>
                <div class="mt-2 space-y-1">
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Booked on:</span>
                    <?php echo date('F j, Y', strtotime($booking['created_at'])); ?>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Check-in:</span>
                    <?php echo date('F j, Y', strtotime($booking['check_in'])); ?>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Check-out:</span>
                    <?php echo date('F j, Y', strtotime($booking['check_out'])); ?>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Number of Guests:</span>
                    <?php echo htmlspecialchars($booking['number_of_guests']); ?>
                  </p>
                  <?php if ($booking['room_type'] !== 'entire_place'): ?>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Number of Rooms:</span>
                    <?php echo htmlspecialchars($booking['number_of_rooms']); ?>
                  </p>
                  <?php endif; ?>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Total Price:</span>
                    NPR.<?php echo number_format($booking['total_price'], 2); ?>
                  </p>
                </div>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">Guest Information</h3>
                <div class="mt-2 space-y-1">
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Name:</span>
                    <?php echo htmlspecialchars($booking['guest_name']); ?>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Email:</span>
                    <?php echo htmlspecialchars($booking['guest_email']); ?>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Phone:</span>
                    <?php echo htmlspecialchars($booking['guest_phone']); ?>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Payment Method:</span>
                    <?php echo ucfirst($booking['payment_method']); ?>
                    <?php if ($booking['payment_reference']): ?>
                    (Ref: <?php echo htmlspecialchars($booking['payment_reference']); ?>)
                    <?php endif; ?>
                  </p>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </main>
  </div>
</body>

</html>