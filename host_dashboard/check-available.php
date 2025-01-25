<?php

session_start();
require_once '../db/config.php';

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

$host_id = $_SESSION['user_id'];

// Fetch all listings for the host
$listings_query = "SELECT id, title, room_type, quantity, price, status 
                  FROM listings 
                  WHERE host_id = ?";
$stmt = $conn->prepare($listings_query);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$listings_result = $stmt->get_result();
$listings = $listings_result->fetch_all(MYSQLI_ASSOC);

// Handle availability check
$availability_data = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_availability'])) {
    $listing_id = $_POST['listing_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    
    // Updated booking query with corrected date range logic
    $bookings_query = "SELECT COALESCE(SUM(b.room_quantity), 0) as booked_rooms, l.quantity
                      FROM listings l
                      LEFT JOIN bookings b ON b.listing_id = l.id
                      AND (
                          (b.check_in < ? AND b.check_out > ?) OR    -- Booking spans the check-in date
                          (b.check_in >= ? AND b.check_in < ?) OR    -- Booking starts during the stay
                          (b.check_in <= ? AND b.check_out > ?)      -- Booking overlaps with the stay
                      )
                      WHERE l.id = ?
                      GROUP BY l.id, l.quantity";
    
    $stmt = $conn->prepare($bookings_query);
    $stmt->bind_param("ssssss" . "i", 
        $check_in, $check_in,      // For first condition
        $check_in, $check_out,     // For second condition
        $check_out, $check_out,    // For third condition
        $listing_id
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $booking_data = $result->fetch_assoc();
    
    // Get listing details
    $listing_query = "SELECT title, quantity FROM listings WHERE id = ?";
    $stmt = $conn->prepare($listing_query);
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $listing_result = $stmt->get_result();
    $listing_data = $listing_result->fetch_assoc();
    
    $booked_rooms = $booking_data['booked_rooms'] ?? 0;
    $total_rooms = $listing_data['quantity'];
    $available_rooms = $total_rooms - $booked_rooms;
    
    $availability_data = [
        'listing_title' => $listing_data['title'],
        'total_rooms' => $total_rooms,
        'booked_rooms' => $booked_rooms,
        'available_rooms' => $available_rooms,
        'check_in' => $check_in,
        'check_out' => $check_out
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Lato Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Kalam:wght@300;400;700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Permanent+Marker&display=swap"
    rel="stylesheet">

  <!-- Merriweather -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Kalam:wght@300;400;700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Permanent+Marker&display=swap"
    rel="stylesheet">

  <!-- Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kalam:wght@300;400;700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Permanent+Marker&display=swap"
    rel="stylesheet">


  <title>Check Availability - Host Dashboard</title>

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <!-- CSS -->
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/bookings.css">
  <link rel="stylesheet" href="css/index.css">
  <script src="//unpkg.com/alpinejs" defer></script>

</head>

<body class="bg-gray-50">

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

        <?php if ($isLoggedIn): ?>
        <div class="relative" x-data="{ open: false }">
          <div class="flex items-center gap-2">
            <?php if ($userDetails['role'] === 'host'): ?>
            <a href="/stayhaven/host_dashboard/index.php"
              class="bg-rose-600 visited:text-white hover:bg-rose-700 text-white px-4 py-2 rounded-lg transition-colors">
              Dashboard
            </a>
            <?php endif; ?>

            <button @click="open = !open" @click.outside="open = false"
              class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-lg transition-colors">
              <div class="w-8 h-8 bg-rose-600 rounded-full flex items-center justify-center">
                <span class="text-white font-medium">
                  <?php echo substr($userDetails['name'], 0, 1); ?>
                </span>
              </div>
              <span class="text-slate-700"><?php echo $userDetails['name']; ?></span>
              <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
            </button>
          </div>

          <div x-show="open" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
            <div class="px-4 py-2 border-b border-slate-200">
              <p class="text-sm text-slate-500">Signed in as</p>
              <p class="text-sm font-medium truncate"><?php echo $userDetails['email']; ?></p>
            </div>

            <?php if ($userDetails['role'] !== 'host'): ?>
            <a href="/stayhaven/bookings.php"
              class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              <i data-lucide="calendar" class="w-4 h-4"></i>
              My Bookings
            </a>

            <a href="/stayhaven/favorites.php"
              class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              <i data-lucide="heart" class="w-4 h-4"></i>
              Favorites
            </a>
            <?php endif; ?>

            <?php if ($userDetails['role'] === 'host'): ?>
            <a href="/stayhaven/user_profile.php"
              class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
              Dashboard
            </a>
            <a href="/stayhaven/host-profile.php?id=<?php echo $userDetails['id']; ?>"
              class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              <i data-lucide="user" class="w-4 h-4"></i>
              Host Profile
            </a>
            <?php endif; ?>

            <a href="/stayhaven/user_profile.php"
              class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              <i data-lucide="settings" class="w-4 h-4"></i>
              Settings
            </a>

            <div class="border-t border-slate-200 mt-1">
              <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                Logout
              </a>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="flex items-center gap-4">
          <a href="/stayhaven/login.php" class="text-slate-600 hover:text-slate-900">
            Login
          </a>
          <a href="/stayhaven/signup.php"
            class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg transition-colors">
            Register
          </a>
        </div>
        <?php endif; ?>
      </div>
    </nav>
  </header>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Check Property Availability</h1>

    <!-- Add a loading spinner -->
    <div id="loading" class="hidden fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">
      <div class="loader"></div>
    </div>

    <!-- Availability Check Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
      <form method="POST" class="space-y-4" onsubmit="showLoading()">
        <div>
          <label class="block text-sm font-medium text-gray-700">Select Property</label>
          <select name="listing_id" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2">
            <option value="">Select a property</option>
            <?php foreach ($listings as $listing): ?>
            <option value="<?= htmlspecialchars($listing['id']) ?>">
              <?= htmlspecialchars($listing['title']) ?>
              (<?= htmlspecialchars($listing['room_type']) ?> -
              <?= $listing['quantity'] ?> units)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Check-in Date</label>
            <input type="date" name="check_in" required min="<?= date('Y-m-d') ?>"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2"
              placeholder="YYYY-MM-DD">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Check-out Date</label>
            <input type="date" name="check_out" required min="<?= date('Y-m-d') ?>"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 p-2"
              placeholder="YYYY-MM-DD">
          </div>
        </div>

        <button type="submit" name="check_availability"
          class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200 ease-in-out">
          Check Availability
        </button>
      </form>
    </div>

    <!-- Availability Results -->
    <?php if ($availability_data): ?>
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
      <h2 class="text-xl font-semibold mb-4">Availability Results for
        <span class="text-red-600 underline">
          <?= htmlspecialchars($availability_data['listing_title']) ?>
        </span>
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
          <p class="text-sm text-gray-500">Total Units</p>
          <p class="text-2xl font-bold"><?= $availability_data['total_rooms'] ?></p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
          <p class="text-sm text-gray-500">Booked Units</p>
          <p class="text-2xl font-bold text-red-600"><?= $availability_data['booked_rooms'] ?></p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-300">
          <p class="text-sm text-gray-500">Available Units</p>
          <p class="text-2xl font-bold text-green-600"><?= $availability_data['available_rooms'] ?></p>
        </div>
      </div>
      <div class="mt-4 text-sm text-gray-600">
        <p>For dates: <?= date('F j, Y', strtotime($availability_data['check_in'])) ?>
          to <?= date('F j, Y', strtotime($availability_data['check_out'])) ?></p>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
  // Add client-side validation for dates
  document.addEventListener('DOMContentLoaded', function() {
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');

    checkInInput.addEventListener('change', function() {
      checkOutInput.min = this.value;
    });

    checkOutInput.addEventListener('change', function() {
      if (this.value < checkInInput.value) {
        this.value = checkInInput.value;
      }
    });
  });
  </script>

  <script>
  lucide.createIcons();
  </script>

  <!-- Add JavaScript function to show loading spinner -->
  <script>
  function showLoading() {
    document.getElementById('loading').classList.remove('hidden');
  }
  </script>

  <!-- Add CSS for loading spinner -->
  <style>
  .loader {
    border: 8px solid #f3f3f3;
    /* Light grey */
    border-top: 8px solid #3498db;
    /* Blue */
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }
  </style>

  <!-- Add CSS for input focus effects -->
  <style>
  input:focus,
  select:focus {
    border-color: #f43f5e;
    /* Tailwind red-500 */
    box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.5);
    /* Tailwind red-500 with opacity */
  }
  </style>
</body>

</html>