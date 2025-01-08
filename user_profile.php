<?php
session_start();
require_once 'db/config.php';

$userId = $_SESSION['user_id'] ?? 1; // Using 1 for demo
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Phone number validation
    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters
    
    // Validate phone number (Nepal format: landline 8 digits starting with 01, mobile 10 digits starting with 98/97)
    if (!empty($phone)) {
        if (strlen($phone) === 8 && substr($phone, 0, 2) === '01') {
            // Valid landline number
            $formatted_phone = $phone;
        } elseif (strlen($phone) === 10 && (substr($phone, 0, 2) === '98' || substr($phone, 0, 2) === '97')) {
            // Valid mobile number
            $formatted_phone = $phone;
        } else {
            $error_message = "Please enter a valid phone number. Use either:
                            - Landline: 8 digits starting with 01
                            - Mobile: 10 digits starting with 98 or 97";
        }
    }

    // Basic validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } 
    elseif (empty($error_message)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update basic info
            $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $email, $formatted_phone, $userId);
            $stmt->execute();

            // Handle password update if requested
            if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
                // Verify current password
                $sql = "SELECT password FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if (password_verify($_POST['current_password'], $user['password'])) {
                    if ($_POST['new_password'] === $_POST['confirm_password']) {
                        $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $hashed_password, $userId);
                        $stmt->execute();
                    } else {
                        throw new Exception("New passwords do not match.");
                    }
                } else {
                    throw new Exception("Current password is incorrect.");
                }
            }

            $conn->commit();
            $success_message = "Profile updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}
// Function to get user details
function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
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

// Clean phone number for display
$phone_display = $userDetails['phone'];
if (!empty($phone_display)) {
    $phone_display = preg_replace('/[^0-9]/', '', $phone_display);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Settings - StayHaven</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script src="//unpkg.com/alpinejs" defer></script>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/user_profile.css">
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

  <div class="container mx-auto px-4 py-8">
    <div class="profile-card">
      <h1 class="text-2xl font-semibold mb-6">Profile Settings</h1>

      <?php if ($success_message): ?>
      <div class="success-alert">
        <?= htmlspecialchars($success_message) ?>
      </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
      <div class="error-alert">
        <?= htmlspecialchars($error_message) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" class="settings-form">
        <!-- Profile Information Section -->
        <div class="form-section">
          <h2>Basic Information</h2>

          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($userDetails['name']) ?>" required>
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" readonly name="email" value="<?= htmlspecialchars($userDetails['email']) ?>">
          </div>

          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" max="10" value="<?= htmlspecialchars($phone_display) ?>"
              placeholder="98XXXXXXXX or 01XXXXXX">
            <small class="text-gray-500">Format: 98XXXXXXXX (mobile) or 01XXXXXX (landline)</small>
          </div>
        </div>



        <div class="form-actions">
          <button type="submit" class="save-button">Save Changes</button>
          <a href="index.php" class="cancel-button">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <script>
  $(document).ready(function() {
    // Phone number formatting - remove formatting logic since we want plain numbers
    $('#phone').on('input', function() {
      var number = $(this).val().replace(/[^\d]/g, '');
      if (number.length <= 10) {
        $(this).val(number);
      }
    });
  });
  </script>

  <script>
  lucide.createIcons();
  </script>

  <?php $conn->close(); ?>
</body>

</html>