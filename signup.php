<?php
require_once "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role  = trim($_POST["role"] ?? "");
    $name  = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $pass  = $_POST["password"] ?? "";

    if ($role === "" || $name === "" || $email === "" || $pass === "") {
        $error = "All fields are required.";
    } else {

        $allowed_roles = ["client", "technician", "manager", "admin"];
        if (!in_array($role, $allowed_roles)) {
            $error = "Invalid role selected.";
        } else {

            // Check if email already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = "Email already exists. Please login.";
            } else {

                $hash = password_hash($pass, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)"
                );

                if ($stmt->execute([$name, $email, $hash, $role])) {
                    header("Location: login.php?signup=success");
                    exit;
                } else {
                    $error = "Signup failed. Try again.";
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign Up | Sonic Elevator Ltd.</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --brand:#00C2CB;
      --soft:#f5fbfc;
    }
    body{
      min-height:100vh;
      display:flex;
      align-items:center;
      background:linear-gradient(135deg,#f8fcff,var(--soft));
    }
    .card{
      border:0;
      border-radius:16px;
      box-shadow:0 15px 40px rgba(0,0,0,.1);
    }
    .brand-icon{
      width:55px;height:55px;
      border-radius:14px;
      background:rgba(0,194,203,.15);
      display:flex;
      align-items:center;
      justify-content:center;
      color:var(--brand);
      font-size:24px;
    }
    .form-control,.form-select{
      border-radius:12px;
      padding:12px;
    }
    .btn-brand{
      background:var(--brand);
      border-color:var(--brand);
      border-radius:999px;
      font-weight:600;
    }
    .btn-brand:hover{
      background:#00aab1;
      border-color:#00aab1;
    }
  </style>
</head>

<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">

      <div class="card p-4 p-lg-5">

        <!-- Header -->
        <div class="text-center mb-4">
          <div class="brand-icon mx-auto mb-3">
            <i class="fa-solid fa-elevator"></i>
          </div>
          <h4>Create Account</h4>
          <p class="text-muted mb-0">Sonic Elevator Ltd.</p>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <!-- Signup Form -->
        <form method="POST" action="signup.php">

          <!-- Role -->
          <div class="mb-3">
            <label class="form-label">Register As</label>
            <select class="form-select" name="role" required>
              <option value="" selected disabled>Select role</option>
              <option value="client">Client / Building Owner</option>
              <option value="technician">Technician</option>
              <option value="manager">Management</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <!-- Name -->
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" placeholder="Your name" required>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
          </div>

          <!-- Password -->
          <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" placeholder="Create password" required>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn btn-brand w-100 py-2">
            Sign Up <i class="fa-solid fa-user-plus ms-2"></i>
          </button>

        </form>

        <!-- Footer -->
        <div class="text-center mt-4">
          <small>Already have an account?
            <a href="login.php" class="text-decoration-none">Login</a>
          </small>
        </div>

      </div>

      <div class="text-center text-muted small mt-3">
        ©️ Sonic Elevator Ltd.
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>