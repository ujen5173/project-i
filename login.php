<?php
session_start();
require_once 'db/config.php'; // Database connection file

// Initialize error variable
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Server-side validation
    if (empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, email, password, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - StaySpot</title>
  <link rel="stylesheet" href="/css/index.css">
  <link rel="stylesheet" href="/css/style.css">
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

  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/login.css">
</head>

<body>
  <div class="container">
    <a href="/stayhaven">
      <h1 class="logo">
        StayHaven
      </h1>
    </a>

    <?php if ($error): ?>
    <div class="server-error">
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <h1>Welcome back</h1>

    <form id="loginForm" method="POST" style="margin-bottom: 10px"
      action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="input-group">
        <label for="email">Email address</label>
        <input type="email" name="email" id="email" placeholder="Email address" required>
        <div class="error-message" id="emailError"></div>
      </div>

      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <div class="error-message" id="passwordError"></div>
      </div>
      <p style="text-align: right; margin-bottom: 10px">
        Forgot password? <a href="reset-password.php">Reset</a>
      </p>
      <button type="submit">Log in</button>
    </form>

    <p class="signup-link">
      Don't have an account? <a href="signup.php">Sign up</a>
    </p>
  </div>

  <script>
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    let isValid = true;
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');

    // Reset error messages
    emailError.style.display = 'none';
    passwordError.style.display = 'none';

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
      emailError.textContent = 'Please enter a valid email address';
      emailError.style.display = 'block';
      isValid = false;
    }

    // Password validation
    if (password.value.length < 8) {
      passwordError.textContent = 'Password must be at least 8 characters long';
      passwordError.style.display = 'block';
      isValid = false;
    }

    if (!isValid) {
      e.preventDefault();
    }
  });
  </script>
</body>

</html>