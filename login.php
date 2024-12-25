 <?php
session_start();
require_once 'db/config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $stmt = $conn->prepare("SELECT id, email, password, name, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                if ($user['role'] === 'host') {
                    header("Location: host_dashboard/index.php");
                } else {
                    header("Location: index.php");
                }
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
   <link rel="stylesheet" href="css/styles.css">
   <link rel="stylesheet" href="css/login.css">
   <!-- Include your font links here -->
 </head>

 <body>
   <div class="container">
     <a href="/stayhaven">
       <h1 class="logo">StayHaven</h1>
     </a>

     <?php if ($error): ?>
     <div class="server-error"><?php echo htmlspecialchars($error); ?></div>
     <?php endif; ?>

     <h1>Welcome back</h1>

     <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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

       <p class="forgot-password">
         <a href="reset-password.php">Forgot password?</a>
       </p>

       <button type="submit">Log in</button>
     </form>

     <p class="signup-link">
       Don't have an account? <a href="signup.php">Sign up</a>
     </p>
   </div>

   <script>
   // Add your client-side validation script here
   </script>
 </body>

 </html>