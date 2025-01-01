<?php
session_start();
require_once __DIR__ . '/db/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userDetails = null;

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT 
        b.*,
        l.title,
        l.image_url,
        l.location,
        l.price,
        u.name as host_name
    FROM bookings b
    JOIN listings l ON b.listing_id = l.id
    JOIN users u ON l.host_id = u.id
    WHERE b.guest_id = ?
    ORDER BY b.check_in DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="css/bookings.css">
  <link rel="stylesheet" href="css/index.css">
</head>

<body class="bg-slate-50">
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
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
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

  <div class="h-10"></div>

  <main class="container mx-auto px-4 py-8">
    <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <i data-lucide="calendar-x"></i>
      <h3>No bookings yet</h3>
      <p>Start exploring amazing places to stay!</p>
      <a href="listings.php" class="btn-primary">Browse Listings</a>
    </div>
    <?php else: ?>
    <div class="bookings-grid">
      <?php foreach ($bookings as $booking): ?>
      <a href="/stayHaven/details.php?id=<?php echo $booking['listing_id']; ?>">

        <div class="booking-card">
          <div class="booking-image">
            <img src="/stayHaven<?php echo htmlspecialchars($booking['image_url']); ?>"
              alt="<?php echo htmlspecialchars($booking['title']); ?>">
            <span class="status-badge <?php echo $booking['status']; ?>">
              <?php echo ucfirst($booking['status']); ?>
            </span>
          </div>

          <div class="booking-content">
            <h3><?php echo htmlspecialchars($booking['title']); ?></h3>

            <div class="booking-details">
              <div class="detail-item">
                <i data-lucide="map-pin"></i>
                <span><?php echo htmlspecialchars($booking['location']); ?></span>
              </div>

              <div class="detail-item">
                <i data-lucide="user"></i>
                <span>Host: <?php echo htmlspecialchars($booking['host_name']); ?></span>
              </div>

              <div class="detail-item">
                <i data-lucide="calendar"></i>
                <span>
                  <?php 
                    $check_in = new DateTime($booking['check_in']);
                    $check_out = new DateTime($booking['check_out']);
                    echo $check_in->format('M d, Y') . ' - ' . $check_out->format('M d, Y');
                ?>
                </span>
              </div>

              <div class="detail-item">
                <i data-lucide="credit-card"></i>
                <span>Total: $<?php echo number_format($booking['total_price'], 2); ?></span>
              </div>
            </div>

            <?php if ($booking['status'] === 'confirmed'): ?>
            <div class="payment-info">
              <i data-lucide="info"></i>
              <p>Please prepare cash payment upon arrival</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>

  <script>
  lucide.createIcons();
  </script>
  <script src="//unpkg.com/alpinejs" defer></script>

</body>

</html>