<?php
session_start();
require_once 'db/config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Server-side validation
    if (empty($email)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store reset token
            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $reset_token, $token_expiry, $email);
            
            if ($update_stmt->execute()) {
                // In a real-world scenario, send reset link via email
                $error = "Password reset link sent to your email";
            } else {
                $error = "Error processing your request";
            }
            $update_stmt->close();
        } else {
            $error = "Email not found";
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
  <title>Reset Password - StaySpot</title>

  <!-- Lato Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Kalam:wght@300;400;700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Permanent+Marker&display=swap"
    rel="stylesheet">

  <!-- Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">

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

    <h1>Reset Password</h1>

    <form id="resetPasswordForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="input-group">
        <label for="email">Email address</label>
        <input type="email" name="email" id="email" placeholder="Email address" required>
        <div class="error-message" id="emailError"></div>
      </div>

      <button type="submit">Reset Password</button>
    </form>

    <p class="signup-link">
      Remember your password? <a href="login.php">Log in</a>
    </p>
  </div>

  <script>
  document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    let isValid = true;
    const email = document.getElementById('email');
    const emailError = document.getElementById('emailError');

    // Reset error messages
    emailError.style.display = 'none';

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
      emailError.textContent = 'Please enter a valid email address';
      emailError.style.display = 'block';
      isValid = false;
    }

    if (!isValid) {
      e.preventDefault();
    }
  });
  </script>
</body>

</html>