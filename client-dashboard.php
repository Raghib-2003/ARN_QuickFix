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
        <!-- Module A: Recent Request Monitor Box Grid -->
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
                                echo "<tr>";
                                // Row contents boosted with 'fw-semibold' (font-weight: 600) or 'fw-bold' (font-weight: 700)
                                echo "<td class='text-secondary fw-semibold'>" . $sl++ . "</td>";
                                echo "<td class='fw-bold text-dark'>#" . htmlspecialchars($row['asset_id']) . "</td>";
                                echo "<td class='fw-bold text-dark'>" . htmlspecialchars($row['asset_type']) . "</td>";
                                echo "<td class='fw-semibold text-dark'>" . htmlspecialchars($row['problem_category']) . "</td>";
                                echo "<td><span class='badge bg-light text-dark border border-secondary fw-bold px-2.5 py-1.5'>" . htmlspecialchars($row['priority']) . "</span></td>";
                                echo "<td class='text-truncate fw-semibold text-dark' style='max-width: 120px;'>" . htmlspecialchars($row['location']) . "</td>";
                                echo "<td class='fw-bold text-dark'>" . htmlspecialchars($row['payment_method']) . "</td>";
                                // Cleaned up creation date display visibility formatting layout row
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

                    <!-- ================= FIXED COMPACT MAINTENANCE OVERVIEW PANEL ================= -->
        <div class="card border-0 rounded-4 shadow-sm mb-4 bg-white p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0 text-dark" style="font-size: 16px;">Maintenance Overview</h5>
            <a href="client_maintenance.php" class="btn btn-sm btn-light border fw-bold text-secondary small rounded-pill px-3" style="font-size: 11px; text-decoration: none;">View Maintenance Details</a>
          </div>

          <?php
          // Fetch the single latest active or overdue maintenance row matching this specific logged-in client
          $activeUserEmail = $_SESSION['email'] ?? '';
          $maintCheckQuery = $conn->query("SELECT asset_type, asset_id, last_service, next_due, maintenance_type, status FROM maintenance_schedules WHERE client_email = '$activeUserEmail' ORDER BY id DESC LIMIT 1");

          if ($maintCheckQuery && $maintCheckQuery->num_rows > 0):
              $maintRow = $maintCheckQuery->fetch_assoc();
              
              // Map elegant visual warning states matching your client UI dashboard standards
              $isOverdue = (strtolower($maintRow['status']) === 'overdue');
              $alertThemeBg = $isOverdue ? '#FEF2F2' : '#FFFBEB';
              $alertThemeText = $isOverdue ? '#EF4444' : '#D97706';
              $alertBadgeBg = $isOverdue ? '#EF4444' : '#F59E0B';
          ?>
            <!-- Live Active Notification Banner (Renders dynamically ONLY if a real row exists) -->
            <div class="p-3 border rounded-3 d-flex flex-column gap-1" style="background-color: <?php echo $alertThemeBg; ?>; border-color: rgba(0,0,0,0.02) !important;">
              <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-2 py-1 font-monospace text-uppercase" style="font-size: 10px; background-color: <?php echo $alertBadgeBg; ?>; border: none; border-radius: 4px;">
                  <?php echo htmlspecialchars($maintRow['status']); ?>
                </span>
                <strong style="color: #0F172A; font-size: 14px;">
                  <?php echo htmlspecialchars($maintRow['asset_id']); ?> (<?php echo htmlspecialchars($maintRow['asset_type']); ?> Unit)
                </strong>
              </div>
              <p class="m-0 font-monospace small mt-1" style="font-size: 12px; color: <?php echo $alertThemeText; ?> !important; opacity: 0.95;">
                Type: <strong><?php echo htmlspecialchars($maintRow['maintenance_type']); ?> Check</strong> | 
                Last Check: <?php echo (!empty($maintRow['last_service']) && $maintRow['last_service'] !== '0000-00-00') ? date('d-m-Y', strtotime($maintRow['last_service'])) : 'None'; ?> | 
                Next Due: <strong class="<?php echo $isOverdue ? 'text-danger' : ''; ?> fw-bold"><?php echo date('d-m-Y', strtotime($maintRow['next_due'])); ?></strong>
              </p>
            </div>
          <?php else: ?>
            <!-- Fallback design layout placeholder if the manager hasn't pushed any tickets yet -->
            <div class="text-center py-4 border rounded-3 bg-light text-muted font-monospace small" style="font-size: 12px; background-color: #F8FAFC !important; border-color: #E2E8F0 !important;">
              🍃 Safe Status: No active or overdue maintenance intervals logged for your machinery.
            </div>
          <?php endif; ?>
        </div>



                <!-- ================= NEW INFALLIBLE MANAGER NOTIFICATIONS PANEL ================= -->
        <div class="dashboard-panel mb-4" style="border-left: 5px solid var(--primary-cyan) !important;">
          <div class="d-flex align-items-center mb-3">
            <!-- Native HTML character emoji instead of FontAwesome ensures a symbol always renders -->
            <span style="font-size: 20px; margin-right: 10px;">✉️</span>
            <div class="panel-heading m-0">Manager Notifications</div>
          </div>
          
          <div style="max-height: 250px; overflow-y: auto; padding-right: 5px;">
            <?php if (!empty($notifications)): ?>
              <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($notifications as $notif): ?>
                  <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                      <span style="font-size: 13px; font-weight: 700; color: #334155;">📋 Operations Dispatcher</span>
                      <span style="font-size: 11px; font-family: monospace; color: #64748B;"><?php echo htmlspecialchars($notif['created_at']); ?></span>
                    </div>
                    <p style="font-size: 13.5px; font-weight: 600; color: #475569; margin: 0; padding-left: 10px; border-left: 3px solid var(--primary-cyan);">
                      <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <!-- Clean structural fallback card layout view -->
              <div class="text-center py-4 text-muted" style="background-color: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 8px;">
                <p class="small m-0 font-monospace fw-bold" style="color: #64748B;">No new messages received from the management team.</p>
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

        <!-- Module D: Complaints Ticket Registration Escalation Form Block -->
        <div class="dashboard-panel border border-danger-subtle bg-white">
          <div class="panel-heading text-danger mb-1">Complaint</div>
          <p class="text-muted small mb-3">Lodge a direct operational escalation ticket onto the manager dashboard queue.</p>
          <form action="client-dashboard.php" method="POST">
            <input type="hidden" name="action_type" value="complaint">
            <div class="row g-2">
              <div class="col-sm-4"><input type="text" name="complaint_asset_id" class="form-control" placeholder="Enter Asset ID" required></div>
                            <div class="col-sm-4">
                <select name="complaint_asset_type" class="form-select" required>
                  <option value="" disabled selected hidden>Select Asset Type</option>
                  <option value="Elevator">Elevator / Lift</option>
                  <option value="AC">AC Unit</option>
                  <option value="Generator">Generator</option>
                </select>
              </div>
              <div class="col-sm-4">
                <select name="complaint_problem_category" class="form-select" required>
                  <option value="" disabled selected hidden>Select Category</option>
                  <option value="Delay">Technical Dispatch Delay</option>
                  <option value="Faulty Repair">Recurring Mechanical Fault</option>
                  <option value="Billing">Invoice Verification Conflict</option>
                </select>
              </div>
              <div class="col-12 mt-2">
                <textarea name="complaint_text" class="form-control" rows="3" placeholder="Write your complaint" required></textarea>
              </div>
              <div class="col-12 mt-2">
                <button type="submit" class="btn btn-complaint-red w-100">Submit Complaint</button>
              </div>
            </div>
          </form>
        </div>
      </div> <!-- Close col-lg-7 right hand column -->
    </div> <!-- Close main content split row -->
  </div> <!-- Close central wrapper container -->

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