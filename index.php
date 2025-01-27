<?php
session_start();
require_once __DIR__ . '/db/config.php';

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

$stmt = $conn->prepare("
    SELECT 
        l.*,
        u.name AS host_name 
    FROM listings l
    JOIN users u ON l.host_id = u.id
    WHERE l.status = 'active'
    ORDER BY l.created_at DESC 
    LIMIT 4
");

$stmt->execute();
$featuredListings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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


  <title>Home - Stayhaven</title>

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <!-- CSS -->
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/bookings.css">
  <link rel="stylesheet" href="css/index.css">
  <script src="//unpkg.com/alpinejs" defer></script>

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
            <a href="/stayhaven/host_dashboard/index.php"
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

  <main class="hero-section">
    <div class="hero-section__wrapper container">
      <div class="overlay"></div>
      <div class="hero-section-content">
        <h1 class="hero-section__title">
          Discover Your Perfect Stay, <br /> Anywhere, Anytime
        </h1>
        <p class="hero-section__description">
          Explore unique accommodations around the world, tailored to your style and budget. <br /> Book with ease, stay
          with
          joy.
        </p>
        <form class="booking-form">
          <div>
            <label for="check-in">Check in</label>
            <input type="date" id="check-in" placeholder="11-17-2024">
          </div>
          <div>
            <label for="check-out">Check out</label>
            <input type="date" id="check-out" placeholder="11-20-2024">
          </div>
          <div>
            <label for="guests">Guests</label>
            <input type="number" id="guests" min="1" max="10" style="width: 100%;" placeholder="4 person">
          </div>
          <button class="btn">Search</button>
        </form>
      </div>
    </div>
  </main>

  <section class="featured-listings">
    <div class="featured-listings__wrapper container">
      <h1 class="featured-listings__title">
        Explore Featured Listings
      </h1>

      <div class="featured-listings__list grid grid-cols-4 gap-4">
        <?php if (empty($featuredListings)): ?>
        <div class="col-span-4 text-center py-8">
          <p class="text-slate-600 text-lg">No listings available at the moment.</p>
          <p class="text-slate-500 mt-2">Check back later for new properties!</p>
        </div>
        <?php else: ?>
        <?php foreach ($featuredListings as $listing): ?>
        <a href="/stayHaven/details.php?id=<?php echo $listing['id'] ?>">
          <div class="booking-card">
            <div class="booking-image">
              <img src="/stayHaven<?php echo $listing['image_url'] ?? "/images/placeholder-image.jpg" ?>"
                alt="<?php echo htmlspecialchars($listing['title']); ?>">
            </div>

            <div class="booking-content">
              <h3 class="h-14"><?php echo htmlspecialchars($listing['title']); ?></h3>

              <div class="listing-details space-y-2">
                <div class="detail-item space-x-2">
                  <i data-lucide="map-pin"></i>
                  <span><?php echo htmlspecialchars($listing['location']); ?></span>
                </div>

                <div class="detail-item space-x-2">
                  <i data-lucide="user"></i>
                  <span>Host: <?php echo htmlspecialchars($listing['host_name']); ?></span>
                </div>


                <div class="detail-item space-x-2">
                  <i data-lucide="credit-card"></i>
                  <span>Total: NPR.<?php echo number_format($listing['price'], 2); ?></span>
                </div>
              </div>
            </div>
          </div>

        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="footer__wrapper container">
      <div class="footer_grid">
        <div class="grid-child child-lg">
          <h1 class="footer_logo">
            StayHaven
          </h1>
          <p class="footer_description">
            Explore unique accommodations around the world, tailored to your style and budget. Book with ease, stay with
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
        &copy; 2025 StayHaven. All rights reserved.
      </p>
    </div>
  </footer>
</body>

<script>
// Add to your homepage JavaScript
document.querySelector('.booking-form').addEventListener('submit', function(e) {
  e.preventDefault();

  const checkIn = document.getElementById('check-in').value;
  const checkOut = document.getElementById('check-out').value;
  const guests = document.getElementById('guests').value;

  const searchParams = new URLSearchParams({
    check_in: checkIn,
    check_out: checkOut,
    guests: guests
  });

  window.location.href = `/stayHaven/search.php?${searchParams.toString()}`;
});

// Date validation
document.getElementById('check-in').addEventListener('change', function() {
  const checkOut = document.getElementById('check-out');
  checkOut.min = this.value;
});

document.getElementById('check-out').addEventListener('change', function() {
  const checkIn = document.getElementById('check-in');
  checkIn.max = this.value;
});

// Set min date to today
const today = new Date().toISOString().split('T')[0];
document.getElementById('check-in').min = today;
document.getElementById('check-out').min = today;
</script>

<script>
// Toggle dropdown menu
const userMenu = document.getElementById('userMenu');
const dropdownMenu = document.getElementById('dropdownMenu');

if (userMenu) {
  userMenu.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdownMenu.classList.toggle('active');
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!userMenu.contains(e.target)) {
      dropdownMenu.classList.remove('active');
    }
  });
}
</script>

<script>
lucide.createIcons();
</script>

</html>