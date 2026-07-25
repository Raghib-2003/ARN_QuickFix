<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$error = "";

// If already logged in, redirect to correct dashboard view instantly
if (isset($_SESSION["user_id"], $_SESSION["role"])) {
  $r = $_SESSION["role"];
  if ($r === "admin") { header("Location: admin-dashboard.php"); exit; }
  if ($r === "technician") { header("Location: technician-dashboard.php"); exit; }
  if ($r === "manager") { header("Location: manager-dashboard.php"); exit; }
  header("Location: client-dashboard.php"); exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $email = trim($_POST["email"] ?? "");
  $pass  = $_POST["password"] ?? "";
  $role  = trim($_POST["role"] ?? "");

  if ($email === "" || $pass === "" || $role === "") {
    $error = "Please fill in all fields.";
  } else {

    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
      $error = "Invalid email or password.";
    } elseif (!password_verify($pass, $user["password_hash"])) {
      $error = "Invalid email or password.";
    } elseif ($user["role"] !== $role) {
      $error = "Role does not match this account.";
    } else {

      $_SESSION["user_id"] = $user["id"];
      $_SESSION["name"]    = $user["name"];
      $_SESSION["role"]    = $user["role"];

      if ($user["role"] === "admin") {
        header("Location: admin-dashboard.php");
      } elseif ($user["role"] === "technician") {
        header("Location: technician-dashboard.php");
      } elseif ($user["role"] === "manager") {
        header("Location: manager-dashboard.php");
      } else {
        header("Location: client-dashboard.php");
      }
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | ARN QuickFix Ltd.</title>

<style>
    :root {
      --primary-color: #00C2CB;
      --text-dark: #333333;
      --text-muted: #888888;
      --border-color: #e2e8f0;
      --bg-card: #ffffff;
      --font-stack: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    body {
      background-color: #f1f5f9 !important;
      font-family: var(--font-stack);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }

    .login-container {
      background: var(--bg-card);
      width: 100%;
      max-width: 500px;
      padding: 45px;
      border-radius: 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
      box-sizing: border-box;
      position: relative;
      text-align: center;
    }

    .back-btn {
      position: absolute;
      top: 45px;
      left: 45px;
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-dark);
      padding: 0;
      text-decoration: none;
      transition: color 0.2s ease;
      z-index: 10;
      display: inline-flex;
      align-items: center;
    }

    .back-btn:hover {
      color: var(--primary-color);
    }

    .brand-title {
      color: var(--primary-color);
      font-size: 32px;
      font-weight: 700;
      margin: 15px 0 10px 0;
    }

    .subtitle {
      color: var(--text-muted);
      font-size: 16px;
      margin: 0 0 35px 0;
    }

    .form-group {
      margin-bottom: 24px;
      text-align: left;
    }

    .form-group label {
      display: block;
      font-size: 14px;
      color: var(--text-dark);
      margin-bottom: 8px;
      font-weight: 500;
    }

    .input-wrapper, .select-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }

    .input-wrapper input, .select-wrapper select {
      width: 100% !important;
      height: 54px !important;
      padding: 0 45px 0 16px !important;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-size: 14px;
      outline: none;
      box-sizing: border-box;
      color: var(--text-dark);
      background-color: #ffffff;
    }

    .select-wrapper select {
      padding: 0 40px 0 16px !important;
      appearance: none !important;
      -webkit-appearance: none !important;
      -moz-appearance: none !important;
      cursor: pointer;
    }

    /* Fixed Native Chevron positioning alignment */
    .select-chevron {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      color: var(--text-dark);
    }

    .input-wrapper input::placeholder {
      color: #cbd5e1;
    }

    .toggle-password-btn {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #cbd5e1;
      font-size: 1.1rem;
      padding: 0;
      z-index: 5;
    }

    .submit-btn {
      width: 100%;
      background-color: var(--primary-color);
      color: white;
      border: none;
      height: 54px;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 15px;
      transition: background-color 0.2s ease;
    }

    .submit-btn:hover {
      background-color: #06aeb6;
    }

    .footer-text {
      font-size: 16px;
      color: var(--text-muted);
      margin-top: 30px;
    }

    .footer-text a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-container">
  
  <!-- Back Action Arrow SVG wrapper (Bypasses FontAwesome loading errors completely) -->
  <a href="index.php" class="back-btn">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
  </a>

  <!-- Branding Header -->
  <div class="mb-4">
    <h2 class="brand-title">ARN QuickFix Ltd.</h2>
    <p class="subtitle">Log in to your account</p>
  </div>

  <!-- Backend Error Alerts -->
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4 text-start small">
      <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <!-- Core Login Form -->
  <form method="POST" action="login.php">
    
    <!-- Email Input -->
    <div class="form-group">
      <label>Email</label>
      <div class="input-wrapper">
        <input type="email" name="email" placeholder="example@something.com" required>
      </div>
    </div>

    <!-- Password Input with Custom SVG Visibility Trigger -->
    <div class="form-group">
      <label>Password</label>
      <div class="input-wrapper">
        <input type="password" name="password" id="passwordField" placeholder="Enter your password" required>
        <button type="button" class="toggle-password-btn" id="passwordToggleBtn">
          <!-- Inline Eye SVG icon component -->
          <svg id="eyeSvg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        </button>
      </div>
    </div>

    <!-- Role Dropdown with Fixed SVG Chevron -->
    <div class="form-group">
      <label>Login As</label>
      <div class="select-wrapper">
        <select name="role" required>
          <option value="" selected disabled>Select Role</option>
          <option value="client">Client</option>
          <option value="technician">Technician</option>
          <option value="manager">Manager</option>
        </select>
        <!-- Custom Inline Dropdown Chevron SVG icon -->
        <svg class="select-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
    </div>

    <!-- Submit Trigger -->
    <button type="submit" class="submit-btn">Log in</button>

  </form>

  <!-- Footnote Signup Redirect Link -->
  <div class="footer-text">
    <span>Don't have an account? <a href="signup.php">Create Account</a></span>
  </div>

</div>

<!-- Password Mask Visibility Script -->
<script>
  document.getElementById('passwordToggleBtn').addEventListener('click', function () {
    const passwordField = document.getElementById('passwordField');
    const eyeSvg = document.getElementById('eyeSvg');
    const isPassword = passwordField.getAttribute('type') === 'password';
    
    passwordField.setAttribute('type', isPassword ? 'text' : 'password');
    
    // Smoothly switches the visual icon path shape between Open Eye and Slash Eye
    if (isPassword) {
      eyeSvg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
      eyeSvg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
  });
</script>

</body>
</html>
