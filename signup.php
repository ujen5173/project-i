<?php
session_start();
require_once 'db/config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Server-side validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($role !== 'guest' && $role !== 'host') {
        $error = "Invalid role selected";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = $role;
                header("Location: " . ($role === 'host' ? 'host_dashboard.php' : 'index.php'));
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
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
  <title>Sign Up - StaySpot</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/signup.css">
  <!-- Include your font links here -->
</head>

<body>
  <div class="container">
    <a href="/stayhaven">
      <h1 class="logo">StayHaven</h1>
    </a>

    <?php if ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <h1>Create an account</h1>

    <form id="registrationForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="input-group">
        <input type="text" name="name" id="name" placeholder="Full name" required>
        <div class="error-message" id="nameError"></div>
      </div>

      <div class="input-group">
        <input type="email" name="email" id="email" placeholder="Email address" required>
        <div class="error-message" id="emailError"></div>
      </div>

      <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <div class="error-message" id="passwordError"></div>
      </div>

      <div class="input-group">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
        <div class="error-message" id="confirmPasswordError"></div>
      </div>

      <div class="input-group">
        <select name="role" id="role" required>
          <option value="">Select your role</option>
          <option value="guest">Guest</option>
          <option value="host">Host</option>
        </select>
        <div class="error-message" id="roleError"></div>
      </div>

      <button type="submit">Sign up</button>
    </form>

    <p class="login-link">
      Already have an account? <a href="login.php">Log in</a>
    </p>
  </div>

</body>

</html>