<?php
session_start();
require_once __DIR__ . '/db/config.php';

// Fetch room details
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM listings WHERE id = ? ");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    // Redirect or show error if room not found
    header("Location: index.php");
    exit();
}

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

  <!-- CSS -->
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/room-detail.css">
</head>

<body>
  <header class="header">
    <nav class="nav container">
      <!-- Navigation from index.php -->
      <div class="left-nav">

        <div class="nav__logo">
          <a href="/index.php">
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
        </ul>
      </div>

      <?php if ($isLoggedIn): ?>
      <div class="user-menu" style="display: flex; gap: 1rem; align-items: center" id="userMenu">
        <div class="user-avatar">
          <?php if ($userDetails['name']): ?>
          <p style="color: #111;">
            Logged in as <?php echo $userDetails['name']; ?>
          </p>
          <?php endif; ?>
        </div>
        <button class="btn btn-sm">Logout</button>
      </div>
      <?php else: ?>

      <div class="btns__wrapper">
        <a href="/stayhaven/login.php">
          <button style="color: #111;" class="btn btn-link">
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
          </div>

          <div class="room-description">
            <h3>About this place</h3>
            <p><?php echo htmlspecialchars($room['description']); ?></p>
          </div>

          <div class="room-amenities">
            <h3>Amenities</h3>
            <div class="amenities-wrapper">
              <?php 
                            $amenities = json_decode($room['amenities'], true);
                            foreach ($amenities as $amenity): 
                            ?>
              <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="booking-section mb-4">
            <div class="price">
              <strong>$<?php echo number_format($room['price'], 2); ?></strong> per night
            </div>
            <button class="btn btn-primary">Book Now</button>
          </div>

          <div class="flex gap-2">
            <button
              class="w-full px-4 bg-slate-100 hover:bg-slate-200 text-slate-800 py-2 rounded-md border border-slate-300">
              Add to favourites
            </button>
            <button
              class="w-full px-4 bg-white hover:bg-slate-100 text-slate-800 py-2 rounded-md border border-slate-300">
              Share
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <section class="latest-listings-stds">
    <div class="latest-listings-stds__wrapper container">
      <h1 class="latest-listings-stds__title">
        Similar Listings
      </h1>

      <div class="latest-listings-stds__list">
        <div class="latest-listings-stds__card">
          <div class="latest-listings-stds__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="latest-listings-stds__content">
            <h1 class="latest-listings-stds__card__title">
              Pokhara, Nepal
            </h1>

            <div class="flex_wrapper">
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-hotel">
                  <path d="M10 22v-6.57" />
                  <path d="M12 11h.01" />
                  <path d="M12 7h.01" />
                  <path d="M14 15.43V22" />
                  <path d="M15 16a5 5 0 0 0-6 0" />
                  <path d="M16 11h.01" />
                  <path d="M16 7h.01" />
                  <path d="M8 11h.01" />
                  <path d="M8 7h.01" />
                  <rect x="4" y="2" width="16" height="20" rx="2" />
                </svg> <span>
                  Hostel
                </span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-dot">
                <circle cx="12.1" cy="12.1" r="1" />
              </svg>
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-users">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                20
                </span>
              </div>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> per month
            </div>
          </div>
        </div>
        <div class="latest-listings-stds__card">
          <div class="latest-listings-stds__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="latest-listings-stds__content">
            <h1 class="latest-listings-stds__card__title">
              Balkumari, Lalitpur
            </h1>

            <div class="flex_wrapper">
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-hotel">
                  <path d="M10 22v-6.57" />
                  <path d="M12 11h.01" />
                  <path d="M12 7h.01" />
                  <path d="M14 15.43V22" />
                  <path d="M15 16a5 5 0 0 0-6 0" />
                  <path d="M16 11h.01" />
                  <path d="M16 7h.01" />
                  <path d="M8 11h.01" />
                  <path d="M8 7h.01" />
                  <rect x="4" y="2" width="16" height="20" rx="2" />
                </svg> <span>
                  Room
                </span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-dot">
                <circle cx="12.1" cy="12.1" r="1" />
              </svg>
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-users">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                2
                </span>
              </div>
            </div>
            <div class="price">
              <strong>
                $73
              </strong> per month
            </div>
          </div>
        </div>
        <div class="latest-listings-stds__card">
          <div class="latest-listings-stds__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="latest-listings-stds__content">
            <h1 class="latest-listings-stds__card__title">
              Pokhara, Nepal
            </h1>

            <div class="flex_wrapper">
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-hotel">
                  <path d="M10 22v-6.57" />
                  <path d="M12 11h.01" />
                  <path d="M12 7h.01" />
                  <path d="M14 15.43V22" />
                  <path d="M15 16a5 5 0 0 0-6 0" />
                  <path d="M16 11h.01" />
                  <path d="M16 7h.01" />
                  <path d="M8 11h.01" />
                  <path d="M8 7h.01" />
                  <rect x="4" y="2" width="16" height="20" rx="2" />
                </svg> <span>
                  Room
                </span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-dot">
                <circle cx="12.1" cy="12.1" r="1" />
              </svg>
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-users">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                3
                </span>
              </div>
            </div>
            <div class="price">
              <strong>
                $20
              </strong> per month
            </div>
          </div>
        </div>
        <div class="latest-listings-stds__card">
          <div class="latest-listings-stds__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="latest-listings-stds__content">
            <h1 class="latest-listings-stds__card__title">
              Pokhara, Nepal
            </h1>

            <div class="flex_wrapper">
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-hotel">
                  <path d="M10 22v-6.57" />
                  <path d="M12 11h.01" />
                  <path d="M12 7h.01" />
                  <path d="M14 15.43V22" />
                  <path d="M15 16a5 5 0 0 0-6 0" />
                  <path d="M16 11h.01" />
                  <path d="M16 7h.01" />
                  <path d="M8 11h.01" />
                  <path d="M8 7h.01" />
                  <rect x="4" y="2" width="16" height="20" rx="2" />
                </svg> <span>
                  Room
                </span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-dot">
                <circle cx="12.1" cy="12.1" r="1" />
              </svg>
              <div class="rating_wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-users">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                1
                </span>
              </div>
            </div>
            <div class="price">
              <strong>
                $105
              </strong> per month
            </div>
          </div>
        </div>
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


  <script>
  lucide.createIcons();
  </script>
</body>

</html>