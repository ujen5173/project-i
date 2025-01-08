<?php
session_start();
require_once __DIR__ . '/db/config.php';



// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userDetails = null;

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}


// Get host ID from URL
$host_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch host details
$stmt = $conn->prepare("SELECT name, email, created_at, role FROM users WHERE id = ? AND role = 'host'");
$stmt->bind_param("i", $host_id);
$stmt->execute();
$host = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$host) {
    header("Location: index.php");
    exit();
}

// Fetch all listings by this host
$stmt = $conn->prepare("SELECT l.*, 
    (SELECT COUNT(*) FROM bookings WHERE listing_id = l.id) as total_bookings,
    GROUP_CONCAT(a.name) as amenities
    FROM listings l
    LEFT JOIN listing_amenities la ON l.id = la.listing_id
    LEFT JOIN amenities a ON la.amenity_id = a.id
    WHERE l.host_id = ?
    GROUP BY l.id
    ORDER BY l.created_at DESC");
$stmt->bind_param("i", $host_id);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($host['name']); ?>'s Listings - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/index.css">
  <script src="//unpkg.com/alpinejs" defer></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="css/styles.css">
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

  <main class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
      <!-- Host Profile Header -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
        <div class="flex items-start gap-6">
          <div class="w-20 h-20 bg-rose-600 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-white font-medium text-2xl">
              <?php echo substr($host['name'], 0, 1); ?>
            </span>
          </div>
          <div>
            <h1 class="text-2xl font-semibold mb-2"><?php echo htmlspecialchars($host['name']); ?></h1>
            <p class="text-slate-600 mb-2">
              <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1"></i>
              Host since <?php echo date('F Y', strtotime($host['created_at'])); ?>
            </p>
            <p class="text-slate-600">
              <i data-lucide="home" class="w-4 h-4 inline-block mr-1"></i>
              <?php echo count($listings); ?> properties listed
            </p>
          </div>
        </div>
      </div>

      <!-- Listings Grid -->
      <h2 class="text-xl font-semibold mb-6">Properties by <?php echo htmlspecialchars($host['name']); ?></h2>

      <?php if (empty($listings)): ?>
      <div class="text-center py-12 bg-slate-50 rounded-lg">
        <i data-lucide="home" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
        <h3 class="text-lg font-medium text-slate-900 mb-2">No listings yet</h3>
        <p class="text-slate-500">This host hasn't posted any properties yet.</p>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($listings as $listing): ?>
        <a href="details.php?id=<?php echo $listing['id']; ?>"
          class="block bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
          <div class="aspect-w-16 aspect-h-9">
            <img src="/stayHaven<?php echo $listing['image_url'] ?? "/images/placeholder-image.jpg" ?>"
              alt="<?php echo htmlspecialchars($listing['title']); ?>" class="w-full h-48 object-cover">
          </div>
          <div class="p-4">
            <h3 class="font-semibold mb-2"><?php echo htmlspecialchars($listing['title']); ?></h3>
            <p class="text-slate-600 mb-2">
              <i data-lucide="map-pin" class="w-4 h-4 inline-block mr-1"></i>
              <?php echo htmlspecialchars($listing['location']); ?>
            </p>
            <div class="flex items-center justify-between">
              <p class="font-medium text-rose-600">
                NPR <?php echo number_format($listing['price']); ?> / night
              </p>
              <p class="text-sm text-slate-500">
                <?php echo $listing['total_bookings']; ?> bookings made
              </p>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </main>


  <footer class="footer">
    <div class="footer__wrapper container">
      <div class="footer_grid">
        <div class="grid-child child-lg">
          <h1 class="footer_logo">
            StayHaven
          </h1>
          <p class="footer_description">
            Explore unique accommodations around the world, tailorose to your style and budget. Book with ease, stay
            with
            joy.
          </p>
        </div>
        <div class="grid-child">
          <h1 class="footer_nav_list_header">
            Company
          </h1>
          <ul>
            <li>About</li>
            <li>Privacy Policy</li>
            <li>Terms and Conditions</li>
          </ul>
        </div>
        <div class="grid-child">
          <h1 class="footer_nav_list_header">
            Links
          </h1>
          <ul>
            <li>Listings</li>
            <li>Orders</li>
          </ul>
          </ul>
        </div>
        <div class="grid-child">
          <h1 class="footer_logo">
            Contact
          </h1>
          <p class="footer_description">
            stayhaven@company.me
          </p>
        </div>
      </div>
    </div>
    <div class="copyright__wrapper">
      <p class="copyright">
        &copy; 2024 StayHaven. All rights reserved.
      </p>
    </div>
  </footer>

  <script>
  lucide.createIcons();
  </script>
</body>

</html>