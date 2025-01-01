<?php
// booking-form.php
session_start();
require_once __DIR__ . '/db/config.php';
;

// Function to get user details
function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get room details
$stmt = $conn->prepare("SELECT * FROM listings WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: index.php");
    exit();
}

// Get existing bookings for this room
$stmt = $conn->prepare("
    SELECT check_in, check_out 
    FROM bookings 
    WHERE listing_id = ? 
    AND status = 'confirmed'
    AND check_out >= CURRENT_DATE()
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

$booked_dates = [];
while ($booking = $result->fetch_assoc()) {
    // Get all dates between check_in and check_out
    $period = new DatePeriod(
        new DateTime($booking['check_in']),
        new DateInterval('P1D'),
        (new DateTime($booking['check_out']))->modify('+1 day')
    );
    
    foreach ($period as $date) {
        $booked_dates[] = $date->format('Y-m-d');
    }
}
$stmt->close();

// Convert booked dates to JSON for JavaScript
$booked_dates_json = json_encode($booked_dates);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book <?php echo htmlspecialchars($room['title']); ?></title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.tailwindcss.com"></script>
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

          <div x-show="open" x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1">
            <div class="px-4 py-2 border-b border-slate-200">
              <p class="text-sm text-slate-500">Signed in as</p>
              <p class="text-sm font-medium truncate"><?php echo $userDetails['email']; ?></p>
            </div>

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

            <a href="/stayhaven/settings.php"
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
          <a href="/stayhaven/sign-up.php"
            class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg transition-colors">
            Register
          </a>
        </div>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Book <span
        class="text-rose-600"><?php echo htmlspecialchars($room['title']); ?></span></h1>

    <form action="process-booking.php" method="POST" class="space-y-4">
      <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">

      <div>
        <label class="block mb-2">Check-in Date:</label>
        <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>"
          class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="block mb-2">Check-out Date:</label>
        <input type="date" name="check_out" required min="<?php echo date('Y-m-d'); ?>"
          class="w-full p-2 border rounded">
      </div>

      <div class="bg-white p-4 rounded shadow">
        <h2 class="font-semibold mb-2">Price Details</h2>
        <p>Price per night: $<?php echo number_format($room['price'], 2); ?></p>
        <p class="text-base font-semibold text-gray-700 mt-2">* Total price will be calculated based on your dates</p>
        <p class="text-base font-semibold text-gray-700">* Payment method: Cash on arrival</p>
      </div>

      <button type="submit" class="w-full bg-rose-600 text-white py-2 px-4 rounded hover:bg-rose-700">
        Book Now
      </button>
    </form>
  </div>

  <script>
  // Get booked dates from PHP
  const bookedDates = <?php echo $booked_dates_json; ?>;
  const checkInInput = document.querySelector('input[name="check_in"]');
  const checkOutInput = document.querySelector('input[name="check_out"]');

  // Function to check if a date is booked
  function isDateBooked(date) {
    const dateString = date.toISOString().split('T')[0];
    return bookedDates.includes(dateString);
  }

  // Function to set date input constraints
  function setDateConstraints(input) {
    const currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);

    // Create array of disabled dates in YYYY-MM-DD format
    const disabledDates = bookedDates.map(date => date);

    // Disable booked dates
    input.addEventListener('input', function() {
      const selectedDate = this.value;
      if (disabledDates.includes(selectedDate)) {
        alert('This date is already booked. Please select another date.');
        this.value = '';
      }
    });
  }

  // Apply constraints to both inputs
  setDateConstraints(checkInInput);
  setDateConstraints(checkOutInput);

  // Update check-out min date when check-in is selected
  checkInInput.addEventListener('change', function() {
    if (this.value) {
      checkOutInput.min = this.value;
      // Clear check-out date if it's before check-in date
      if (checkOutInput.value && checkOutInput.value < this.value) {
        checkOutInput.value = '';
      }
    }
  });

  // Validate that selected date range doesn't include any booked dates
  document.querySelector('form').addEventListener('submit', function(e) {
    if (checkInInput.value && checkOutInput.value) {
      const start = new Date(checkInInput.value);
      const end = new Date(checkOutInput.value);

      for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
        if (isDateBooked(date)) {
          e.preventDefault();
          alert('Your selected date range includes booked dates. Please choose different dates.');
          return;
        }
      }
    }
  });
  </script>
</body>

</html>