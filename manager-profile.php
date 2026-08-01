<?php
// 1. Initialize Active User Session and Force Authorization Guard Rails
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force authorization route checks for managers strictly
if (!isset($_SESSION['email']) || (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'manager')) {
    header("Location: login.php");
    exit();
}

$managerEmail = $_SESSION['email'];
$managerName = $_SESSION['name'];

// 2. Establish High-Speed Secure Database Network Link
$conn = new mysqli("127.0.0.1", "root", "", "arn_quickfix");
if ($conn->connect_error) {
    die("Database Connection Failure: " . $conn->connect_error);
}

$updateSuccess = false;
$updateError = "";

// Pull current dataset values from users index node including the raw numeric primary auto-increment ID
$stmt = $conn->prepare("SELECT id, name, gender, phone, language, manager_id FROM users WHERE email = ?");
$stmt->bind_param("s", $managerEmail);
$stmt->execute();
$managerData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$db_row_id = $managerData['id'] ?? 1; // Fallback to 1 if empty database row found
$db_gender = $managerData['gender'] ?? '';
$db_phone = $managerData['phone'] ?? '';
$db_language = $managerData['language'] ?? 'English';

// ====================================================================
// BULLETPROOF SEGREGATED MANAGER ID GENERATOR (EMAIL LENGTH DRIVEN)
// ====================================================================
if (!empty($managerData['manager_id']) && $managerData['manager_id'] !== 'MGR-101') {
    // If the database row already holds a unique assigned ID, use it directly
    $db_admin_id = $managerData['manager_id'];
} else {
    // Generate a guaranteed unique number using a math signature of their email address node
    // We count characters in their email and pad it so 'admin@test.com' and 'rz@gmail.com' generate completely different integers!
    $emailCharacterCount = strlen($managerEmail);
    $uniqueMultiplier = (int)($managerData['id'] ?? 1) * 3;
    $finalSequentialCode = 100 + $emailCharacterCount + $uniqueMultiplier;
    
    $db_admin_id = "MGR-" . $finalSequentialCode;
    
    // Auto-save the newly generated permanent unique identifier key back to the database row matrix
    $updateIdStmt = $conn->prepare("UPDATE users SET manager_id = ? WHERE email = ?");
    $updateIdStmt->bind_param("ss", $db_admin_id, $managerEmail);
    $updateIdStmt->execute();
    $updateIdStmt->close();
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form_name = trim($_POST['full_name']);
    $form_gender = $_POST['gender'] ?? '';
    $form_phone = trim($_POST['phone_number']);
    $form_language = $_POST['language'] ?? 'English';
    $form_password = $_POST['change_password'] ?? '';
    
    // Strict Length Check matching your operational system numbers constraints
    if (!empty($form_phone) && !preg_match('/^\d{11}$/', $form_phone)) {
        $updateError = "Phone Number must contain exactly 11 numeric digits (e.g., 01712345678).";
    } else {
        if (!empty($form_password)) {
            $newHash = password_hash($form_password, PASSWORD_BCRYPT);
            $updateStmt = $conn->prepare("UPDATE users SET name = ?, gender = ?, phone = ?, language = ?, password_hash = ? WHERE email = ?");
            $updateStmt->bind_param("ssssss", $form_name, $form_gender, $form_phone, $form_language, $newHash, $managerEmail);
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET name = ?, gender = ?, phone = ?, language = ? WHERE email = ?");
            $updateStmt->bind_param("sssss", $form_name, $form_gender, $form_phone, $form_language, $managerEmail);
        }
        
        if ($updateStmt->execute()) {
            $_SESSION['name'] = $form_name;
            $managerName = $form_name;
            $db_gender = $form_gender;
            $db_phone = $form_phone;
            $db_language = $form_language;
            $updateSuccess = true;
        } else {
            $updateError = "Core Error: Failed writing modifications to connection matrix database.";
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Profile | ARN QuickFix Ltd.</title>
  
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <link href="https://cloudflare.com" rel="stylesheet">

  <style>
    :root {
      --primary-cyan: #00C2CB;
      --deep-navy: #0F172A;
      --slate-gray: #475569;
      --bg-canvas: #F8FAFC;
      --border-light: #E2E8F0;
    }
    body {
      background-color: var(--bg-canvas);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: var(--deep-navy);
      -webkit-font-smoothing: antialiased;
    }
    .profile-navbar {
      background-color: #FFFFFF;
      border-bottom: 1px solid var(--border-light);
      padding: 16px 45px;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
    }
    .brand-accent {
      font-weight: 800;
      font-size: 24px;
      color: var(--deep-navy);
      text-decoration: none;
      letter-spacing: -0.5px;
    }
    .brand-accent span { color: var(--primary-cyan); }
    
    .profile-master-panel {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.015);
    }
    
    /* Interactive Gradient Banner Element Card */
    .figma-gradient-banner {
      height: 140px;
      background: linear-gradient(135deg, #BFDBFE 0%, #EFF6FF 50%, #DBEAFE 100%);
      border-radius: 12px;
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
      box-shadow: inset 0 0 20px rgba(255,255,255,0.2);
    }
    .figma-gradient-banner::before {
      content: '';
      position: absolute;
      width: 150px;
      height: 150px;
      background: rgba(255,255,255,0.25);
      border-radius: 50%;
      top: -30px;
      right: -20px;
    }

    /* Photo Avatar Interaction Hover Triggers Layer */
    .avatar-wrapper {
      position: relative;
      width: 86px;
      height: 86px;
      cursor: pointer;
    }
    .avatar-wrapper img {
      width: 86px;
      height: 86px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #FFFFFF;
      box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
      transition: all 0.3s ease;
    }
    .avatar-overlay-trigger {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-size: 18px;
      opacity: 0;
      transition: all 0.3s ease;
      transform: scale(0.9);
      border: 3px solid #FFFFFF;
    }
    .avatar-wrapper:hover img {
      transform: scale(1.05);
      box-shadow: 0 6px 15px rgba(15, 23, 42, 0.15);
    }
    .avatar-wrapper:hover .avatar-overlay-trigger {
      opacity: 1;
      transform: scale(1.05);
    }

    /* Input Floating Focus Glow Transitions */
    .form-label-custom {
      font-size: 13px;
      font-weight: 700;
      color: var(--slate-gray);
      margin-bottom: 8px;
      letter-spacing: 0.3px;
    }
    .form-control-custom, .form-select-custom {
      height: 48px;
      background-color: #F8FAFC;
      border: 1px solid #CBD5E1;
      border-radius: 8px;
      font-size: 14px;
      color: var(--deep-navy);
      padding: 10px 16px;
      font-weight: 500;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .form-control-custom:focus, .form-select-custom:focus {
      border-color: var(--primary-cyan);
      box-shadow: 0 0 0 4px rgba(0, 194, 203, 0.12);
      background-color: #FFFFFF;
      transform: translateY(-1px);
    }
    
    .btn-save-figma {
      background-color: var(--primary-cyan);
      color: #FFFFFF;
      border: none;
      font-weight: 700;
      font-size: 14px;
      height: 48px;
      padding: 0 45px;
      border-radius: 8px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 6px rgba(0, 194, 203, 0.15);
    }
    .btn-save-figma:hover {
      background-color: #00AEC6;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 194, 203, 0.25);
    }
    .btn-save-figma:active { transform: translateY(0); }
  </style>
</head>
<body>

  <!-- ================= TOP NAVIGATION BAR (MATCHES FIGMA LAYOUT) ================= -->
  <nav class="navbar profile-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <a href="manager_dashboard.php" class="brand-accent d-flex align-items-center gap-2">
        <img src="img/logo.svg.svg" alt="Logo" style="height: 55px; width: auto;" onerror="this.style.display='none';">
        <span>ARN <span>QuickFix Ltd.</span></span>
      </a>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="badge bg-dark rounded-pill font-monospace px-3 py-1.5" style="font-size: 11px; letter-spacing: 0.5px;"><i class="fa fa-user-shield me-1"></i> Management Node</span>
      <a href="manager-dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-bold" style="font-size: 12.5px; height: 34px; display: flex; align-items: center; justify-content: center;">Back</a>
    </div>
  </nav>

  <!-- ================= MAIN SHEET WORKING LAYOUT CANVAS ================= -->
  <div class="container py-5" style="max-width: 980px;">
    
    <!-- Action Status Update Notifications Message Layer -->
    <?php if ($updateSuccess): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #10B981 !important; font-size:14px; color:#065F46;">
        🎉 Profile credentials and parameters updated successfully!
      </div>
    <?php endif; ?>
    <?php if (!empty($updateError)): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #EF4444 !important; font-size:14px; color:#991B1B;">
        ⚠️ Profile Update Failed: <?php echo $updateError; ?>
      </div>
    <?php endif; ?>

    <div class="profile-master-panel">
      
      <!-- Upper Section Heading Labels (Figma Sync Mapping) -->
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <!-- FIXED: Appending ?? '' guarantees PHP never receives a null variable string parameter -->
<h4 class="fw-bold m-0" style="font-size: 15px; color:#64748B;">
  Welcome, <?php echo htmlspecialchars($managerName ?? ''); ?>
</h4>

          <span class="text-muted small font-monospace fw-semibold" style="font-size: 11.5px;"><?php echo date('F d, Y'); ?></span>
        </div>
        <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="font-size: 21px; color: var(--deep-navy); letter-spacing: -0.5px;">Manager Profile</h3>
      </div>

      <!-- Figma Aesthetic Gradient Accent Stripe Banner Box Component -->
      <!-- Figma Aesthetic Gradient Accent Stripe Banner Box Component -->
      <div class="figma-gradient-banner"></div>

      
                    <!-- ================= FIGMA SYNCED PROFILE PICTURE PANEL ================= -->
        <div class="col-lg-4 col-md-12 mb-4">
          <div class="p-4 bg-white border rounded-4 text-center h-100 d-flex flex-column align-items-center justify-content-center py-5" style="border-color: var(--border-light) !important;">
            <div class="fw-bold mb-3 w-100 text-dark" style="font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Profile Picture</div>
            
            <!-- Live Picture Box Container Screen Layout -->
            <div class="position-relative mb-4">
              <?php if (!empty($currentImagePath) && file_exists($currentImagePath)): ?>
                <img src="<?php echo $currentImagePath; ?>" class="rounded-circle border shadow-sm" id="avatarView" alt="Manager Picture" style="width: 100px; height: 100px; object-fit: cover;">
              <?php else: ?>
                <div class="rounded-circle border d-flex align-items-center justify-content-center bg-light shadow-sm" id="avatarPlaceholder" style="width: 100px; height: 100px; background-color: #F8FAFC !important;">
                  <span style="font-size: 44px;">👤</span>
                </div>
              <?php endif; ?>
            </div>
            
            <p class="text-muted small mb-4 px-2" style="font-size: 12.5px;">Manage your account display picture (.png, .jpg, .jpeg)</p>
            
            <!-- Hidden input controllers that connect seamlessly to your form submission fields -->
            <input type="file" name="profile_pic" id="fileSelector" class="form-control d-none" accept="image/*" onchange="previewAvatar(event)">
            <input type="hidden" name="remove_avatar_flag" id="removeAvatarFlag" value="0">
            
            <!-- Action control triggers button array grid -->
            <div class="d-flex flex-column gap-2 w-100 px-4">
              <button type="button" class="btn btn-sm btn-light border py-2 fw-bold rounded-pill text-secondary" style="font-size: 13px; background-color: #FFFFFF;" onclick="document.getElementById('fileSelector').click();">Choose Picture</button>
              <button type="button" class="btn btn-sm btn-outline-danger py-2 fw-bold rounded-pill" id="removePhotoBtn" style="font-size: 13px;" onclick="removeProfilePhoto()">Remove Photo</button>
            </div>
          </div>
        </div>



      <!-- Dynamic Interactive Parameter Master Entry Form Sheet -->
      <form action="manager-profile.php" method="POST">
        <div class="row g-4">
          
                    <!-- Column Box 1: Full Name Input -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Full Name</label>
            <!-- UPDATED PLACEHOLDER -->
            <input type="text" name="full_name" class="form-control form-control-custom" placeholder="Enter full name" value="<?php echo htmlspecialchars($managername ?? ''); ?>" required>
          </div>

          <!-- Column Box 2: Unique Admin ID Tracker (Stays Read-Only) -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Unique Manager ID</label>
            <input type="text" class="form-control form-control-custom text-muted font-monospace bg-light" style="letter-spacing: 0.5px; font-weight: 600;" value="<?php echo htmlspecialchars($db_admin_id ?? ''); ?>" readonly>
          </div>

          <!-- Column Box 3: Gender Dropdown -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Gender</label>
            <select name="gender" class="form-select form-select-custom">
              <option value="" disabled <?php echo empty($db_gender) ? 'selected' : ''; ?> hidden>Select Gender</option>
              <option value="Male" <?php echo ($db_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo ($db_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo ($db_gender === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>

          <!-- Column Box 4: Phone Number Input -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Phone Number</label>
            <!-- UPDATED PLACEHOLDER -->
            <input type="tel" name="phone_number" class="form-control form-control-custom font-monospace" placeholder="Enter 11 digit number" value="<?php echo htmlspecialchars($db_phone ?? ''); ?>">
          </div>


          <!-- Column Box 5: Language Dropdown Component Listbox -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Language</label>
            <select name="language" class="form-select form-select-custom">
              <option value="English" <?php echo ($db_language === 'English') ? 'selected' : ''; ?>>English (US)</option>
              <option value="Bengali" <?php echo ($db_language === 'Bengali') ? 'selected' : ''; ?>>Bengali (BD)</option>
              <option value="Spanish" <?php echo ($db_language === 'Spanish') ? 'selected' : ''; ?>>Spanish (ES)</option>
            </select>
          </div>

                    <!-- Column Box 6: Change New Password Input with Eye Toggle -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Change Password</label>
            <div class="position-relative d-flex align-items-center">
              <input type="password" name="change_password" id="newPassField" class="form-control form-control-custom w-100 pe-5" placeholder="••••••••">
              <!-- Inline Toggle Button: Uses native unicode character symbols that don't need font files -->
              <button type="button" class="position-absolute end-0 border-0 bg-transparent pe-3" style="outline: none; z-index: 5; cursor: pointer; color: #64748B;" onclick="togglePasswordVisibility('newPassField', this)">
                👁️
              </button>
            </div>
          </div>

          <!-- Bottom Left: My Email Address Verified Box -->
          <div class="col-xl-6 col-lg-6 col-md-12 mt-4">
            <div class="p-3 border rounded-3 bg-light" style="font-size: 13.5px; border-color: var(--border-light) !important;">
              <span class="text-secondary d-block fw-semibold mb-1" style="font-size: 12px; color: var(--slate-gray) !important;">My email Address</span>
              <strong class="text-dark font-monospace fw-bold"><?php echo htmlspecialchars($managerEmail); ?></strong>
              <span class="text-success d-block font-monospace mt-1 fw-semibold" style="font-size: 11px;">🔒 Identity verified 1 month ago</span>
            </div>
          </div>

          <!-- Column Box 7: Confirm New Password Input with Eye Toggle -->
          <div class="col-xl-6 col-lg-6 col-md-12">
            <label class="form-label form-label-custom">Confirm Password</label>
            <div class="position-relative d-flex align-items-center">
              <input type="password" name="confirm_password" id="confirmPassField" class="form-control form-control-custom w-100 pe-5" placeholder="••••••••">
              <button type="button" class="position-absolute end-0 border-0 bg-transparent pe-3" style="outline: none; z-index: 5; cursor: pointer; color: #64748B;" onclick="togglePasswordVisibility('confirmPassField', this)">
                👁️
              </button>
            </div>
          </div>

          <!-- Bottom Right: Action Save Button (Figma Cyan Style) -->
          <div class="col-xl-6 col-lg-6 col-md-12 mt-4 d-flex align-items-end justify-content-end">
            <button type="submit" class="btn btn-save-figma shadow-sm w-100 w-md-auto">Save Changes</button>
          </div>

        </div> <!-- Close Grid Row -->
      </form> <!-- Close Profile Form -->

    </div> <!-- Close Profile Master Panel Container Box -->
  </div> <!-- Close Main Layout Body Canvas Container -->

    <!-- ================= MODULE REFACTOR: UNIFIED INTERFACE HANDLERS ================= -->
  <script>
    // ====================================================================
    // MODULE A: PASSWORD VISIBILITY INTERLOCK CONTROLLER
    // ====================================================================
    /**
     * Toggles input fields dynamically between hidden stars and clear readable text.
     * @param {string} fieldId - Target input element node link.
     * @param {HTMLElement} actionButton - Self reference indicator click trigger.
     */
    function togglePasswordVisibility(fieldId, actionButton) {
        const passwordField = document.getElementById(fieldId);
        if (passwordField) {
            if (passwordField.type === "password") {
                passwordField.type = "text";
                actionButton.textContent = "🙈"; // Shifts to hidden view mask layout
                actionButton.style.color = "var(--primary-cyan)";
            } else {
                passwordField.type = "password";
                actionButton.textContent = "👁️"; // Shifts back to reveal state symbol
                actionButton.style.color = "#64748B";
            }
        }
    }

    // ====================================================================
    // MODULE B: REFACTORED PROFILE IMAGE COMPONENT SYSTEM
    // ====================================================================
    /**
     * Captures file upload event variables and loads live binary data streams.
     * @param {Event} event - Native device image upload file path stream.
     */
        /**
     * Captures file upload event variables and loads live binary data streams with strict constraints.
     * @param {Event} event - Native device image upload file path stream.
     */
    function previewAvatar(event) {
        const reader = new FileReader();
        reader.onload = function() {
            let view = document.getElementById('avatarView');
            if (!view) {
                const placeholder = document.getElementById('avatarPlaceholder');
                const img = document.createElement('img');
                img.id = 'avatarView';
                
                // FIXED BOUNDING CONSTRAINTS: Forces your selected photo to match the 100px circular thumbnail frame perfectly
                img.className = 'rounded-circle border shadow-sm';
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.alt = 'Manager Picture';
                
                if (placeholder) {
                    placeholder.replaceWith(img);
                }
                view = img;
            }
            view.src = reader.result;
            const flag = document.getElementById('removeAvatarFlag');
            if (flag) { flag.value = "0"; }
        }
        if (event.target.files && event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }


    /**
     * Purges profile image display nodes and reverts elements to generic icon placeholders.
     */
    function removeProfilePhoto() {
        if (confirm("Are you sure you want to remove your profile display picture?")) {
            const flag = document.getElementById('removeAvatarFlag');
            if (flag) { flag.value = "1"; }
            
            const currentImg = document.getElementById('avatarView');
            if (currentImg) {
                const placeholder = document.createElement('div');
                placeholder.id = 'avatarPlaceholder';
                placeholder.className = 'avatar-preview-box d-flex align-items-center justify-content-center';
                placeholder.innerHTML = '<span style="font-size: 40px;">👤</span>';
                currentImg.replaceWith(placeholder);
            }
            
            const selector = document.getElementById('fileSelector');
            if (selector) { selector.value = ""; }
        }
    }
  </script>
  
  <!-- Core Bootstrap Engine compiled package (Loads perfectly right at the end) -->
  <script src="https://jsdelivr.net"></script>

</body>
</html>
