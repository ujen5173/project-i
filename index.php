<?php
session_start();
require_once __DIR__ . '/db/config.php';

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


// Fetch featured listings
$stmt = $conn->prepare("SELECT * FROM listings ORDER BY created_at DESC LIMIT 4");
$stmt->execute();
$featuredListings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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


  <title>Document</title>

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <!-- CSS -->
  <link rel="stylesheet" href="css/styles.css">
  <!-- tailwindcss -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
  <link rel="stylesheet" href="css/index.css">

</head>

<body>
  <header class="header">
    <nav class="nav container">
      <div class="left-nav">
        <div class="nav__logo">
          <a href="/stayHaven/index.php">
            <h1 class="logo">StayHaven</h1>
          </a>
        </div>
        <ul class="nav__list">
          <li class="nav__item">
            <a href="/stayhaven/index.php" class="nav__link">Home</a>
          </li>
          <li class="nav__item">
            <a href="#" class="nav__link">About</a>
          </li>
          <li class="nav__item">
            <a href="/stayhaven/listings.php" class="nav__link">Listings</a>
          </li>
          <li class="nav__item">
            <a href="#" class="nav__link">Contact</a>
          </li>
        </ul>
      </div>

      <?php if ($isLoggedIn): ?>
      <div class="user-menu" style="display: flex; gap: 1rem; align-items: center" id="userMenu">
        <div class="user-avatar">
          <?php if ($userDetails['name']): ?>
          <p style="color: white;">
            Logged in as <?php echo $userDetails['name']; ?>
          </p>
          <?php endif; ?>
        </div>

        <?php if ($userDetails['role'] === "host"): ?>
        <div>
          <a href="host_dashboard/index.php">
            <button class="btn btn-sm">Dashboard</button>
          </a>
        </div>
        <?php endif; ?>
        <a href="logout.php">
          <button class="btn btn-sm">Logout</button>
        </a>
      </div>
      <?php else: ?>

      <div class="btns__wrapper">
        <a href="/stayhaven/login.php">
          <button style="color: white;" class="btn btn-link">
            Login / Sign in
          </button>
        </a>
        <a href="/stayhaven/sign-up.php">
          <button class="btn btn-secondary">
            Register
          </button>
        </a>

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
        <?php foreach ($featuredListings as $listing): ?>
        <a href="/stayHaven/details.php?id=<?php echo $listing['id'] ?>" class="featured-listing">
          <div class="featured-listing__img">
            <?php if ($listing['image_url']): ?>
            <img src="/stayHaven/<?php echo htmlspecialchars($listing['image_url']); ?>"
              alt="<?php echo htmlspecialchars($listing['title']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
            <img src="https://via.placeholder.com/300" alt="Placeholder image" class="w-full h-full object-cover">
            <?php endif; ?>
          </div>
          <div class="featured-listing__content">
            <h2 class="featured-listing__title">
              <?php echo htmlspecialchars($listing['title']); ?>
            </h2>
            <p class="listing-location" style="display: flex; align-items:center; gap: 6px; margin-bottom: 15px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-map-pin-check">
                <path
                  d="M19.43 12.935c.357-.967.57-1.955.57-2.935a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 1.202 0 32.197 32.197 0 0 0 .813-.728" />
                <circle cx="12" cy="10" r="3" />
                <path d="m16 18 2 2 4-4" />
              </svg>
              <?php echo htmlspecialchars($listing['location']); ?>
            </p>
            <div class=" price">
              <strong>
                $<?php echo number_format($listing['price'], 2); ?>
              </strong> per night
            </div>
          </div>
        </a>
        <?php endforeach; ?>
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
        &copy; 2024 StayHaven. All rights reserved.
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

</html>