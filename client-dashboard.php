<?php
// 1. Initialize Active User Session and Force Authorization Guard Rails
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force error reporting on for local workspace debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// BULLETPROOF FIX: We check for BOTH uppercase and lowercase variants to prevent any redirection loops!
if (isset($_SESSION['Email'])) { $_SESSION['email'] = $_SESSION['Email']; }
if (isset($_SESSION['Name'])) { $_SESSION['name'] = $_SESSION['Name']; }

if (!isset($_SESSION['email']) || !isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

// Extract authenticated user parameters safely
$clientName = htmlspecialchars($_SESSION['name']);
$clientEmail = htmlspecialchars($_SESSION['email']);



// 3. Establish Secure Database Integration Network Link
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "arn_quickfix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Error Trace: " . $conn->connect_error);
}

// 3. Process Backend Data Metric Tracking Summaries (RESTORED USERNAME PRESERVATION)
$totalRequestsCount = 0;
$openRequestsCount = 0;
$overdueRequestsCount = 0; // Layout matching placeholder metric
$notifications = [];

// Combined Counter Logic: Single structured query lookup to reduce server round trips
$countQuery = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status IN ('pending', 'processing') THEN 1 END) as open
    FROM service_requests 
    WHERE client_email = ?
");

if ($countQuery) {
    $countQuery->bind_param("s", $clientEmail);
    $countQuery->execute();
    $countResult = $countQuery->get_result()->fetch_assoc();
    $totalRequestsCount = $countResult['total'] ?? 0;
    $openRequestsCount = $countResult['open'] ?? 0;
    $countQuery->close();
}

// CRITICAL SAFETY GUARD: Re-verify that $clientName preserves your clean active session tracking token variables
if (!isset($clientName) || empty($clientName)) {
    $clientName = htmlspecialchars($_SESSION['name'] ?? 'Authorized Client');
}

// Optimized Notifications Pull Loop
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

// 5. Form Submission Handling Rule Blocks
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type'])) {
        if ($_POST['action_type'] === 'service_request') {
        $assetType = $_POST['asset_type'];
        $assetBrand = $_POST['asset_brand'];
        $assetId = trim(strtoupper($_POST['asset_id'])); // Forces uppercase tracking normalization
        $problemCategory = $_POST['problem_category'];
        $priority = $_POST['priority'];
        $phone = trim($_POST['phone']);
        $location = $_POST['location'];
        $paymentMethod = $_POST['payment_method'];
        
        // RULE CHECK A: Verify if this specific Asset ID is already tracking in your database row loop
        $checkAsset = $conn->prepare("SELECT id FROM service_requests WHERE asset_id = ?");
        $checkAsset->bind_param("s", $assetId);
        $checkAsset->execute();
        $checkAssetResult = $checkAsset->get_result();
        
        // RULE CHECK B: Verify if this specific Phone Number is already registered to a ticket
        $checkPhone = $conn->prepare("SELECT id FROM service_requests WHERE phone = ?");
        $checkPhone->bind_param("s", $phone);
        $checkPhone->execute();
        $checkPhoneResult = $checkPhone->get_result();
        
        if ($checkAssetResult->num_rows > 0) {
            echo "<script>alert('Validation Error: This Asset ID is already registered under an active service request log! Please verify your reference number.'); window.history.back();</script>";
            $checkAsset->close();
            $checkPhone->close();
            exit();
        } 
        elseif ($checkPhoneResult->num_rows > 0) {
            echo "<script>alert('Validation Error: This Contact Phone Number is already linked to an open service ticket row! Please use an alternate contact number.'); window.history.back();</script>";
            $checkAsset->close();
            $checkPhone->close();
            exit();
        } else {
            // Close safety checking streams cleanly
            $checkAsset->close();
            $checkPhone->close();
            
            // Proceed to standard database entry execution safely
            $insertStmt = $conn->prepare("INSERT INTO service_requests (client_email, asset_type, asset_brand, asset_id, problem_category, priority, phone, location, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $insertStmt->bind_param("sssssssss", $clientEmail, $assetType, $assetBrand, $assetId, $problemCategory, $priority, $phone, $location, $paymentMethod);
            
            if ($insertStmt->execute()) {
                echo "<script>alert('Service request created successfully!'); window.location.href='client-dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error processing request: " . $insertStmt->error . "');</script>";
            }
            $insertStmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Dashboard | ARN QuickFix</title>
  
      <!-- FIXED: Points to your actual local Bootstrap stylesheet file -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  
  <!-- FIXED: Points to your actual local custom style configurations file -->
  <!-- <link href="css/style.css" rel="stylesheet"> -->
  
  <!-- FontAwesome v6 asset vector icons CDN (Keep this for font symbols) -->
  <!-- <link href="https://cloudflare.com" rel="stylesheet"> -->
 

  
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
    .nav-user-label {
      font-size: 14px;
      font-weight: 500;
      color: #64748B;
    }
    .metric-card {
      background: #FFFFFF;
      border: 1px solid var(--border-gray);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .metric-title {
      font-size: 13px;
      color: #64748B;
      font-weight: 600;
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .metric-value {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-dark);
    }
    .dashboard-panel {
      background: #FFFFFF;
      border: 1px solid var(--border-gray);
      border-radius: 12px;
      padding: 30px;
      margin-bottom: 24px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .panel-heading {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--text-dark);
    }
    .form-control, .form-select {
      height: 48px;
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
      height: 48px;
      border-radius: 8px;
      font-weight: 600;
      width: 100%;
      transition: background-color 0.2s;
    }
    .btn-submit-cyan:hover {
      background-color: #00AEC6;
      color: #FFFFFF;
    }
    .btn-complaint-red {
      background-color: #EF4444;
      color: #FFFFFF;
      border: none;
      height: 48px;
      border-radius: 8px;
      font-weight: 600;
      width: 100%;
    }
    .btn-complaint-red:hover {
      background-color: #DC2626;
      color: #FFFFFF;
    }
    .action-icon-btn {
      background: none;
      border: none;
      color: #64748B;
      font-size: 18px;
      position: relative;
      padding: 5px;
    }
    .action-icon-btn:hover {
      color: var(--primary-cyan);
    }
    .notification-dot {
      position: absolute;
      top: 4px;
      right: 4px;
      width: 8px;
      height: 8px;
      background-color: #EF4444;
      border-radius: 50%;
    }
    .btn-outline-custom {
      border: 1px solid #CBD5E1;
      border-radius: 20px;
      padding: 6px 16px;
      font-size: 13px;
      color: #64748B;
      font-weight: 500;
      text-decoration: none;
      background-color: #FFFFFF;
    }
    .btn-outline-custom:hover {
      border-color: var(--primary-cyan);
      color: var(--primary-cyan);
    }
  </style>
</head>
<body>

  <!-- ================= NAV BAR HEADER (SINGLE CLEAN VERSION) ================= -->
  <!-- ================= MASTER DASHBOARD NAVIGATION BAR ================= -->
  <nav class="navbar dashboard-navbar d-flex align-items-center justify-content-between py-3 px-4 bg-white border-bottom">
    
    <!-- Left Section: Brand Logo and Title -->
    <div class="d-flex align-items-center gap-3">
      <a href="index.php" class="brand-accent text-decoration-none" style="color: var(--primary-cyan); font-weight: 700; font-size: 24px;">
      <!-- INSERTED BRAND LOGO MAP: Points cleanly to your local img directory structure folder -->
      <img src="img/logo.svg.svg" alt="ARN QuickFix Logo" style="height: 80px; width: auto; object-fit: contain;">  
      <i class="fa fa-tools me-2"></i>ARN QuickFix Ltd.
      </a>
      <!-- Changing border-start to border-start-4 thickens the line to 4px instantly -->
<span class="fs-4 fw-bold text-dark border-start border-start-4 ps-3" style="border-color: var(--border-gray) !important;">
  Client Dashboard
</span>

    </div>
    
    <!-- Right Section: Interactive Actions and Session Controls -->
    <div class="d-flex align-items-center gap-4">
      
      <span class="nav-user-label text-secondary small fw-semibold">
  Client: <strong class="text-dark fw-bold" style="font-weight: 700 !important;"><?php echo $clientName; ?></strong>
</span>

      <!-- Profile View Interface Controller Button -->
       <!-- Points directly to your new profile manager view layout page -->
<a href="client-profile.php" class="btn btn-outline-secondary rounded-pill px-3 py-1 fw-semibold small d-flex align-items-center gap-2" style="font-size: 13px;">
  <i class="fa fa-user-circle fs-6"></i> Profile
</a>
      <!-- Fully Terminated Session Exit Script Link -->
      <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" onclick="return confirm('Are you sure you want to log out?');" style="font-size: 13px;">
        <i class="fa fa-sign-out-alt me-1"></i> Logout
      </a>
      
    </div>
  </nav>



  <!-- ================= MASTER DASHBOARD BODY GRID CONTAINER ================= -->
  <div class="container py-4">
    
    <!-- Counters Summary Metric Grid Rows -->
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="metric-card"><div class="metric-title">Total Requests</div><div class="metric-value"><?php echo $totalRequestsCount; ?></div></div></div>
      <div class="col-md-4"><div class="metric-card"><div class="metric-title">Open Requests</div><div class="metric-value"><?php echo $openRequestsCount; ?></div></div></div>
      <div class="col-md-4"><div class="metric-card"><div class="metric-title">Overdue Maintenance</div><div class="metric-value text-danger"><?php echo $overdueRequestsCount; ?></div></div></div>
    </div>

    <!-- Main Content Form / Table Dynamic Split Grid Setup Row -->
    <div class="row g-4">
      <!-- LEFT HAND PANEL: Creation Matrix Form Panel Block -->
      <div class="col-lg-5">
        <div class="dashboard-panel">
          <div class="panel-heading">Create Service Request</div>
          <form action="client-dashboard.php" method="POST" onsubmit="return validateFormLayout(event)">
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
  <!-- ADDED: style="text-transform: uppercase;" to keep your database IDs looking clean and professional -->
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
                <input type="tel" name="phone" class="form-control" placeholder="Enter your Phone Number" required>
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
              <div class="col-12 mt-4"><button type="submit" class="btn btn-submit-cyan">Submit Request</button></div>
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
            <a href="all_requests.php" class="btn-outline-custom">View All Requests</a>
          </div>
          <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-size: 13.5px;">
              <!-- Table Headers: Boosted to font-weight 700 with high-contrast text color -->
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
            <a href="maintenance_details.php" class="btn-outline-custom">View Maintenance Details</a>
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
            <a href="generate_pdf.php?type=requests" target="_blank" class="btn btn-sm btn-light border px-3 py-2 text-secondary fw-semibold">Service Requests (PDF)</a>
            <a href="generate_pdf.php?type=maintenance" target="_blank" class="btn btn-sm btn-light border px-3 py-2 text-secondary fw-semibold">Maintenance Overview (PDF)</a>
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

  <!-- ================= POPUP BOX MODAL A: NOTIFICATIONS PORTAL VIEW ================= -->
  <!-- <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold"><i class="fa fa-envelope-open text-primary me-2"></i>Manager Notifications</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <?php if (!empty($notifications)): ?>
            <div class="list-group list-group-flush">
              <?php foreach ($notifications as $notif): ?>
                <div class="list-group-item px-0 py-3 border-bottom-0">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-bold text-dark"><i class="fa fa-user-tie me-1 text-secondary"></i> Operations Dispatcher</span>
                    <span class="small text-muted font-monospace"><?php echo htmlspecialchars($notif['created_at']); ?></span>
                  </div>
                  <p class="text-secondary small m-0 ps-3 border-start border-2 border-info"><?php echo htmlspecialchars($notif['message']); ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="fa fa-envelope-open-text fa-3x mb-2 text-secondary opacity-50"></i>
              <p class="small m-0">No new notification dispatches received from management loops.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div> -->

  <!-- ================= POPUP BOX MODAL B: PROFILE VALUES PORTAL SETTINGS ================= -->
  <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold"><i class="fa fa-id-card text-primary me-2"></i>Profile Identity Parameters</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded-circle border shadow-sm mb-2" style="width: 70px; height: 70px; font-size: 32px;">
              <i class="fa fa-user"></i>
            </div>
            <h4 class="fw-bold m-0 text-dark"><?php echo $clientName; ?></h4>
            <span class="badge bg-info text-capitalize mt-1 px-3 py-1"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Client'); ?> Account</span>
          </div>
          <div class="border rounded-3 p-3 bg-light">
            <div class="mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
              <span class="small text-secondary fw-semibold">Email Identity</span>
              <span class="small text-dark font-monospace fw-bold"><?php echo $clientEmail; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="small text-secondary fw-semibold">Connection Node</span>
              <span class="small text-muted font-monospace">Localhost DB Cluster v8.0</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    <!-- Bootstrap 5 JavaScript Bundle Layout Core Engine CDN Injection -->
  <!-- <script src="https://jsdelivr.net"></script> -->

  <!-- ================= MODERN CUSTOM TOAST POPUP NOTIFICATION CONTAINER ================= -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1100;">
  <div id="validationToast" class="toast align-items-center text-white bg-danger border-0 rounded-3 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
    <div class="d-flex p-3">
      <div class="toast-body d-flex align-items-center gap-2 font-monospace fw-bold" id="toastMessage" style="font-size: 14px;">
        <!-- Your custom error text message will be dynamically injected right here -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close" style="box-shadow: none;"></button>
    </div>
  </div>
</div>

  <!-- FIXED: Restored the official, working Bootstrap 5 compiled JavaScript engine link -->
  <!-- <script src="https://jsdelivr.net"></script> -->

  <!-- Dynamic Problem Category Menu Loader Script Logic -->
  <script>
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

    // DIRECTLY TARGETED FORM VALIDATION ENGINE
    function validateFormLayout(event) {
        const assetType = document.getElementById('asset_type').value;
        const assetId = document.getElementById('asset_id').value.trim().toUpperCase();
        
        const toastElement = document.getElementById('validationToast');
        const toastMessage = document.getElementById('toastMessage');
        const bsToast = new bootstrap.Toast(toastElement);

        // Pattern validation parameter evaluation strings check
        if (assetType === 'Elevator' && !assetId.startsWith('ELV')) {
            toastMessage.innerHTML = '<i class="fa fa-exclamation-triangle fs-5"></i> Error: Elevator Asset ID must strictly start with "ELV" (e.g., ELV-101)';
            bsToast.show();
            event.preventDefault(); // Direct system halt rule injection
            return false;
        } 
        else if (assetType === 'AC' && !assetId.startsWith('AC')) {
            toastMessage.innerHTML = '<i class="fa fa-exclamation-triangle fs-5"></i> Error: AC Asset ID must strictly start with "AC" (e.g., AC-202)';
            bsToast.show();
            event.preventDefault();
            return false;
        } 
        else if (assetType === 'Generator' && !assetId.startsWith('GEN')) {
            toastMessage.innerHTML = '<i class="fa fa-exclamation-triangle fs-5"></i> Error: Generator Asset ID must strictly start with "GEN" (e.g., GEN-303)';
            bsToast.show();
            event.preventDefault();
            return false;
        }
        
        return true; // Let form process smoothly if parameters pass checking rules
    }
  </script>

</body>
</html>
<?php 
// 6. Terminate Active Database Connection Thread Safely
if (isset($conn)) {
    $conn->close(); 
}
?>
