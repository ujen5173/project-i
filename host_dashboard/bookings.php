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
$search_query = "";
$search_params = [];
$where_conditions = ["l.host_id = ?"];
$search_params[] = $_SESSION['user_id'];
$param_types = "i";

if (isset($_GET['search'])) {
    if (!empty($_GET['guest_name'])) {
        $where_conditions[] = "u.name LIKE ?";
        $search_params[] = "%" . $_GET['guest_name'] . "%";
        $param_types .= "s";
    }
    if (!empty($_GET['date_from'])) {
        $where_conditions[] = "b.check_in >= ?";
        $search_params[] = $_GET['date_from'];
        $param_types .= "s";
    }
    if (!empty($_GET['date_to'])) {
        $where_conditions[] = "b.check_out <= ?";
        $search_params[] = $_GET['date_to'];
        $param_types .= "s";
    }
    if (!empty($_GET['room_type'])) {
        $where_conditions[] = "l.room_type = ?";
        $search_params[] = $_GET['room_type'];
        $param_types .= "s";
    }
    if (!empty($_GET['payment_method'])) {
        $where_conditions[] = "b.payment_method = ?";
        $search_params[] = $_GET['payment_method'];
        $param_types .= "s";
    }
}

$where_clause = implode(" AND ", $where_conditions);
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
        u.name as guest_name,
        u.email as guest_email,
        u.phone as guest_phone
    FROM bookings b
    JOIN listings l ON b.listing_id = l.id
    JOIN users u ON b.guest_id = u.id
    WHERE $where_clause
    AND b.check_out >= CURRENT_DATE()
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($search_params)) {
    $stmt->bind_param($param_types, ...$search_params);
}
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique room types for the filter dropdown
$room_types_query = "SELECT DISTINCT room_type FROM listings WHERE host_id = ?";
$stmt = $conn->prepare($room_types_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$room_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
  <link href="https://unpkg.com/@tailwindcss/forms@0.2.1/dist/forms.min.css" rel="stylesheet">
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
        <h1 class="text-4xl font-semibold mb-6">Bookings</h1>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Guest Name</label>
              <input type="text" name="guest_name"
                value="<?php echo isset($_GET['guest_name']) ? htmlspecialchars($_GET['guest_name']) : ''; ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Check-in Date From</label>
              <input type="date" name="date_from"
                value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Check-out Date To</label>
              <input type="date" name="date_to"
                value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Room Type</label>
              <select name="room_type"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                <option value="">All Types</option>
                <?php foreach ($room_types as $type): ?>
                <option value="<?php echo htmlspecialchars($type['room_type']); ?>"
                  <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == $type['room_type']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($type['room_type']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">Payment Method</label>
              <select name="payment_method"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                <option value="">All Methods</option>
                <option value="cash"
                  <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'cash') ? 'selected' : ''; ?>>
                  Cash</option>
                <option value="esewa"
                  <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'esewa') ? 'selected' : ''; ?>>
                  Esewa</option>
              </select>
            </div>

            <div class="flex items-end space-x-2">
              <button type="submit" name="search"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                Search
              </button>
              <a href="bookings.php"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Reset
              </a>
            </div>
          </form>
        </div>

        <!-- Results count -->
        <div class="mb-4 text-sm text-gray-600">
          Found <?php echo count($bookings); ?> booking<?php echo count($bookings) !== 1 ? 's' : ''; ?>
        </div>

        <?php if (empty($bookings)): ?>
        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-500">No ongoing bookings found.</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Room Details
                  </th>
                  <th scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Guest Information
                  </th>
                  <th scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Booking Details
                  </th>
                  <th scope="col"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Payment
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($bookings as $booking): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex flex-col">
                      <span class="text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($booking['room_title']); ?>
                      </span>
                      <span class="text-sm text-gray-500">
                        Type: <?php echo htmlspecialchars($booking['room_type']); ?>
                      </span>
                      <span class="text-sm text-gray-500">
                        Rooms: <?php echo htmlspecialchars($booking['room_quantity']); ?>
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-col">
                      <span class="text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($booking['guest_name']); ?>
                      </span>
                      <span class="text-sm text-gray-500">
                        <?php echo htmlspecialchars($booking['guest_email']); ?>
                      </span>
                      <span class="text-sm text-gray-500">
                        <?php echo htmlspecialchars($booking['guest_phone']); ?>
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-col">
                      <div class="flex items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none"
                          viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-sm text-gray-500">
                          <?php echo date('M d, Y', strtotime($booking['check_in'])); ?> -
                          <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                        </span>
                      </div>
                      <span class="text-sm text-gray-500">
                        Guests: <?php echo htmlspecialchars($booking['number_of_guests']); ?>
                      </span>
                      <span class="text-xs text-gray-400">
                        Booked: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-col">
                      <span class="text-sm font-medium text-gray-900">
                        NPR <?php echo number_format($booking['total_price'], 2); ?>
                      </span>
                      <span class="text-sm text-gray-500">
                        <?php echo ucfirst($booking['payment_method']); ?>
                      </span>
                      <?php if ($booking['payment_reference']): ?>
                      <span class="text-xs text-gray-400">
                        Ref: <?php echo htmlspecialchars($booking['payment_reference']); ?>
                      </span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>
      </main>

  </div>
</body>

</html>