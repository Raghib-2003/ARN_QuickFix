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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type'])) {
    if ($_POST['action_type'] === 'service_request') {
        $assetType = $_POST['asset_type'] ?? '';
        $assetBrand = $_POST['asset_brand'] ?? '';
        $assetId = trim(strtoupper($_POST['asset_id'] ?? ''));
        $problemCategory = $_POST['problem_category'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $location = $_POST['location'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? '';
        
        // Check for Duplicate Asset ID inside active connection thread pipelines
        $checkAsset = $conn->prepare("SELECT id FROM service_requests WHERE asset_id = ?");
        $checkAsset->bind_param("s", $assetId);
        $checkAsset->execute();
        $checkAsset->store_result();
        $assetDuplicatesCount = $checkAsset->num_rows;
        $checkAsset->close();
        
        // Check for Duplicate Contact Phone Number inside active connection thread pipelines
        $checkPhone = $conn->prepare("SELECT id FROM service_requests WHERE phone = ?");
        $checkPhone->bind_param("s", $phone);
        $checkPhone->execute();
        $checkPhone->store_result();
        $phoneDuplicatesCount = $checkPhone->num_rows;
        $checkPhone->close();
        
        if ($assetDuplicatesCount > 0) {
            $toastTriggerMsg = "<i class='fa fa-database me-1'></i> Duplicate Error: This Asset ID is already linked to an active ticket!";
        } elseif ($phoneDuplicatesCount > 0) {
            $toastTriggerMsg = "<i class='fa fa-phone-slash me-1'></i> Duplicate Error: This Phone Number is already linked to an active ticket row!";
        } else {
            // Core database entry insertion execution mapping (amount defaults to NULL)
            $insertStmt = $conn->prepare("INSERT INTO service_requests (client_email, asset_type, asset_brand, asset_id, problem_category, priority, phone, location, payment_method, status, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL)");
            $insertStmt->bind_param("sssssssss", $clientEmail, $assetType, $assetBrand, $assetId, $problemCategory, $priority, $phone, $location, $paymentMethod);
            
            if ($insertStmt->execute()) {
                $_SESSION['flash_request_success'] = true;
                header("Location: client-dashboard.php");
                exit();
            } else {
                $toastTriggerMsg = "<i class='fa fa-exclamation-circle me-1'></i> Database Error: Failed to execute request submission.";
            }
            $insertStmt->close();
        }
    } elseif ($_POST['action_type'] === 'complaint') {
        $compAssetId = trim(strtoupper($_POST['complaint_asset_id'] ?? ''));
        $compAssetType = $_POST['complaint_asset_type'] ?? '';
        $compProblem = $_POST['complaint_problem_category'] ?? '';
        $compText = trim($_POST['complaint_text'] ?? '');
        
        $compStmt = $conn->prepare("INSERT INTO complaints (client_email, asset_id, asset_type, problem_category, complaint_text) VALUES (?, ?, ?, ?, ?)");
        $compStmt->bind_param("sssss", $clientEmail, $compAssetId, $compAssetType, $compProblem, $compText);
        
        if ($compStmt->execute()) {
            $_SESSION['flash_complaint_success'] = true;
            header("Location: client-dashboard.php");
            exit();
        } else {
            $toastTriggerMsg = "<i class='fa fa-exclamation-circle me-1'></i> Complaint Error: Failed registering escalation ticket.";
        }
        $compStmt->close();
    }
}

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
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Asset Type</label>
                <select name="asset_type" id="asset_type" class="form-select" onchange="updateProblemCategories()" required>
                  <option value="" disabled selected hidden>Select Asset Type</option>
                  <option value="Elevator">Elevator / Lift</option>
                  <option value="AC">Air Conditioner (AC)</option>
                  <option value="Generator">Power Generator</option>
                </select>
              </div>
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Asset Brand</label>
                <input type="text" name="asset_brand" class="form-control" placeholder="Brand Name" required>
              </div>
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Asset ID</label>
                <input type="text" name="asset_id" id="asset_id" class="form-control" style="text-transform: uppercase;" placeholder="Select Asset Type First" required>
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Problem Category</label>
                <select name="problem_category" id="problem_category" class="form-select" required>
                  <option value="" disabled selected hidden>Select Asset Type First</option>
                </select>
              </div>
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Priority Level</label>
                <select name="priority" class="form-select" required>
                  <option value="" disabled selected hidden>Select Priority</option>
                  <option value="Low">Low</option>
                  <option value="Medium">Medium</option>
                  <option value="High">High</option>
                </select>
              </div>
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Phone Number</label>
                <!-- FIXED PARAMETER: Explicit name="phone" attribute property matching server binds -->
                <input type="tel" name="phone" class="form-control" placeholder="Enter your 11-Digit Phone Number" required>
              </div>
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Enter Location</label>
                <input type="text" name="location" class="form-control" placeholder="Enter Location" required>
              </div>
              
              <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Preferred Payment Method</label>
                <select name="payment_method" class="form-select" required>
                  <option value="" disabled selected hidden>Select Preferred Payment Method</option>
                  <option value="Bkash">bKash</option>
                  <option value="Nagad">Nagad</option>
                  <option value="Bank Transfer">Bank Transfer</option>
                  <option value="Cash">Cash</option>
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

                <!-- Module B: Scheduled Maintenance Cycles Calendar Box -->
        <div class="dashboard-panel mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="panel-heading m-0">Maintenance Overview</div>
            <a href="maintenance.php" class="btn-outline-custom">View Maintenance Details</a>
          </div>
          
          <!-- Explicitly labeled sample container with subtle aesthetic background tint styling -->
          <div class="p-3 border border-warning-subtle rounded-3" style="font-size: 13px; background-color: #FFFBEB !important;">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-warning text-dark font-monospace fw-bold" style="font-size: 10px;">EXAMPLE DEMO</span>
              <div class="fw-bold text-dark">ELV-9-C (Elevator Unit)</div>
            </div>
            <div class="text-secondary small fw-semibold">Type: Monthly Check | Last Check: 12-06-2026 | Next Due: 12-07-2026</div>
          </div>
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
    // Module 1: Automated Backend Duplicate Error Monitor Intercept Trigger
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

      const problems = {
        'Elevator': [
          { value: 'Component Repair', text: 'Component Repair (Motors, Gearboxes, Door systems, PCBs)' },
          { value: 'Part Replacement', text: 'Part Replacement (Worn wire ropes, Brakes, Sensors)' },
          { value: 'Modernization', text: 'Modernization (Upgrading control panels & aesthetics)' },
          { value: 'Routine Servicing', text: 'Routine Monthly / Quarterly Check' },
          { value: 'Emergency Breakdown', text: 'Emergency Breakdown Support' }
        ],
        'AC': [
          { value: 'Basic Servicing', text: 'Basic Servicing (Filter washing, dust removal)' },
          { value: 'Deep Cleaning', text: 'Master Jet Wash / Deep Cleaning' },
          { value: 'Duct Cleaning', text: 'Duct Cleaning & Air Vents' },
          { value: 'Gas Refill', text: 'Gas Refill / Refrigerant Leak Repair' },
          { value: 'Electrical Repair', text: 'Electrical & PCB Circuit Repair' },
          { value: 'Compressor Repair', text: 'Compressor & Blower Motor Overhaul' }
        ],
        'Generator': [
          { value: 'Preventative Inspection', text: 'Preventative Maintenance (Fluids & Filters)' },
          { value: 'Fault Code Diagnostic', text: 'Fault Code Decoding & Control Panel Alerts' },
          { value: 'Engine Rebuild', text: 'Engine Rebuild / Motor Overhaul' },
          { value: 'Component Repairs', text: 'Component Repairs (AVR, Alternators, etc.)' },
          { value: 'Advanced Testing', text: 'Load Bank & ATS Switch Testing' },
          { value: 'Fuel Polishing', text: 'Fuel Polishing & Auxiliary Support' }
        ]
      };

      if (problems[assetType]) {
        problems[assetType].forEach(issue => {
          const option = document.createElement('option');
          option.value = issue.value;
          option.textContent = issue.text;
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
?>
