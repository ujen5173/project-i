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
 
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width='device-width', initial-scale=1.0">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/room-detail.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Document</title>
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

  <div style="height: 70px;">

  </div>

  <main style="padding: 40px 0">
    <section class="container">

      <h1 class=" text-3xl font-semibold mb-4">
        Listings
      </h1>

      <div class="most-popular-hotels__list">
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/470770143.webp?k=299ef4606678b9b2afdefef73f9fe68cb18226098bfb5439d1265f392b32d6b5&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/329596525.webp?k=8438bdd1e1023770c3499dfc44667f2665da4355da811d0a244ed6fb0a18fc93&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Holiday Spot
            </h1>
            <p class="hotel-location">
              Budhanikantha, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.3 (1600+)
              </span>
            </div>
            <div class="price">
              <strong>
                $500
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://a0.muscache.com/im/pictures/hosting/Hosting-U3RheVN1cHBseUxpc3Rpbmc6MTI4NzU3MTgwMzg2NDk2OTQxMw%3D%3D/original/02ea369e-50f3-4461-a68c-3556cb35aff7.jpeg?im_w=720"
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Aparthotel Stare Miasto
            </h1>
            <p class="hotel-location">
              Old Town, Poland, Krakow
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.4 (3000+)
              </span>
            </div>
            <div class="price">
              <strong>
                $17
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
        <div class="most-popular-hotel">
          <div class="most-popular-hotel__img">
            <img
              src="https://cf.bstatic.com/xdata/images/hotel/square600/483812425.webp?k=f4a1e128538c8c9450775de46a668c6d72bd8ee4230d8eabf7c4b2a2b7a147c6&o="
              alt="Hotel 1">
          </div>
          <div class="most-popular-hotel__content">
            <h1 class="most-popular-hotel__title">
              Himalayan Hotel
            </h1>
            <p class="hotel-location">
              Pokhara, Nepal
            </p>
            <div class="rating_wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-star">
                <path
                  d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
              </svg>
              <span>
                4.5 (500+)
              </span>
            </div>
            <div class="price">
              <strong>
                $50
              </strong> night
            </div>
          </div>
        </div>
      </div>
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