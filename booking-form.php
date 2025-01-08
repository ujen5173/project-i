<!-- booking-form.php -->
<?php
session_start();
require_once __DIR__ . '/db/config.php';
require_once 'EsewaPayment.php';

function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, name, email, role, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userDetails = null;

if ($isLoggedIn) {
    $userDetails = getUserDetails($conn, $_SESSION['user_id']);
    $_SESSION['email'] = $userDetails['email'];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM listings WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT check_in, check_out 
    FROM bookings 
    WHERE listing_id = ? 
    AND check_out >= CURRENT_DATE()
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

$booked_dates = [];
while ($booking = $result->fetch_assoc()) {
    $period = new DatePeriod(
        new DateTime($booking['check_in']),
        new DateInterval('P1D'),
        (new DateTime($booking['check_out']))->modify('+1 day')
    );
    
    foreach ($period as $date) {
        $booked_dates[] = $date->format('Y-m-d');
    }
}
$stmt->close();

$booked_dates_json = json_encode($booked_dates);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book <?php echo htmlspecialchars($room['title']); ?></title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.tailwindcss.com"></script>
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

  <div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Book <span
        class="text-rose-600"><?php echo htmlspecialchars($room['title']); ?></span></h1>

    <?php if (empty($userDetails['phone'])): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-yellow-700">
            Please add your phone number before booking.
            <a href="/stayhaven/user_profile.php" class="font-medium underline text-yellow-700 hover:text-yellow-600">
              Update your profile here
            </a>
          </p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <form id="bookingForm" class="space-y-4">
      <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">

      <div>
        <label class="block mb-2">Check-in Date:</label>
        <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>"
          class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="block mb-2">Check-out Date:</label>
        <input type="date" name="check_out" required min="<?php echo date('Y-m-d'); ?>"
          class="w-full p-2 border rounded">
      </div>

      <div class="bg-white p-4 rounded shadow">
        <h2 class="font-semibold mb-2">Price Details</h2>
        <p>Price per night: NPR.<?php echo number_format($room['price'], 2); ?></p>
        <div id="totalPrice" class="hidden mt-2">
          <p>Total price: NPR.<span id="priceAmount">0</span></p>
        </div>
      </div>

      <div class="space-y-2">
        <label class="block font-medium">Payment Method:</label>
        <div class="space-y-2">
          <label class="flex items-center">
            <input type="radio" name="payment_method" value="esewa" class="mr-2">
            eSewa
          </label>
          <label class="flex items-center">
            <input type="radio" name="payment_method" value="cash" class="mr-2">
            Cash on arrival
          </label>
        </div>
      </div>

      <button type="submit" class="w-full bg-rose-600 text-white py-2 px-4 rounded hover:bg-rose-700">
        Reserve Room
      </button>
    </form>

    <div id="esewaPayment"></div>
  </div>

  <script>
  const bookedDates = <?php echo $booked_dates_json; ?>;
  const checkInInput = document.querySelector('input[name="check_in"]');
  const checkOutInput = document.querySelector('input[name="check_out"]');
  const pricePerNight = <?php echo $room['price']; ?>;
  const totalPriceDiv = document.getElementById('totalPrice');
  const priceAmount = document.getElementById('priceAmount');
  const bookingForm = document.getElementById('bookingForm');
  const esewaPaymentDiv = document.getElementById('esewaPayment');

  function isDateBooked(date) {
    const dateString = date.toISOString().split('T')[0];
    return bookedDates.includes(dateString);
  }

  function setDateConstraints(input) {
    const currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);
    const disabledDates = bookedDates.map(date => date);

    input.addEventListener('input', function() {
      const selectedDate = this.value;
      if (disabledDates.includes(selectedDate)) {
        alert('This date is already booked. Please select another date.');
        this.value = '';
      }
      calculatePrice();
    });
  }

  function calculatePrice() {
    if (checkInInput.value && checkOutInput.value) {
      const start = new Date(checkInInput.value);
      const end = new Date(checkOutInput.value);
      const days = (end - start) / (1000 * 60 * 60 * 24);
      const total = days * pricePerNight;

      totalPriceDiv.classList.remove('hidden');
      priceAmount.textContent = total.toFixed(2);
      return total;
    }
    return 0;
  }

  setDateConstraints(checkInInput);
  setDateConstraints(checkOutInput);

  checkInInput.addEventListener('change', function() {
    if (this.value) {
      checkOutInput.min = this.value;
      if (checkOutInput.value && checkOutInput.value < this.value) {
        checkOutInput.value = '';
      }
      calculatePrice();
    }
  });

  // Replace the existing bookingForm event listener in booking-form.php with this:

  bookingForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    <?php if (empty($userDetails['phone'])): ?>
    alert('Please add your phone number in your profile before booking.');
    return;
    <?php endif; ?>

    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
      alert('Please select a payment method');
      return;
    }

    try {
      const totalAmount = calculatePrice();
      const formData = {
        room_id: <?php echo $room_id; ?>,
        check_in: checkInInput.value,
        check_out: checkOutInput.value,
        amount: totalAmount,
        payment_method: paymentMethod.value
      };

      console.log('Sending data:', formData);

      const response = await fetch('process-booking.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const text = await response.text();
      console.log('Raw response:', text);

      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error('Failed to parse JSON:', text);
        throw new Error('Invalid JSON response from server');
      }

      console.log('Parsed response:', data);

      if (data.error) {
        throw new Error(data.error);
      }

      if (paymentMethod.value === 'esewa' && data.esewaForm) {
        esewaPaymentDiv.innerHTML = data.esewaForm;
        const form = esewaPaymentDiv.querySelector('form');
        if (form) {
          form.submit();
        }
      } else if (data.success) {
        window.location.href = 'booking-confirmation.php?id=' + data.booking_id;
      } else {
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error('Error:', error);
      alert(error.message || 'An error occurred. Please try again.');
    }
  });
  </script>
</body>

</html>