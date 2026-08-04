<?php
// Initialize system session access wrappers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ====================================================================
// CRITICAL FIX: EXPLICITLY RESET CACHED SESSION VALUES ON INITIAL LAND
// ====================================================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    session_unset();
    $_SESSION = array();
}

// Establish database sync connection
$conn = @new mysqli("localhost", "root", "", "arn_quickfix");
if ($conn->connect_error) {
    die("Database connectivity node failed to sync: " . $conn->connect_error);
}

// Handle Authenticated Login Verification Form Data Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // AUTOMATED: Pulls the precise role token saved during signup without relying on user guesswork
    $stmt = $conn->prepare("SELECT name, password_hash, role FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verifies the securely hashed credentials matching your table layout patterns
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $user['name'];

                // FIXED INTERLOCK: Forces lower-case comparison to prevent string match typos
                $userRole = strtolower(trim($user['role'] ?? 'client'));
                $_SESSION['role'] = $userRole;
                
                $stmt->close();
                $conn->close();
                
                // ====================================================================
                // FIXED DYNAMIC ROUTER JUNCTION (ROUTING DRIVEN BY DATABASE ACCOUNT ROLES)
                // ====================================================================
                switch ($userRole) {
                    case 'manager':
                        header("Location: manager-dashboard.php");
                        break;
                    case 'tech_ac':
                    case 'tech_generator':
                    case 'tech_elevator':
                        header("Location: technician_dashboard.php");
                        break;
                    case 'client':
                    default:
                        header("Location: client-dashboard.php");
                        break;
                }
                exit();
                
            } else {
                echo "<script>alert('Invalid account password credentials! Please try again.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('No registered profile matches this email destination node!'); window.history.back();</script>";
            exit();
        }
        $stmt->close();
    } else {
        echo "<script>alert('System Core Error: Ensure your central users database table exists with correct column parameters!'); window.history.back();</script>";
        exit();
    }
}
?>
<!-- ================= THE FRONTEND USER INTERFACE HTML LAYOUT ================= -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | ARN QuickFix Ltd.</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cloudflare.com" rel="stylesheet">

  <style>
    body { 
      background-color: #F8FAFC; 
      font-family: system-ui, -apple-system, sans-serif; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      min-height: 100vh; 
      margin: 0; 
    }
    
    /* ENLARGED DIMENSIONS: Extended from 400px to 540px to enhance look balance layout metrics */
    .login-card { 
      background: #FFFFFF; 
      max-width: 540px; 
      width: 100%; 
      padding: 45px 50px; 
      border-radius: 16px; 
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); 
      position: relative;
    }
    
    /* PIXEL-PERFECT NAVIGATION BACK ARROW BUTTON LINK */
    .back-nav-arrow {
      position: absolute;
      top: 48px;
      left: 50px;
      color: #64748B;
      font-size: 18px;
      transition: color 0.2s, transform 0.2s;
      text-decoration: none;
    }
    .back-nav-arrow:hover {
      color: #0F172A;
      transform: translateX(-3px);
    }
    
    .brand-title { 
      color: #00C2CB; 
      font-weight: 800; 
      font-size: 26px; 
      margin-bottom: 4px; 
      text-align: center;
    }
    .card-subtitle {
      color: #94A3B8;
      font-size: 14px;
      margin-bottom: 35px;
      text-align: center;
    }
    
    .form-group { 
      text-align: left; 
      margin-bottom: 24px; 
    }
    .form-group label { 
      display: block; 
      font-weight: 600; 
      color: #475569; 
      font-size: 13px; 
      margin-bottom: 8px; 
      letter-spacing: 0.2px;
    }
    
    .input-position-wrapper {
      position: relative;
      width: 100%;
    }
    .form-control-custom { 
      width: 100%; 
      height: 44px; 
      background: #F8FAFC; 
      border: 1px solid #CBD5E1; 
      border-radius: 8px; 
      padding: 0 14px; 
      font-size: 14px; 
      outline: none; 
      transition: border-color 0.2s;
    }
    .form-control-custom:focus {
      border-color: #00C2CB;
      background: #FFFFFF;
    }
    
    /* INTERACTIVE INLINE PASSWORD VISIBILITY TOGGLE ICON EYE BUTTON */
    .password-toggle-eye {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #94A3B8;
      cursor: pointer;
      font-size: 15px;
      transition: color 0.2s;
    }
    .password-toggle-eye:hover {
      color: #475569;
    }
    
    .btn-login { 
      width: 100%; 
      height: 46px; 
      background-color: #00C2CB; 
      color: #FFFFFF; 
      font-weight: 700; 
      border: none; 
      border-radius: 8px; 
      margin-top: 10px; 
      font-size: 15px;
      transition: background 0.2s, transform 0.1s; 
    }
    .btn-login:hover { 
      background-color: #00A3AB; 
    }
    .btn-login:active {
      transform: scale(0.99);
    }
    
    /* HIGHLIGHTED DYNAMIC FOOTER TEXT LINK COMPILER */
    .footer-register-prompt {
      margin-top: 30px;
      font-size: 13.5px;
      color: #64748B;
      text-align: center;
    }
    .highlighted-action-link {
      color: #00C2CB;
      font-weight: 700;
      text-decoration: none;
      padding: 2px 6px;
      border-radius: 4px;
      background-color: transparent;
      transition: all 0.25s ease-in-out;
      display: inline-block;
    }
    /* Dynamic hover animation box highlights the target anchor link beautifully */
    .highlighted-action-link:hover {
      color: #FFFFFF !important;
      background-color: #00C2CB;
      box-shadow: 0 4px 12px rgba(0, 194, 203, 0.25);
    }
  </style>
</head>
<body>

    <div class="login-card">
    
    <!-- ================= AUTHENTIC BLACK SVG BACK NAV LINK BUTTON ================= -->
    <a href="index.php" class="back-btn" title="Return to Homepage" style="position: absolute; top: 48px; left: 50px; color: #000000 !important; transition: opacity 0.2s, transform 0.2s; text-decoration: none; display: inline-block;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
    </a>

    <div class="brand-title">ARN QuickFix Ltd.</div>
    <div class="card-subtitle">Log in to your account</div>


    <form action="login.php" method="POST">
      <div class="form-group">
        <label>Email</label>
        <div class="input-position-wrapper">
          <input type="email" name="email" class="form-control-custom" placeholder="example@something.com" required>
        </div>
      </div>

            <div class="form-group">
        <label>Password</label>
        <div class="input-position-wrapper">
          <!-- Password target text parameter entry field box -->
          <input type="password" id="passwordField" name="password" class="form-control-custom" placeholder="Enter your password" required>
          
          <!-- Integrated Custom SVG Toggle Button Link -->
          <button type="button" id="passwordToggleBtn" class="svg-toggle-wrapper-btn" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <svg id="eyeSvg" xmlns="http://w3.org" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: stroke 0.2s;">
              <!-- Default Open Eye Shape Vector Path Loaded Initially -->
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </button>
        </div>
      </div>


      <button type="submit" class="btn-login">Log In</button>
      
      <!-- Footer Prompt text block containing the new highlighted action layout styles link anchor -->
      <div class="footer-register-prompt">
        Don't have an account? <a href="signup.php" class="highlighted-action-link">Create Account</a>
      </div>
    </form>
  </div>

  <!-- ================= CLIENT SIDE VISIBILITY JAVASCRIPT LOGIC TRUCKS ================= -->
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
