<?php
session_start();
require_once __DIR__ . '/db/config.php';

// Fetch room details
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT l.*, 
    GROUP_CONCAT(a.name) as amenities,
    u.name as host_name,
    u.email as host_email,
    u.created_at as host_joined 
    FROM listings l
    LEFT JOIN listing_amenities la ON l.id = la.listing_id
    LEFT JOIN amenities a ON la.amenity_id = a.id
    LEFT JOIN users u ON l.host_id = u.id
    WHERE l.id = ?
    GROUP BY l.id");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    // roseirect or show error if room not found
    header("Location: index.php");
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userDetails = null;

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT id, name, email,role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}


// Fetch featurose listings
$stmt = $conn->prepare("SELECT DISTINCT l.*,
    (
        SELECT COUNT(*) FROM listing_amenities la 
        WHERE la.listing_id = l.id AND la.amenity_id IN (
            SELECT amenity_id FROM listing_amenities 
            WHERE listing_id = ?)
    ) as matching_amenities
FROM listings l
WHERE l.id != ? -- Exclude current listing
AND l.room_type = (SELECT room_type FROM listings WHERE id = ?)
AND l.max_guests >= (SELECT max_guests FROM listings WHERE id = ?)
AND l.price BETWEEN (
    SELECT price * 0.8 FROM listings WHERE id = ?
) AND (
    SELECT price * 1.2 FROM listings WHERE id = ?
)
ORDER BY matching_amenities DESC, l.price
LIMIT 4;");
$stmt->bind_param("iiiiii", 
  $room_id, 
  $room_id,
  $room_id,
  $room_id,
  $room_id,
  $room_id
);
$stmt->execute();
$similarListings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($room['title']); ?> - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Links (from index.php) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Merriweather:wght@400;700&display=swap"
    rel="stylesheet">

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script src="//unpkg.com/alpinejs" defer></script>

  <!-- CSS -->
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/search.css">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/room-detail.css">
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

  <main class="room-detail-section">
    <div class="room-detail__wrapper container">
      <div class="room-detail-wrapper">
        <div class="room-images">
          <!-- You may need to implement a gallery here based on your image storage system -->
          <div class="room-images_thumbnail">
            <img src="/stayHaven<?php echo htmlspecialchars($room['image_url']); ?>"
              alt="<?php echo htmlspecialchars($room['title']); ?>">
          </div>
        </div>

        <div class="room-info">
          <h1 class="room-title"><?php echo htmlspecialchars($room['title']); ?></h1>

          <div class="room-meta">

            <!-- <span class="dot"></span> -->
            <div class="location">
              <i style="width: 18px; height: 18px;" data-lucide="map-pin"></i>
              <?php echo htmlspecialchars($room['location']); ?>
            </div>
          </div>

          <div class="room-details">
            <div class="room-type">
              <i style="width: 18px; height: 18px;" data-lucide="home"></i>
              <?php echo htmlspecialchars($room['room_type']); ?>
            </div>
            <span class="dot"></span>
            <div class="max-guests">
              <i style="width: 18px; height: 18px;" data-lucide="users"></i>
              Max <?php echo $room['max_guests']; ?> guests
            </div>
            <?php if ($room['room_type'] !== 'Entire place'): ?>
            <span class="dot"></span>
            <div class="quantity">
              <i style="width: 18px; height: 18px;" data-lucide="home-check"></i>
              <?php echo $room['quantity']; ?> unit(s) available
            </div>
            <?php endif; ?>
          </div>

          <div class="room-description">
            <h3>About this place</h3>
            <p><?php echo htmlspecialchars($room['description']); ?></p>
          </div>

          <div class="room-amenities">
            <h3>Amenities</h3>
            <div class="amenities-wrapper">
              <?php 
                $amenities = $room['amenities'] ? explode(',', $room['amenities']) : [];
                if (empty($amenities)): ?>
              <p>No amenities available</p>
              <?php else:
                foreach ($amenities as $amenity): ?>
              <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
              <?php endforeach;
                endif; ?>
            </div>
          </div>

          <div class="border-t border-slate-200 pt-8 mb-8">
            <h3 class="text-xl font-semibold mb-4">
              Hosted by
              <a href="/stayhaven/host-profile.php?id=<?php echo $room['host_id']; ?>"
                class="text-rose-600 hover:text-rose-700">
                <?php echo htmlspecialchars($room['host_name']); ?>
              </a>
            </h3>
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-rose-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white font-medium text-lg">
                  <?php echo substr($room['host_name'], 0, 1); ?>
                </span>
              </div>
              <div>
                <p class="text-slate-600 mb-2">
                  <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1"></i>
                  Joined <?php echo date('F Y', strtotime($room['host_joined'])); ?>
                </p>
                <p class="text-slate-600">
                  <i data-lucide="mail" class="w-4 h-4 inline-block mr-1"></i>
                  Contact: <?php echo htmlspecialchars($room['host_email']); ?>
                </p>
              </div>
            </div>
          </div>

          <div class="booking-section mb-4">
            <div class="price">
              <strong>NPR.<?php echo number_format($room['price'], 2); ?></strong> per night
            </div>
            <a href="booking-form.php?id=<?php echo $room_id; ?>" class="btn btn-primary">Book Now</a>
          </div>

          <div class="flex gap-2">
            <button data-room-id="<?php echo $room_id; ?>"
              class="w-full px-4 bg-slate-100 hover:bg-slate-200 text-slate-800 py-2 rounded-md border border-slate-300"
              onclick="addToFavorites(this)">
              Add to favourites
            </button>
            <button onclick="copyToClipboard()"
              class="w-full px-4 bg-white hover:bg-slate-100 text-slate-800 py-2 rounded-md border border-slate-300">
              Share
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <section class="latest-listings-stds py-10">
    <div class="latest-listings-stds__wrapper container">
      <h1 class="featured-listings__title">
        Similar Listings
      </h1>
      <?php if (empty($similarListings)): ?>
      <div class="flex flex-col items-center justify-center py-12 bg-slate-50 rounded-lg">
        <i data-lucide="home" class="w-16 h-16 text-slate-300 mb-4"></i>
        <h3 class="text-lg font-medium text-slate-900 mb-2">No similar listings found</h3>
        <p class="text-slate-500 text-center max-w-md mb-6">We couldn't find any listings with similar features at the
          moment</p>
        <a href="/stayHaven/listings.php" class="text-rose-600 hover:text-rose-700 font-medium">
          Browse all listings →
        </a>
      </div>
      <?php else: ?>
      <div class="featurose-listings__list grid grid-cols-4 gap-4">
        <?php foreach ($similarListings as $listing): ?>
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
                <?php if ($listing['room_type'] !== 'Entire place'): ?>
                <span class="quantity"><?php echo htmlspecialchars($listing['quantity']); ?> units available</span>
                <?php endif; ?>
              </div>
              <div class="listing-footer">
                <span class="price">NPR.<?php echo htmlspecialchars($listing['price']); ?> / night</span>
                <a href="/stayHaven/details.php?id=<?php echo $listing['id']; ?>" class="view-btn">View Details</a>
              </div>
            </div>
          </a>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
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
  function copyToClipboard() {
    // get the link to the current page
    const link = window.location.href;
    navigator.clipboard.writeText(link).then(() => {
      alert('Link copied to clipboard');
    }).catch((err) => {
      console.error('Failed to copy: ', err);
    });
  }

  function addToFavorites(button) {
    // Get room_id from the button's data attribute
    const roomId = button.getAttribute('data-room-id');

    // Get existing favorites from localStorage
    let favorites = JSON.parse(localStorage.getItem('favoriteRooms')) || [];

    // Check if room is already in favorites
    if (!favorites.includes(roomId)) {
      // Add room_id to favorites array
      favorites.push(roomId);

      // Save updated favorites back to localStorage
      localStorage.setItem('favoriteRooms', JSON.stringify(favorites));

      // Optional: Change button text/style to show it's favorited
      button.textContent = 'Added to favourites';
      button.classList.add('bg-slate-200');
    } else {
      // Optional: Alert if already in favorites
      alert('This room is already in your favorites!');
    }
  }
  </script>

  <script>
  lucide.createIcons();
  </script>
</body>

</html>