<?php
// 1. Force explicit session initialization parameters
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// BULLETPROOF ALIGNMENT: Unifies casing options so your queries never search with empty variables
if (isset($_SESSION['Email'])) { $_SESSION['email'] = $_SESSION['Email']; }
if (isset($_SESSION['Name'])) { $_SESSION['name'] = $_SESSION['Name']; }

if (!isset($_SESSION['email']) || !isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

$clientEmail = $_SESSION['email'];
$clientName = $_SESSION['name'];


// 2. High-Speed Local Database Integration Link
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "arn_quickfix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connectivity Failure Trace: " . $conn->connect_error);
}

// ====================================================================
// SEPARATED NOTIFICATION CLEAR ENGINES (FIXED ISOLATION)
// ====================================================================
// 1. REPAIR NOTIFICATIONS ONLY: Wipes service requests dispatches/completions alerts
if (isset($_GET['clear_client_alerts']) && $_GET['clear_client_alerts'] == '1') {
    $conn->query("UPDATE service_requests SET is_read = 1 WHERE client_email = '$clientEmail'");
    header("Location: client-dashboard.php");
    exit();
}

// 2. MAINTENANCE OVERVIEW ONLY: Wipes preventative inspection calendar alerts
if (isset($_GET['clear_maint_alerts']) && $_GET['clear_maint_alerts'] == '1') {
    $conn->query("UPDATE maintenance_schedules SET is_read = 1 WHERE client_email = '$clientEmail'");
    header("Location: client-dashboard.php");
    exit();
}




// 3. Form Submission Handling Controller Logic Blocks
$toastTriggerMsg = "";

// 4. Form Submission Handling Rule Blocks (STICKY MEMORY INITIALIZATION)
$toastTriggerMsg = ""; 

// We initialize these to empty strings so they don't throw warnings on the first page load
$sticky_brand = "";
$sticky_id = "";
$sticky_phone = "";
$sticky_location = "";
$sticky_type = "";
$sticky_priority = "";
$sticky_payment = "";

// --------------------------------------------------------------------
// FORM PROCESSING: HANDLE CLIENT ESCALATION COMPLAINT SUBMISSION
// --------------------------------------------------------------------
$actionMessage = "";
$actionError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_type'])) {
    if ($_POST['action_type'] === 'submit_complaint_escalation') {
        $asset_id = strtoupper(trim($_POST['asset_id'] ?? ''));
        $asset_type = $_POST['asset_type'] ?? '';
        $problem_category = $_POST['problem_category'] ?? '';
        $complaint_notes = trim($_POST['complaint_notes'] ?? '');

        if (!empty($asset_id) && !empty($asset_type) && !empty($problem_category) && !empty($complaint_notes)) {
            
            // SECURITY CHECK: Verify this specific asset code combination actually matches a ticket logged by this client
            // UPDATED SECURITY CHECK: Verifies the Asset ID and Type belong to this user account (removes category constraint for flexible complaints)
$verifyStmt = $conn->prepare("SELECT id FROM service_requests WHERE client_email = ? AND asset_id = ? AND asset_type = ? LIMIT 1");
$verifyStmt->bind_param("sss", $clientEmail, $asset_id, $asset_type);

            $verifyStmt->execute();
            $ticketResult = $verifyStmt->get_result()->fetch_assoc();
            $verifyStmt->close();

            if ($ticketResult) {
                $targetTicketId = $ticketResult['id'];
                
                // Update the matching row's parameters with the complaint text and shift its state to highlight onto manager queues
                $updateStmt = $conn->prepare("UPDATE service_requests SET status = 'complaint_raised', complaint_text = ? WHERE id = ?");
                $updateStmt->bind_param("si", $complaint_notes, $targetTicketId);
                
                if ($updateStmt->execute()) {
                    $_SESSION['alert_success'] = "Success! Complaint escalated to Manager.";
                } else {
                    $_SESSION['alert_error'] = "Database Error: Could not save complaint text notes.";
                }
                $updateStmt->close();
            } else {
                $_SESSION['alert_error'] = "Data Error: No ticket records found matching Asset ID '{$asset_id}' with category '{$problem_category}' under your account.";
            }
        } else {
            $_SESSION['alert_error'] = "Validation Mismatch: Please fill in all complaint details completely.";
        }
        
        // Post-Redirect-Get Interlock to kill browser form resubmission memory caches on refresh
        header("Location: client-dashboard.php");
        exit();
    }
}

// Extract temporary notifications arrays safely out of session memory structures
if (isset($_SESSION['alert_success'])) { $actionMessage = $_SESSION['alert_success']; unset($_SESSION['alert_success']); }
if (isset($_SESSION['alert_error'])) { $actionError = $_SESSION['alert_error']; unset($_SESSION['alert_error']); }

// Fetch the list of historical asset components assigned to this user to populate form fields dynamically
$distinctAssets = $conn->query("SELECT DISTINCT asset_id, asset_type, problem_category FROM service_requests WHERE client_email = '$clientEmail' ORDER BY id DESC");


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type'])) 
    if ($_POST['action_type'] === 'service_request') {
        $assetType = $_POST['asset_type'] ?? '';
        $assetBrand = $_POST['asset_brand'] ?? '';
        $assetId = trim(strtoupper($_POST['asset_id'] ?? ''));
        $problemCategory = $_POST['problem_category'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $location = $_POST['location'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? '';
        
        // Save these into sticky wrappers so the HTML inputs can recall them on failure
        $sticky_brand = $assetBrand;
        $sticky_id = $assetId;
        $sticky_phone = $phone;
        $sticky_location = $location;
        $sticky_type = $assetType;
        $sticky_priority = $priority;
        $sticky_payment = $paymentMethod;
        
        // ... (your existing validation checks and database INSERT logic remain exactly underneath here) ...

        // --- BACKEND VERIFICATION SHIELD 1: Strict Prefix Structural Mismatch Validation ---
        if ($assetType === 'Elevator' && !str_starts_with($assetId, 'ELV')) {
            $toastTriggerMsg = "<i class='fa fa-exclamation-triangle me-1'></i> Validation Failure: Elevator Asset ID must start with 'ELV' (e.g., ELV-101)!";
        } elseif ($assetType === 'AC' && !str_starts_with($assetId, 'AC')) {
            $toastTriggerMsg = "<i class='fa fa-exclamation-triangle me-1'></i> Validation Failure: AC Asset ID must start with 'AC' (e.g., AC-202)!";
        } elseif ($assetType === 'Generator' && !str_starts_with($assetId, 'GEN')) {
            $toastTriggerMsg = "<i class='fa fa-exclamation-triangle me-1'></i> Validation Failure: Generator Asset ID must start with 'GEN' (e.g., GEN-303)!";
        }
        // --- BACKEND VERIFICATION SHIELD 2: Strict 11-Digit Number Sequence Validation ---
        elseif (!preg_match('/^\d{11}$/', $phone)) {
            $toastTriggerMsg = "<i class='fa fa-phone me-1'></i> Validation Failure: Contact Phone Number must contain exactly 11 numeric digits (e.g., 01712345678)!";
        }
        else {
            // --- BACKEND VERIFICATION SHIELD 3: Database Database Uniqueness Duplication Checks ---
            $checkAsset = $conn->prepare("SELECT id FROM service_requests WHERE asset_id = ?");
            $checkAsset->bind_param("s", $assetId);
            $checkAsset->execute();
            $checkAsset->store_result();
            $assetDuplicatesCount = $checkAsset->num_rows;
            $checkAsset->close();
            
            $checkPhone = $conn->prepare("SELECT id FROM service_requests WHERE phone = ?");
            $checkPhone->bind_param("s", $phone);
            $checkPhone->execute();
            $checkPhone->store_result();
            $phoneDuplicatesCount = $checkPhone->num_rows;
            $checkPhone->close();
            
            if ($assetDuplicatesCount > 0) {
                $toastTriggerMsg = "<i class='fa fa-database me-1'></i> Duplicate Error: This Asset ID is already linked to an active request ticket!";
            } elseif ($phoneDuplicatesCount > 0) {
                $toastTriggerMsg = "<i class='fa fa-phone-slash me-1'></i> Duplicate Error: This Phone Number is already linked to an active ticket row!";
            } else {
                // If every single verification rule passes successfully, commit the record data cleanly
                $insertStmt = $conn->prepare("INSERT INTO service_requests (client_email, asset_type, asset_brand, asset_id, problem_category, priority, phone, location, payment_method, status, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL)");
                $insertStmt->bind_param("sssssssss", $clientEmail, $assetType, $assetBrand, $assetId, $problemCategory, $priority, $phone, $location, $paymentMethod);
                
                if ($insertStmt->execute()) {
                    $_SESSION['flash_request_success'] = true;
                    header("Location: client-dashboard.php");
                    exit();
                } else {
                    $toastTriggerMsg = "<i class='fa fa-exclamation-circle me-1'></i> Execution Error: Failed writing request data.";
                }
                $insertStmt->close();
            }
        }
    }
    // ... your remaining complaint code handles rest underneath ...


// 4. Process Backend Metric Summaries (Aggregated Queries)
$totalRequestsCount = 0;
$openRequestsCount = 0;
$overdueRequestsCount = 0;
$notifications = [];

$countQuery = $conn->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN status IN ('pending', 'processing', 'submitted') THEN 1 END) as open FROM service_requests WHERE client_email = ?");
if ($countQuery) {
    $countQuery->bind_param("s", $clientEmail);
    $countQuery->execute();
    $countResult = $countQuery->get_result()->fetch_assoc();
    $totalRequestsCount = $countResult['total'] ?? 0;
    $openRequestsCount = $countResult['open'] ?? 0;
    $countQuery->close();
}

// Pull dynamic manager notification updates records
$notifQuery = $conn->prepare("SELECT message, created_at FROM manager_notifications WHERE client_email = ? ORDER BY id DESC LIMIT 5");
if ($notifQuery) {
    $notifQuery->bind_param("s", $clientEmail);
    $notifQuery->execute();
    $notifResult = $notifQuery->get_result();
    while ($row = $notifResult->fetch_assoc()) {
        $notifications[] = $row;
    }
    $notifQuery->close();
}

// Check maintenance schedules to calculate overdue count status
$maintQuery = $conn->prepare("SELECT COUNT(*) as overdue FROM maintenance_schedules WHERE client_email = ? AND status = 'Overdue'");
if ($maintQuery) {
    $maintQuery->bind_param("s", $clientEmail);
    $maintQuery->execute();
    $maintResult = $maintQuery->get_result()->fetch_assoc();
    $overdueRequestsCount = $maintResult['overdue'] ?? 0;
    $maintQuery->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Dashboard | ARN QuickFix Ltd.</title>
  
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <link href="https://cloudflare.com" rel="stylesheet">
  
  <style>
    :root {
      --primary-cyan: #00C2CB;
      --bg-slate: #F4F7F9;
      --text-dark: #333333;
      --border-gray: #E2E8F0;
    }
    body {
      background-color: var(--bg-slate);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: var(--text-dark);
    }
    .dashboard-navbar {
      background-color: #FFFFFF;
      border-bottom: 1px solid var(--border-gray);
      padding: 15px 40px;
    }
    .brand-accent {
      color: var(--primary-cyan);
      font-weight: 700;
      font-size: 24px;
      text-decoration: none;
    }
    .metric-card {
      background: #FFFFFF;
      border: 1px solid var(--border-gray);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }
    .metric-title {
      font-size: 11px;
      color: #64748B;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: 8px;
      letter-spacing: 0.5px;
    }
    .metric-value {
      font-size: 26px;
      font-weight: 700;
      color: var(--text-dark);
    }
    .dashboard-panel {
      background: #FFFFFF;
      border: 1px solid var(--border-gray);
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 24px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }
    .panel-heading {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--text-dark);
    }
    .form-control, .form-select {
      height: 46px;
      background-color: #F8FAFC;
      border: 1px solid #CBD5E1;
      border-radius: 8px;
      font-size: 14px;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-cyan);
      box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.15);
      background-color: #FFFFFF;
    }
    .btn-submit-cyan {
      background-color: var(--primary-cyan);
      color: #FFFFFF;
      border: none;
      height: 46px;
      border-radius: 8px;
      font-weight: 700;
      width: 100%;
    }
    .btn-submit-cyan:hover { background-color: #00AEC6; color:#FFF; }
    .btn-complaint-red {
      background-color: #EF4444;
      color: #FFFFFF;
      border: none;
      height: 44px;
      border-radius: 8px;
      font-weight: 700;
      width: 100%;
    }
    .btn-complaint-red:hover { background-color: #DC2626; color:#FFF; }
    .btn-outline-custom {
      border: 1px solid #CBD5E1;
      border-radius: 4px;
      padding: 5px 15px;
      font-size: 12px;
      color: #64748B;
      font-weight: 600;
      text-decoration: none;
      background-color: #FFFFFF;
    }
    .btn-outline-custom:hover { border-color: var(--primary-cyan); color: var(--primary-cyan); }
  </style>
</head>
<body>
    <!-- ================= TOP NAVIGATION BAR ================= -->
  <nav class="navbar dashboard-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <a href="client-dashboard.php" class="brand-accent d-flex align-items-center gap-2">
        <img src="img/logo.svg.svg" alt="Logo" style="height: 50px; width: auto;" onerror="this.style.display='none';">
        <span>ARN QuickFix Ltd.</span>
      </a>
      <span class="fs-5 fw-bold text-dark border-start border-start-4 ps-3" style="border-color: var(--border-gray) !important;">
        Client Dashboard
      </span>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="nav-user-label text-secondary small fw-medium">Client: <strong><?php echo $clientName; ?></strong></span>
      <a href="client-profile.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-bold" style="font-size:12px;">Profile</a>
      <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold" onclick="return confirm('Are you sure you want to log out?');" style="font-size:12px;">Logout</a>
    </div>
  </nav>

  <!-- ================= MAIN LAYOUT WRAPPER GRID CONTAINER ================= -->
  <div class="container py-4">

    <!-- Interactive Action Status Flash Banners -->
    <?php if (isset($_SESSION['flash_request_success'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #10B981 !important; font-size:13.5px; color:#065F46;">
        🎉 Service Request submitted successfully! It has been dispatched to management.
      </div>
      <?php unset($_SESSION['flash_request_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_complaint_success'])): ?>
      <div class="alert alert-warning border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #F59E0B !important; font-size:13.5px; color:#92400E;">
        ⚠️ Complaint escalation ticket successfully recorded onto the supervisor queue.
      </div>
      <?php unset($_SESSION['flash_complaint_success']); ?>
    <?php endif; ?>
        <?php if (isset($toastTriggerMsg) && !empty($toastTriggerMsg)): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #EF4444 !important; font-size:13.5px; color:#991B1B;">
        ⚠️ System Alert: <?php echo $toastTriggerMsg; ?>
      </div>
    <?php endif; ?>


    <!-- Counters Summary Metric Grid Rows -->
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="metric-card"><div class="metric-title">Total Requests</div><div class="metric-value"><?php echo $totalRequestsCount; ?></div></div></div>
      <div class="col-md-4"><div class="metric-card"><div class="metric-title">Open Requests</div><div class="metric-value"><?php echo $openRequestsCount; ?></div></div></div>
      <div class="col-md-4"><div class="metric-card"><div class="metric-title">Overdue Maintenance</div><div class="metric-value text-danger"><?php echo $overdueRequestsCount; ?></div></div></div>
    </div>

    <!-- Main Split Columns Setup Layout Grid -->
    <div class="row g-4">
      
      <!-- Main Content Form / Table Dynamic Split Grid Setup Row -->
    <div class="row g-4">
            <!-- LEFT HAND PANEL: Creation Matrix Form Panel Block -->
      <div class="col-lg-5">
        <div class="dashboard-panel">
          <div class="panel-heading">Create Service Request</div>
          
          <!-- FIXED ACTION HOOK: Directly binds validator event arrays -->
          <form action="client-dashboard.php" method="POST" onsubmit="return validateFormLayout(event)">
            <!-- CRITICAL HIDDEN FIX: Forces the action type identifier parameter check to true -->
            <input type="hidden" name="action_type" value="service_request">
                       <div class="row g-3">
              
              <!-- 1. Sticky Asset Type Dropdown -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Asset Type</label>
                <select name="asset_type" id="asset_type" class="form-select" onchange="updateProblemCategories()" required>
                  <option value="" disabled <?php echo empty($sticky_type) ? 'selected' : ''; ?> hidden>Select Asset Type</option>
                  <option value="Elevator" <?php echo ($sticky_type === 'Elevator') ? 'selected' : ''; ?>>Elevator / Lift</option>
                  <option value="AC" <?php echo ($sticky_type === 'AC') ? 'selected' : ''; ?>>Air Conditioner (AC)</option>
                  <option value="Generator" <?php echo ($sticky_type === 'Generator') ? 'selected' : ''; ?>>Power Generator</option>
                </select>
              </div>
              
              <!-- 2. Sticky Asset Brand Text Field -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Asset Brand</label>
                <input type="text" name="asset_brand" class="form-control" placeholder="Brand Name" value="<?php echo htmlspecialchars($sticky_brand); ?>" required>
              </div>
              
              <!-- 3. Sticky Asset ID Text Field -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Asset ID</label>
                <input type="text" name="asset_id" id="asset_id" class="form-control" style="text-transform: uppercase;" placeholder="Select Asset Type First" value="<?php echo htmlspecialchars($sticky_id); ?>" required>
              </div>
              
              <!-- 4. Problem Category (Will reload based on Asset Type Selection) -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Problem Category</label>
                <select name="problem_category" id="problem_category" class="form-select" required>
                  <option value="" disabled selected hidden>Select Asset Type First</option>
                </select>
              </div>
              
              <!-- 5. Sticky Priority Level Dropdown -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Priority Level</label>
                <select name="priority" class="form-select" required>
                  <option value="" disabled <?php echo empty($sticky_priority) ? 'selected' : ''; ?> hidden>Select Priority</option>
                  <option value="Low" <?php echo ($sticky_priority === 'Low') ? 'selected' : ''; ?>>Low</option>
                  <option value="Medium" <?php echo ($sticky_priority === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                  <option value="High" <?php echo ($sticky_priority === 'High') ? 'selected' : ''; ?>>High</option>
                </select>
              </div>
              
              <!-- 6. Sticky Phone Number Text Field -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Phone Number</label>
                <input type="tel" name="phone" class="form-control" placeholder="Enter your Phone Number" value="<?php echo htmlspecialchars($sticky_phone); ?>" required>
              </div>
              
              <!-- 7. Sticky Location Text Field -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Enter Location</label>
                <input type="text" name="location" class="form-control" placeholder="Enter Location" value="<?php echo htmlspecialchars($sticky_location); ?>" required>
              </div>
              
              <!-- 8. Sticky Preferred Payment Method Dropdown -->
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary mb-1">Preferred Payment Method</label>
                <select name="payment_method" class="form-select" required>
                  <option value="" disabled <?php echo empty($sticky_payment) ? 'selected' : ''; ?> hidden>Select Preferred Payment Method</option>
                  <option value="Bkash" <?php echo ($sticky_payment === 'Bkash') ? 'selected' : ''; ?>>bKash</option>
                  <option value="Nagad" <?php echo ($sticky_payment === 'Nagad') ? 'selected' : ''; ?>>Nagad</option>
                  <option value="Bank Transfer" <?php echo ($sticky_payment === 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                  <option value="Cash" <?php echo ($sticky_payment === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                </select>
              </div>

              
              <div class="col-12 mt-4">
                <button type="submit" class="btn btn-submit-cyan w-100 fw-bold">Submit Request</button>
              </div>
              
            </div>
          </form>
        </div>
      </div>


      <!-- RIGHT HAND PANEL: Request History Logs, Overviews, and Complaint Box -->
      <div class="col-lg-7">
                <!-- Module A: Recent Request Monitor Box Grid (WITH LIVE INLINE BASELINE PRICE SYNC) -->
        <div class="dashboard-panel mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="panel-heading m-0">Recent Request</div>
            <!-- FIXED: Redirects cleanly to your correct verified ledger tracking page filename -->
            <a href="service_requests.php" class="btn-outline-custom">View All Requests</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle" style="font-size: 13.5px;">
              <thead class="table-light text-dark uppercase font-monospace">
                <tr>
                  <th scope="col" class="fw-bold py-3 text-secondary" style="font-weight: 700 !important;">SL</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Asset ID</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Asset Type</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Category</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Priority</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Location</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Preferred Payment Method</th>
                  <th scope="col" class="fw-bold py-3" style="font-weight: 700 !important;">Created</th>
                </tr>
              </thead>

              <tbody>
                <?php
                if (isset($conn)) {
                    $logQuery = $conn->prepare("SELECT asset_id, asset_type, problem_category, priority, location, payment_method, created_at FROM service_requests WHERE client_email = ? ORDER BY id DESC LIMIT 3");
                    if ($logQuery) {
                        $logQuery->bind_param("s", $clientEmail);
                        $logQuery->execute();
                        $logResult = $logQuery->get_result();
                        if ($logResult->num_rows > 0) {
                            $sl = 1;
                            while ($row = $logResult->fetch_assoc()) {
                                $currentProblem = trim($row['problem_category'] ?? '');
                                $priceGuideTag = "";

                                // Dynamic Price Guide Calculation Mapping Engine
                                switch ($currentProblem) {
                                    // --- Elevator Categories ---
                                    case 'Component Repair':    $priceGuideTag = "৳4,500"; break;
                                    case 'Part Replacement':     $priceGuideTag = "৳3,000"; break;
                                    case 'Modernization':        $priceGuideTag = "৳15,000"; break;
                                    case 'Routine Servicing':    $priceGuideTag = "৳2,000"; break;
                                    case 'Emergency Breakdown':  $priceGuideTag = "৳5,000"; break;

                                    // --- AC Categories ---
                                    case 'Basic Servicing':      $priceGuideTag = "৳600"; break;
                                    case 'Deep Cleaning':        $priceGuideTag = "৳1,200"; break;
                                    case 'Duct Cleaning':        $priceGuideTag = "৳5,000"; break;
                                    case 'Gas Refill':           $priceGuideTag = "৳2,500"; break;
                                    case 'Electrical Repair':    $priceGuideTag = "৳1,500"; break;
                                    case 'Compressor Repair':    $priceGuideTag = "৳4,000"; break;

                                    // --- Generator Categories ---
                                    case 'Preventative Inspection': $priceGuideTag = "৳3,500"; break;
                                    case 'Fault Code Diagnostic':   $priceGuideTag = "৳1,800"; break;
                                    case 'Engine Rebuild':          $priceGuideTag = "৳25,000"; break;
                                    case 'Component Repairs':       $priceGuideTag = "৳6,000"; break;
                                    case 'Advanced Testing':        $priceGuideTag = "৳8,000"; break;
                                    case 'Fuel Polishing':          $priceGuideTag = "৳4,500"; break;
                                    
                                    default: $priceGuideTag = ""; break;
                                }

                                // Create the HTML pricing badge layout string cleanly if a price is matched
                                $badgeHtml = "";
                                if (!empty($priceGuideTag)) {
                                    $badgeHtml = "<div class='mt-1'><span class='badge font-monospace' style='font-size: 10.5px; padding: 3px 7px; background-color: #ECFEFF; color: #0891B2; border: 1px solid #CFFAFE; border-radius: 4px; font-weight: 700; display: inline-block;'>Base: " . $priceGuideTag . "</span></div>";
                                }

                                echo "<tr>";
                                echo "<td class='text-secondary fw-semibold'>" . $sl++ . "</td>";
                                echo "<td class='fw-bold text-dark'>#" . htmlspecialchars($row['asset_id']) . "</td>";
                                echo "<td class='fw-bold text-dark'>" . htmlspecialchars($row['asset_type']) . "</td>";
                                
                                // INJECTED PRICE DISPLAY: Prints out your problem name, immediately followed by the clean layout badge!
                                echo "<td class='fw-semibold text-dark'><div>" . htmlspecialchars($currentProblem) . "</div>" . $badgeHtml . "</td>";
                                
                                echo "<td><span class='badge bg-light text-dark border border-secondary fw-bold px-2.5 py-1.5'>" . htmlspecialchars($row['priority']) . "</span></td>";
                                echo "<td class='text-truncate fw-semibold text-dark' style='max-width: 120px;'><span title='" . htmlspecialchars($row['location']) . "'>" . htmlspecialchars($row['location']) . "</span></td>";
                                echo "<td class='fw-bold text-dark'>" . htmlspecialchars($row['payment_method']) . "</td>";
                                echo "<td class='text-dark fw-bold'>" . date('Y-m-d', strtotime($row['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-dark fw-bold text-center py-4'>No active operations logs found for your profile.</td></tr>";
                        }
                        $logQuery->close();
                    }
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>


                        <!-- ================= REFACTORED MAINTENANCE OVERVIEW HUB WITH QUICK CLEAR ================= -->
    <div class="card border-0 rounded-4 shadow-sm mb-4 bg-white p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        
        <?php
          // FORCE ACTIVE SYNC: Dynamically pre-calculates unread active/overdue schedule rows
          $activeUserEmail = $_SESSION['email'] ?? '';
          $liveMaintAlertsCount = 0;

          $qMaintCount = $conn->query("SELECT COUNT(*) as total FROM maintenance_schedules WHERE client_email = '$activeUserEmail' AND is_read = 0");
          if ($qMaintCount) { 
              $liveMaintAlertsCount = (int)$qMaintCount->fetch_assoc()['total']; 
          }
        ?>

        <div class="d-flex align-items-center gap-2">
          <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-1.5" style="font-size: 16px;">
             Maintenance Overview
            
            <?php if ($liveMaintAlertsCount > 0): ?>
              <!-- Inline Live Indicator Badge - Automatically floats next to title matching design -->
              <span class="badge rounded-circle text-white d-inline-flex align-items-center justify-content-center p-0 font-monospace fw-bold" 
                    style="width: 16px; height: 16px; font-size: 9.5px; background-color: #EF4444 !important; line-height: 1; margin-left: 4px; vertical-align: middle;">
                <?php echo $liveMaintAlertsCount; ?>
              </span>
            <?php endif; ?>
          </h5>

                    <?php if ($liveMaintAlertsCount > 0): ?>
            <!-- ISOLATED MAINTENANCE CLEAR BUTTON LINK: Clears ONLY the maintenance tracker grid -->
            <a href="client-dashboard.php?clear_maint_alerts=1" class="text-decoration-none fw-bold ms-2" 
               style="font-size: 11px; color: #64748B; transition: color 0.2s;"
               onmouseover="this.style.color='#1E293B';" onmouseout="this.style.color='#64748B';">
              <i class="fa-solid fa-check-double text-secondary" style="font-size: 10px;"></i> Mark all read
            </a>
          <?php endif; ?>

        </div>

        <a href="client_maintenance.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size: 12px; height: 32px; display: flex; align-items: center; justify-content: center; text-decoration: none;">View Maintenance Details</a>
      </div>

      <!-- MAINTENANCE FEED BODY LOOP -->
      <div class="d-flex flex-column gap-2">
        <?php
          // Fetch only items that are unread (is_read = 0)
          $maintListQuery = $conn->query("SELECT asset_id, asset_type, next_due, status FROM maintenance_schedules WHERE client_email = '$activeUserEmail' AND is_read = 0 ORDER BY id DESC");
          
          if ($maintListQuery && $maintListQuery->num_rows > 0):
              while ($mRow = $maintListQuery->fetch_assoc()):
                  $dbStatus = trim($mRow['status'] ?? 'Active');
                  $nextDueTarget = $mRow['next_due'] ?? '';
                  $currentCalendarDay = date('Y-m-d');
                  
                  $isOverdue = ($nextDueTarget < $currentCalendarDay && $dbStatus !== 'Completed') || (strtolower($dbStatus) === 'overdue');
                  
                  $bannerBg = $isOverdue ? '#FEF2F2' : '#F0FDF4';
                  $bannerBorder = $isOverdue ? '#FEE2E2' : '#DCFCE7';
                  $bannerTextClass = $isOverdue ? 'text-danger' : 'text-success';
                  $bannerBadgeText = $isOverdue ? 'OVERDUE' : 'SCHEDULED';
          ?>
            <!-- Dynamic Maintenance Row Alert Box Container -->
            <div class="p-3 border rounded-3 text-start" style="background-color: <?php echo $bannerBg; ?>; border-color: <?php echo $bannerBorder; ?> !important;">
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge text-uppercase font-monospace fw-extrabold" style="font-size: 9px; padding: 2px 5px; background-color: <?php echo $isOverdue ? '#EF4444' : '#10B981'; ?>; color: #FFFFFF; font-weight:800; border-radius:4px;"><?php echo $bannerBadgeText; ?></span>
                <strong class="text-dark" style="font-size: 13.5px;"><?php echo htmlspecialchars($mRow['asset_id'] . " (" . $mRow['asset_type'] . ")"); ?></strong>
              </div>
              <div class="text-muted mt-1 small font-monospace" style="font-size: 11.5px;">
                Target Inspection Window Calibration Date: <strong class="<?php echo $bannerTextClass; ?>"><?php echo date('d-m-Y', strtotime($mRow['next_due'])); ?></strong>
              </div>
            </div>
          <?php 
              endwhile;
          else: 
          ?>
            <!-- Pristine Fallback Panel Canvas Display -->
            <div class="text-center py-4 border rounded-3 bg-light text-muted font-monospace small" style="font-size: 12px; background-color: #F8FAFC !important; border-color: #E2E8F0 !important;">
              🍃 Calendar Clear: All preventative inspection maintenance schedules are caught up and reviewed.
            </div>
          <?php endif; ?>
      </div>
    </div>



                                   <!-- ================= REFACTORED NOTIFICATIONS HUB WITH MARK READ MATRIX ================= -->
    <div class="card border-0 rounded-4 shadow-sm mb-4 bg-white p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        
                                <?php
          // REFACTORED WORKFLOW SYNC: Counts ONLY direct service and delivery updates
          $activeUserEmail = $_SESSION['email'] ?? '';
          $totalLiveAlerts = 0;

          // Count 1: Processing Dispatches
          $qCountA = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE client_email = '$activeUserEmail' AND status = 'processing' AND is_read = 0");
          if ($qCountA) { $totalLiveAlerts += (int)$qCountA->fetch_assoc()['total']; }

          // Count 2: Today's Completed Closures
          $qCountB = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE client_email = '$activeUserEmail' AND status = 'completed' AND DATE(created_at) = CURDATE() AND is_read = 0");
          if ($qCountB) { $totalLiveAlerts += (int)$qCountB->fetch_assoc()['total']; }
        ?>




        <div class="d-flex align-items-center gap-2">
          <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-1.5" style="font-size: 16px;">
            <span style="font-size: 18px;">📊</span> Notifications
            
            <?php if ($totalLiveAlerts > 0): ?>
              <!-- Inline Live Indicator Badge -->
              <span class="badge rounded-circle text-white d-inline-flex align-items-center justify-content-center p-0 font-monospace fw-bold" 
                    style="width: 16px; height: 16px; font-size: 9.5px; background-color: #EF4444 !important; line-height: 1; vertical-align: middle;">
                <?php echo $totalLiveAlerts; ?>
              </span>
            <?php endif; ?>
          </h5>

          <?php if ($totalLiveAlerts > 0): ?>
            <!-- ================= SLEEK MINI MARK READ QUICK LINK ================= -->
            <a href="client-dashboard.php?clear_client_alerts=1" class="text-decoration-none fw-bold ms-2" 
               style="font-size: 11px; color: #64748B; transition: color 0.2s;"
               onmouseover="this.style.color='#1E293B';" onmouseout="this.style.color='#64748B';">
              <i class="fa-solid fa-check-double text-secondary" style="font-size: 10px;"></i> Mark all read
            </a>
          <?php endif; ?>
        </div>

        <span class="badge rounded-pill bg-light text-secondary border px-2.5 py-1" style="font-size: 11px; font-weight: 700;">
          Live Stream Feed
        </span>
      </div>


      
      <!-- SCROLL CONTAINER HOOK: Height capped at 245px with an invisible responsive scroll track -->
      <div class="d-flex flex-column gap-2.5" style="max-height: 245px; overflow-y: auto; padding-right: 4px; scrollbar-width: thin; -ms-overflow-style: none;">
        <?php
        $activeUserEmail = $_SESSION['email'] ?? '';
        $hasNotifications = false;

        // --------------------------------------------------------------------
        // LOGIC REPOSITORY A: FETCH ALL TICKETS CURRENTLY IN PROCESSING STATE
        // --------------------------------------------------------------------
        // REMOVED "LIMIT 1" to let ALL active field dispatches populate fluidly inline!
$dispatchQuery = $conn->query("SELECT asset_id, asset_type, location FROM service_requests WHERE client_email = '$activeUserEmail' AND status = 'processing' AND is_read = 0 ORDER BY id DESC");
        
        if ($dispatchQuery && $dispatchQuery->num_rows > 0):
            while ($dRow = $dispatchQuery->fetch_assoc()):
                $hasNotifications = true;
                $locString = $dRow['location'] ?? '';
                $techName = "A Field Engineer";
                if (preg_match('/\(Assigned to:\s*([^)]+)\)/', $locString, $matches)) {
                    $techName = trim($matches[1]);
                }
        ?>
          <!-- Dispatch Notification Item Badge Grid -->
          <div class="p-3 border rounded-3 d-flex align-items-start gap-2.5 text-start animate-fade-in" style="background-color: #ECFEFF; border-color: #CFFAFE !important; transition: all 0.2s;">
            <div class="mt-0.5" style="font-size: 15px;">⚡</div>
            <div>
              <span class="d-block fw-bold text-dark" style="font-size: 13px;">Field Crew Dispatched!</span>
              <span class="d-block text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.45;">
                Manager has approved your ticket for **<?php echo htmlspecialchars($dRow['asset_id']); ?>** (<?php echo htmlspecialchars($dRow['asset_type']); ?>). **<?php echo htmlspecialchars($techName); ?>** is arriving on site [1.1].
              </span>
            </div>
          </div>
        <?php 
            endwhile;
        endif; 

         

                  // --------------------------------------------------------------------
        // LOGIC REPOSITORY C: FETCH RECENTLY COMPLETED REPAIR TASKS (FIXED)
        // --------------------------------------------------------------------
        // Pulls completed items and automatically resolves base rate fallbacks if empty
$completedFeedQuery = $conn->query("SELECT asset_id, asset_type, problem_category, allocated_part, part_price, amount FROM service_requests WHERE client_email = '$activeUserEmail' AND status = 'completed' AND DATE(created_at) = CURDATE() AND is_read = 0 ORDER BY id DESC");
        
        if ($completedFeedQuery && $completedFeedQuery->num_rows > 0):
            while ($cRow = $completedFeedQuery->fetch_assoc()):
                $hasNotifications = true;
                $usedPart = trim($cRow['allocated_part'] ?? '');
                $partPrice = (float)($cRow['part_price'] ?? 0.00);
                $finalAmount = (float)($cRow['amount'] ?? 0.00);
                $currentProblem = trim($cRow['problem_category'] ?? '');

                // Match labor fees exactly to calculate fallback baseline rates for every single row item
                $baseRateNumeric = 0.00;
                switch ($currentProblem) {
                    case 'Component Repair':          $baseRateNumeric = 4500.00; break;
                    case 'Part Replacement':           $baseRateNumeric = 3000.00; break;
                    case 'Modernization':              $baseRateNumeric = 15000.00; break;
                    case 'Routine Servicing':          $baseRateNumeric = 2000.00; break;
                    case 'Emergency Breakdown':        $baseRateNumeric = 5000.00; break;
                    case 'Basic Servicing':            $baseRateNumeric = 600.00; break;
                    case 'Deep Cleaning':              $baseRateNumeric = 1200.00; break;
                    case 'Duct Cleaning':              $baseRateNumeric = 5000.00; break;
                    case 'Gas Refill':                 $baseRateNumeric = 2500.00; break;
                    case 'Electrical Repair':          $baseRateNumeric = 1500.00; break;
                    case 'Compressor Repair':          $baseRateNumeric = 4000.00; break;
                    case 'Preventative Inspection':    $baseRateNumeric = 3500.00; break;
                    case 'Fault Code Diagnostic':         $baseRateNumeric = 1800.00; break;
                    case 'Engine Rebuild':                $baseRateNumeric = 25000.00; break;
                    case 'Component Repairs':             $baseRateNumeric = 6000.00; break;
                    case 'Advanced Testing':              $baseRateNumeric = 8000.00; break;
                    case 'Fuel Polishing':                $baseRateNumeric = 4500.00; break;
                    default:                              $baseRateNumeric = 0.00; break;
                }

                // SMART FALLBACK COMPILER: If database amount is empty, calculate Base Labor + Part Price automatically!
                $displayBill = ($finalAmount > 0.00) ? $finalAmount : ($baseRateNumeric + $partPrice);
        ?>
          <!-- Completed Task Notification Item Badge Card -->
          <div class="p-3 border rounded-3 d-flex align-items-start gap-2.5 text-start animate-fade-in" style="background-color: #F0FDF4; border-color: #DCFCE7 !important; transition: all 0.2s;">
            <div class="mt-0.5" style="font-size: 15px;">✅</div>
            <div>
              <span class="d-block fw-bold text-success" style="font-size: 13px;">Servicing Completed Successfully!</span>
              <span class="d-block text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.45;">
                Your request for unit **<?php echo htmlspecialchars($cRow['asset_id']); ?>** (<?php echo htmlspecialchars($cRow['asset_type']); ?>) has been closed by the field engineer. 
                <strong>Invoice Total:</strong> ৳<?php echo number_format($displayBill, 2); ?> <?php echo !empty($usedPart) ? "(Warehouse part allocated: " . htmlspecialchars($usedPart) . ")" : ""; ?>.
              </span>
            </div>
          </div>
        <?php 
            endwhile;
        endif; 



        // --------------------------------------------------------------------
        // FALLBACK CONTAINER: RENDERS ONLY IF MATRIX SUM RECOVERY IS COMPLETELY ZERO
        // --------------------------------------------------------------------
        if (!$hasNotifications): 
        ?>
          <div class="text-center py-4 border rounded-3 bg-light text-muted font-monospace small" style="font-size: 12px; background-color: #F8FAFC !important; border-color: #E2E8F0 !important;">
            🍃 Pristine Canvas: No new operational alerts or dispatches received from management.
          </div>
        <?php endif; ?>
      </div>
    </div>





        <!-- Module C: Document Downloads Portal -->
        <div class="dashboard-panel mb-4">
          <div class="panel-heading mb-1">Downloads</div>
          <p class="text-muted small mb-3">Open a printable report and use Ctrl + P Save as PDF.</p>
          <div class="d-flex gap-2">
            <a href="print-requests.php?type=requests" target="_blank" class="btn btn-sm btn-light border px-3 py-2 text-secondary fw-semibold">Service Requests (PDF)</a>
            <a href="generate_maintenance-report.php?type=maintenance" target="_blank" class="btn btn-sm btn-light border px-3 py-2 text-secondary fw-semibold">Maintenance Overview (PDF)</a>
          </div>
        </div>

               <!-- ================= FIGMA SPEC COMPLAINT CARD MODULE ================= -->
        <div class="card border-0 rounded-4 shadow-sm mb-4 bg-white p-4">
          <h5 class="fw-bold text-danger mb-1" style="font-size: 16px;">Complaint</h5>
          <p class="text-muted small mb-3">Lodge a direct operational escalation ticket onto the manager dashboard queue.</p>

          <!-- System Alert Notifications Inline Banners -->
          <?php if (!empty($actionMessage)): ?>
            <div class="alert alert-success border-0 small py-2 px-3 font-monospace mb-3" style="font-size:12px; background-color:#F0FDF4; color:#16A34A; border:1px solid #DCFCE7;"><?php echo $actionMessage; ?></div>
          <?php endif; ?>
          <?php if (!empty($actionError)): ?>
            <div class="alert alert-danger border-0 small py-2 px-3 font-monospace mb-3" style="font-size:12px; background-color:#FEF2F2; color:#EF4444; border:1px solid #FEE2E2;"><?php echo $actionError; ?></div>
          <?php endif; ?>

                    <!-- ================= FIXED ALIGNED COMPLAINT FORM MODULE ================= -->
          <form action="client-dashboard.php" method="POST">
            <input type="hidden" name="action_type" value="submit_complaint_escalation">
            
            <!-- Grid Split: Form row broken down cleanly into perfectly divisible column sets -->
            <div class="row g-2 mb-2.5">
              <!-- Column 1: Asset Code ID Field (Takes up exactly half the width box space) -->
              <div class="col-6">
                <input type="text" name="asset_id" class="form-control form-control-custom w-100 text-uppercase" placeholder="Enter Asset ID" style="height: 38px; font-size: 12.5px; background-color: #F8FAFC;" required>
              </div>
              
              <!-- Column 2: Asset Machinery Classification Selector (Takes up the other half width box space) -->
              <div class="col-6">
                <select name="asset_type" class="form-select form-select-custom w-100" style="height: 38px; font-size: 12.5px; background-color: #F8FAFC;" required>
                  <option value="" disabled selected hidden>Select Asset Type</option>
                  <option value="AC">AC Unit</option>
                  <option value="Elevator">Elevator</option>
                  <option value="Generator">Generator</option>
                </select>
              </div>
            </div>

            <!-- Full-Width Horizontal row for your complaint type categories -->
            <div class="mb-3">
              <select name="problem_category" class="form-select form-select-custom w-100" style="height: 38px; font-size: 12.5px; background-color: #F8FAFC;" required>
                <option value="" disabled selected hidden>Select Complaint Type Category</option>
                <option value="Machine Still Broken">Machine Still Broken / Faulty Servicing</option>
                <option value="Technician Conduct">Technician Conduct / Negligence Issue</option>
                <option value="Billing Dispute">Billing Dispute / Overcharged Parts</option>
                <option value="Warranty Refusal">Warranty Claim Rejection Request</option>
              </select>
            </div>

            <!-- Detailed Technical Notes Context Textarea -->
            <div class="mb-3">
              <textarea name="complaint_notes" class="form-control" rows="3" placeholder="Write your complaint notes details here..." style="font-size: 13px; background-color: #F8FAFC;" required></textarea>
            </div>

            <!-- Submit Button Trigger Element Layout Layout Card -->
            <button type="submit" class="btn btn-danger w-100 fw-bold text-uppercase" style="height: 42px; font-size: 12px; background-color: #EF4444; border: none; border-radius: 6px; transition: all 0.2s;">
              Submit Complaint
            </button>
          </form>
        </div>


  <!-- Modern Warning Toast DOM Structure -->
  <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1100;">
    <div id="validationToast" class="toast align-items-center text-white bg-danger border-0 rounded-3 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
      <div class="d-flex p-3">
        <div class="toast-body d-flex align-items-center gap-2 font-monospace fw-bold" id="toastMessage" style="font-size: 14px;"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close" style="box-shadow: none;"></button>
      </div>
    </div>
  </div>

  <!-- Framework Compiled Engine Injector Libraries -->
  <!-- <script src="https://jsdelivr.net"></script> -->
  <script>
        // Automated Backend Duplicate Error Monitor Intercept Trigger
    document.addEventListener("DOMContentLoaded", function() {
        const backendErrorMsg = "<?php echo isset($toastTriggerMsg) ? addslashes($toastTriggerMsg) : ''; ?>";
        if (backendErrorMsg.trim() !== "") {
            const toastElement = document.getElementById('validationToast');
            const toastMessage = document.getElementById('toastMessage');
            if(toastElement && toastMessage) {
                toastMessage.innerHTML = backendErrorMsg;
                new bootstrap.Toast(toastElement).show();
            }
        }
        
        // ADDED STICKY AUTOMATION: Force-triggers option swappers if a failed form reloads with a saved type
        const savedType = document.getElementById('asset_type').value;
        if (savedType !== "") {
            updateProblemCategories();
            // Automatically select back the problem category the user initially chose
            const problemSelect = document.getElementById('problem_category');
            const savedProblem = "<?php echo isset($problemCategory) ? addslashes($problemCategory) : ''; ?>";
            if (savedProblem !== "") {
                problemSelect.value = savedProblem;
            }
        }
    });


    // Module 2: Dynamic Dropdown Problem Categories Swapper Menu Loader
    function updateProblemCategories() {
      const assetType = document.getElementById('asset_type').value;
      const problemSelect = document.getElementById('problem_category');
      const assetIdInput = document.getElementById('asset_id');

      problemSelect.innerHTML = '<option value="" disabled selected hidden>Select Issue</option>';

      if (assetType === 'Elevator') {
          assetIdInput.placeholder = "Enter ID (Must start with 'ELV', e.g., ELV-101)";
      } else if (assetType === 'AC') {
          assetIdInput.placeholder = "Enter ID (Must start with 'AC', e.g., AC-202)";
      } else if (assetType === 'Generator') {
          assetIdInput.placeholder = "Enter ID (Must start with 'GEN', e.g., GEN-303)";
      } else {
          assetIdInput.placeholder = "Select Asset Type First";
      }

     // We append local standard service baseline pricing directly onto your visible text options
      const problems = {
        'Elevator': [
          { value: 'Component Repair', text: 'Component Repair (Motors, Gearboxes, PCBs) — [From ৳4,500]' },
          { value: 'Part Replacement', text: 'Part Replacement (Worn wire ropes, Brakes) — [From ৳3,000]' },
          { value: 'Modernization', text: 'Modernization (Upgrading control panels) — [From ৳15,000]' },
          { value: 'Routine Servicing', text: 'Routine Monthly / Quarterly Check — [From ৳2,000]' },
          { value: 'Emergency Breakdown', text: 'Emergency Breakdown Support — [From ৳5,000]' }
        ],
        'AC': [
          { value: 'Basic Servicing', text: 'Basic Servicing (Filter washing, dust removal) — [From ৳600]' },
          { value: 'Deep Cleaning', text: 'Master Jet Wash / Deep Cleaning — [From ৳1,200]' },
          { value: 'Duct Cleaning', text: 'Duct Cleaning & Air Vents — [From ৳5,000]' },
          { value: 'Gas Refill', text: 'Gas Refill / Refrigerant Leak Repair — [From ৳2,500]' },
          { value: 'Electrical Repair', text: 'Electrical & PCB Circuit Repair — [From ৳1,500]' },
          { value: 'Compressor Repair', text: 'Compressor & Blower Motor Overhaul — [From ৳4,000]' }
        ],
        'Generator': [
          { value: 'Preventative Inspection', text: 'Preventative Maintenance (Fluids & Filters) — [From ৳3,500]' },
          { value: 'Fault Code Diagnostic', text: 'Fault Code Decoding & Control Panel Alerts — [From ৳1,800]' },
          { value: 'Engine Rebuild', text: 'Engine Rebuild / Motor Overhaul — [From ৳25,000]' },
          { value: 'Component Repairs', text: 'Component Repairs (AVR, Alternators) — [From ৳6,000]' },
          { value: 'Advanced Testing', text: 'Load Bank & ATS Switch Testing — [From ৳8,000]' },
          { value: 'Fuel Polishing', text: 'Fuel Polishing & Auxiliary Support — [From ৳4,500]' }
        ]
      };

      if (problems[assetType]) {
        problems[assetType].forEach(issue => {
          const option = document.createElement('option');
          option.value = issue.value; // Keeps the core database entry text short and clean
          option.textContent = issue.text; // Displays the full text layout description with pricing
          problemSelect.appendChild(option);
        });
      }
    }

    // Module 3: Combined Form Verification Intercept Engine (Asset prefixes & 11-digit Phone)
    function validateFormLayout(event) {
        const assetType = document.getElementById('asset_type').value;
        const assetId = document.getElementById('asset_id').value.trim().toUpperCase();
        const phoneInput = document.querySelector("input[name='phone']");
        const phoneValue = phoneInput ? phoneInput.value.trim() : "";
        
        const toastElement = document.getElementById('validationToast');
        const toastMessage = document.getElementById('toastMessage');
        
        if (!toastElement || !toastMessage) { return true; }
        const bsToast = new bootstrap.Toast(toastElement);

        // Prefix Mismatch Validations Check
        if (assetType === 'Elevator' && !assetId.startsWith('ELV')) {
            toastMessage.innerHTML = '<i class="fa fa-exclamation-triangle fs-5 me-1"></i> Validation Error: Elevator Asset ID must start with "ELV" (e.g., ELV-101)';
            bsToast.show();
            event.preventDefault(); 
            return false;
        } 
        else if (assetType === 'AC' && !assetId.startsWith('AC')) {
            toastMessage.innerHTML = '<i class="fa fa-exclamation-triangle fs-5 me-1"></i> Validation Error: AC Asset ID must start with "AC" (e.g., AC-202)';
            bsToast.show();
            event.preventDefault();
            return false;
        } 
        else if (assetType === 'Generator' && !assetId.startsWith('GEN')) {
            toastMessage.innerHTML = '<i class="fa fa-exclamation-triangle fs-5 me-1"></i> Validation Error: Generator Asset ID must start with "GEN" (e.g., GEN-303)';
            bsToast.show();
            event.preventDefault();
            return false;
        }
        
        // Strict 11-Digit Length Phone Match Rules
        const phonePattern = /^\d{11}$/;
        if (!phonePattern.test(phoneValue)) {
            toastMessage.innerHTML = '<i class="fa fa-phone fs-5 me-1"></i> Validation Error: Phone Number must contain exactly 11 numeric digits (e.g., 01712345678).';
            bsToast.show();
            event.preventDefault(); 
            return false;
        }
        
        return true; 
    }
  </script>
</body>
</html>
<?php 
// Terminate your database integration connection thread cleanly on page exit
if (isset($conn)) {
    $conn->close();
}