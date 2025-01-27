<?php
session_start();
require_once 'db/config.php';

function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userDetails = null;

if ($isLoggedIn) {
    $userDetails = getUserDetails($conn, $_SESSION['user_id']);
}

$amenities = $conn->query("SELECT * FROM amenities ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$mainQuery = "SELECT DISTINCT l.*, u.name as host_name 
              FROM listings l 
              JOIN users u ON l.host_id = u.id";
$params = [];

// Dynamic search filters
if (!empty($_GET)) {
    $conditions = [];
    
    if (!empty($_GET['check_in']) && !empty($_GET['check_out'])) {
        $checkIn = $conn->real_escape_string($_GET['check_in']);
        $checkOut = $conn->real_escape_string($_GET['check_out']);
        
        // Modified subquery to check room availability based on quantity
        $conditions[] = "(
            l.id NOT IN (
                SELECT b.listing_id 
                FROM bookings b 
                WHERE b.status != 'cancelled' 
                AND (
                    (b.check_in <= '$checkOut' AND b.check_out >= '$checkIn')
                )
                GROUP BY b.listing_id 
                HAVING COUNT(*) >= (
                    SELECT quantity 
                    FROM listings 
                    WHERE id = b.listing_id
                )
            )
        )";
    }
    
    if (!empty($_GET['guests'])) {
        $guests = (int)$_GET['guests'];
        $conditions[] = "l.max_guests >= $guests";
    }
    
    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $conditions[] = "(l.title LIKE '%$search%' OR l.description LIKE '%$search%' OR l.location LIKE '%$search%')";
    }
    
    if (!empty($_GET['room_type'])) {
        $roomType = $conn->real_escape_string($_GET['room_type']);
        $conditions[] = "l.room_type = '$roomType'";
    }
    
    if (!empty($_GET['max_price'])) {
        $maxPrice = (float)$_GET['max_price'];
        $conditions[] = "l.price <= $maxPrice";
    }
    
    if (!empty($_GET['amenities'])) {
        $amenityIds = array_map('intval', $_GET['amenities']);
        $amenityStr = implode(',', $amenityIds);
        $conditions[] = "l.id IN (
            SELECT listing_id 
            FROM listing_amenities 
            WHERE amenity_id IN ($amenityStr)
            GROUP BY listing_id 
            HAVING COUNT(DISTINCT amenity_id) = " . count($amenityIds) . "
        )";
    }
    
    if (!empty($conditions)) {
        $mainQuery .= " WHERE " . implode(' AND ', $conditions);
    }
}

$mainQuery .= " ORDER BY l.created_at DESC";
$listings = $conn->query($mainQuery)->fetch_all(MYSQLI_ASSOC);

if ($conn->error) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Listings - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/search.css">
  <link rel="stylesheet" href="css/styles.css">
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

  <main class="container mx-auto px-4 py-8">
    <section class="filters-section mb-8">
      <form action="" method="GET" class="space-y-4">
        <div class="date-inputs md:col-span-2 grid grid-cols-2 gap-4">
          <div class="filter-group">
            <label>Check In</label>
            <input type="date" name="check_in" value="<?php echo $_GET['check_in'] ?? ''; ?>" class="w-full rounded-lg">
          </div>
          <div class="filter-group">
            <label>Check Out</label>
            <input type="date" name="check_out" value="<?php echo $_GET['check_out'] ?? ''; ?>"
              class="w-full rounded-lg">
          </div>
        </div>
        <div class="search-input">
          <input type="text" name="search" placeholder="Search locations, titles..."
            value="<?php echo $_GET['search'] ?? ''; ?>" class="w-full rounded-lg">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="filter-group">
            <select name="room_type" class="w-full rounded-lg">
              <option value="">Any room type</option>
              <?php
              $types = ['Entire place', 'Private room', 'Shared room'];
              foreach ($types as $type) {
                  $selected = (isset($_GET['room_type']) && $_GET['room_type'] === $type) ? 'selected' : '';
                  echo "<option value=\"$type\" $selected>$type</option>";
              }
            ?>
            </select>
          </div>

          <div class="filter-group">
            <input type="number" name="max_price" placeholder="Max price"
              value="<?php echo $_GET['max_price'] ?? ''; ?>" class="w-full rounded-lg">
          </div>

          <div class="filter-group">
            <input type="number" name="guests" placeholder="Number of guests"
              value="<?php echo $_GET['guests'] ?? ''; ?>" class="w-full rounded-lg">
          </div>

          <div class="amenities-filter md:col-span-2">
            <h3 class="text-lg font-semibold mb-2">Amenities</h3>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($amenities as $amenity): ?>
              <label class="amenity-checkbox">
                <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>"
                  <?php echo in_array($amenity['id'], $_GET['amenities'] ?? []) ? 'checked' : ''; ?>>
                <span><?php echo htmlspecialchars($amenity['name']); ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="md:col-span-2 text-right">
            <button type="submit" class="search-btn">Search</button>
          </div>
        </div>
      </form>
    </section>

    <section class="listings-grid">
      <?php foreach ($listings as $listing): ?>
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
              <span class="price">NPR.<?php echo htmlspecialchars($listing['price']); ?> / night</span>
              <a href="details.php?id=<?php echo $listing['id']; ?>" class="view-btn">View Details</a>
            </div>
          </div>
        </a>
      </article>
      <?php endforeach; ?>
    </section>
  </main>
</body>
<script>
lucide.createIcons();
</script>

</html>