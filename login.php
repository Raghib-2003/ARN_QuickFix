<?php
require_once "config.php";

$error = "";

// If already logged in, send to dashboard
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

    // Fetch user by email
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

      // Login success: store session
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["name"]    = $user["name"];
      $_SESSION["role"]    = $user["role"];

      // Redirect to correct dashboard
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
  <title>Login | Sonic Elevator Ltd.</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    body{
      background: linear-gradient(120deg, #e6f9fb, #ffffff);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .login-card{
      max-width:420px;
      width:100%;
      border-radius:18px;
      box-shadow:0 15px 40px rgba(0,0,0,.08);
    }
    .btn-sonic{
      background:#00C2CB;
      border:none;
      font-weight:700;
    }
    .btn-sonic:hover{ background:#05aeb6; }
  </style>
</head>
<body>

<div class="card login-card p-4">
  <div class="text-center mb-4">
    <h3 class="fw-bold">Sonic Elevator Ltd.</h3>
    <p class="text-muted mb-0">Login to your account</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["signup"]) && $_GET["signup"] === "success"): ?>
    <div class="alert alert-success">
      Signup successful! Please login.
    </div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <!-- Email -->
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <!-- Password -->
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <!-- Role -->
    <div class="mb-4">
      <label class="form-label">Login As</label>
      <select name="role" class="form-select" required>
        <option value="">Select Role</option>
        <option value="admin">Admin</option>
        <option value="technician">Technician</option>
        <option value="client">Client</option>
        <option value="manager">Management</option>
      </select>
    </div>

    <!-- Button -->
    <button type="submit" class="btn btn-sonic w-100 py-2">
      <i class="fa-solid fa-right-to-bracket me-2"></i>Login
    </button>
  </form>

  <div class="text-center mt-3">
    <small>Don’t have an account? <a href="signup.php">Sign Up</a></small>
  </div>
</div>

</body>
</html>