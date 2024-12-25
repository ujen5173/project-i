<?php
require_once 'db/config.php';

// Get amenities
$amenities = $conn->query("SELECT * FROM amenities ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Base query
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
        $conditions[] = "l.id NOT IN (
            SELECT listing_id FROM bookings 
            WHERE status != 'cancelled' 
            AND ((check_in BETWEEN '$checkIn' AND '$checkOut') 
            OR (check_out BETWEEN '$checkIn' AND '$checkOut'))
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

// Check for query errors
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
</head>

<body class="bg-gray-50">
  <header class="bg-white shadow-sm">
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
      <a href="/stayHaven/index.php" class="text-2xl font-bold text-red-600">StayHaven</a>
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
            <img src="/stayHaven/<?php echo htmlspecialchars($listing['image_url']); ?>"
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
  </main>
</body>

</html>