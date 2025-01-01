<?php

// header.php
session_start();
require_once __DIR__ . '/db/config.php';

// Function to get user details
function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
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

function getAllListings($conn) {
    // Query to get listings with related information
    $query = "
        SELECT 
            l.*,
            u.name as host_name,
            u.email as host_email,
            GROUP_CONCAT(DISTINCT a.name) as amenities,
            AVG(r.rating) as average_rating,
            COUNT(DISTINCT r.id) as review_count
        FROM listings l
        LEFT JOIN users u ON l.host_id = u.id
        LEFT JOIN listing_amenities la ON l.id = la.listing_id
        LEFT JOIN amenities a ON la.amenity_id = a.id
        LEFT JOIN reviews r ON l.id = r.listing_id
        GROUP BY l.id
        ORDER BY l.created_at DESC
    ";

    $result = $conn->query($query);

    if (!$result) {
        return [
            'success' => false,
            'error' => 'Failed to fetch listings: ' . $conn->error
        ];
    }

    $listings = [];
    while ($row = $result->fetch_assoc()) {
        // Convert amenities string to array
        $row['amenities'] = $row['amenities'] ? explode(',', $row['amenities']) : [];
        
        // Format the rating
        $row['average_rating'] = $row['average_rating'] ? round($row['average_rating'], 1) : null;
        
        // Format prices
        $row['price'] = number_format($row['price'], 2);
        
        $listings[] = $row;
    }

    return [
        'success' => true,
        'data' => $listings
    ];
}
 
$result = getAllListings($conn);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width='device-width', initial-scale=1.0">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/search.css">
  <link rel="stylesheet" href="css/room-detail.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="//unpkg.com/alpinejs" defer></script>

  <title>All Listings | StayHaven</title>
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


  <main style="padding: 40px 0">
    <section class="container">

      <h1 class=" text-3xl font-semibold mb-4">
        All Listings
      </h1>

      <section class="listings-grid">
        <?php foreach ($result['data'] as $listing): ?>
        <article class="listing-card">
          <a href="/stayHaven/details.php?id=<?php echo $listing['id']; ?>">
            <div class="listing-image">
              <img src="/stayHaven<?php echo $listing['image_url'] ?? "/images/placeholder-image.jpg" ?>"
                alt="<?php echo htmlspecialchars($listing['title']); ?>">
            </div>
            <div class="listing-content">
              <h2 class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></h2>
              <p class="listing-location"><?php echo htmlspecialchars($listing['location']); ?></p>
              <div class="listing-details">
                <span class="room-type"><?php echo htmlspecialchars($listing['room_type']); ?></span>
                <span class="guests">Up to <?php echo htmlspecialchars($listing['max_guests']); ?> guests</span>
              </div>
              <div class="listing-footer">
                <span class="price">$<?php echo htmlspecialchars($listing['price']); ?> / night</span>
                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="view-btn">View Details</a>
              </div>
            </div>
          </a>
        </article>
        <?php endforeach; ?>
      </section>
    </section>

  </main>
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
        &copy; 2024 StayHaven. All rights reserved.
      </p>
    </div>
  </footer>
</body>

</html>