<?php
session_start();
require_once __DIR__ . '/db/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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


// Get favorites from localStorage via JavaScript and query the database
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Favorites - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="css/index.css">
  <style>
  body {
    font-family: 'Inter', sans-serif;
  }
  </style>
  <script src="//unpkg.com/alpinejs" defer></script>
</head>

<body class="bg-slate-50 min-h-screen">
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
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-bold text-slate-900">My Favorites</h1>
      <a href="/stayhaven/listings.php" class="text-rose-600 hover:text-rose-700 flex items-center gap-2">
        <i data-lucide="search" class="w-4 h-4"></i>
        Browse Listings
      </a>
    </div>

    <div id="favorites-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <!-- Content will be loaded dynamically -->
    </div>

    <!-- Empty state (initially hidden) -->
    <div id="empty-state" class="hidden text-center py-16">
      <i data-lucide="heart" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
      <h3 class="text-lg font-medium text-slate-900 mb-2">No favorites yet</h3>
      <p class="text-slate-500 mb-8">Start adding places you love to your favorites!</p>
      <a href="/stayhaven/listings.php"
        class="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors">
        Explore Listings
      </a>
    </div>
  </main>

  <script defer>
  document.addEventListener('DOMContentLoaded', function() {
    loadFavorites();
  });

  function loadFavorites() {
    const favoritesContainer = document.getElementById('favorites-container');
    const emptyState = document.getElementById('empty-state');
    const favorites = JSON.parse(localStorage.getItem('favoriteRooms')) || [];

    if (favorites.length === 0) {
      favoritesContainer.classList.add('hidden');
      emptyState.classList.remove('hidden');
      return;
    }

    favoritesContainer.classList.remove('hidden');
    emptyState.classList.add('hidden');

    // Fetch listing details for each favorite
    fetch('get-favourites.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          favorites: favorites
        })
      })
      .then(response => response.json())
      .then(listings => {
        console.log({
          listings
        })
        favoritesContainer.innerHTML = listings.map(listing => `
          <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-slate-200 hover:shadow-md transition-shadow">
              <div class="relative h-48">
                <img 
                  src="/stayHaven${listing.image_url}" 
                  alt="${listing.title}"
                  class="w-full h-full object-cover"
                >
                <button 
                  onclick="removeFavorite(${listing.id}, this)"
                  class="absolute top-4 right-4 bg-white p-2 rounded-full shadow-sm hover:bg-red-50 transition-colors"
                >
                  <i data-lucide="heart" class="w-5 h-5 text-red-500"></i>
                </button>
              </div>
              
              <div class="p-4">
                <h3 class="font-semibold text-slate-900 mb-2">${listing.title}</h3>
                
                <div class="flex items-center text-slate-600 text-sm mb-2">
                  <i data-lucide="map-pin" class="w-4 h-4 mr-1"></i>
                  ${listing.location}
                </div>

                <div class="flex items-center justify-between mt-4">
                  <div class="text-slate-900">
                    <span class="font-semibold">NPR.${listing.price}</span> / night
                  </div>
                  <a href="/stayhaven/details.php?id=${listing.id}" class="text-rose-600 hover:text-rose-700 text-sm font-medium">
                    View Details →
                  </a>
                </div>
              </div>
          </div>
      `).join('');

        // Reinitialize Lucide icons for the new content
        lucide.createIcons();
      });
  }

  function removeFavorite(roomId, button) {
    const favorites = JSON.parse(localStorage.getItem('favoriteRooms')) || [];
    const newFavorites = favorites.filter(id => id !== roomId.toString());
    localStorage.setItem('favoriteRooms', JSON.stringify(newFavorites));

    // Remove the card with animation
    const card = button.closest('.bg-white');
    card.style.opacity = '0';
    card.style.transform = 'scale(0.95)';
    card.style.transition = 'all 0.2s ease-out';

    setTimeout(() => {
      loadFavorites(); // Reload the entire grid
    }, 200);
  }
  </script>

  <script>
  lucide.createIcons();
  </script>
</body>

</html>