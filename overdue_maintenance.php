<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force authorization checks for managers strictly
if (!isset($_SESSION['email']) || (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'manager')) {
    header("Location: login.php");
    exit();
}

$managerEmail = $_SESSION['email'];
$managerName = $_SESSION['name'] ?? 'Operations Manager';

// Establish Database Connection
$conn = new mysqli("127.0.0.1", "root", "", "arn_quickfix");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// --------------------------------------------------------------------
// FORM PROCESSING: HANDLE ADD NEW MAINTENANCE SCHEDULE
// --------------------------------------------------------------------
$actionMessage = "";
$actionError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_type'])) {
    if ($_POST['action_type'] === 'add_schedule') {
        $client_email = trim($_POST['client_email']);
        $asset_type = $_POST['asset_type'] ?? '';
        $asset_id = trim($_POST['asset_id']);
        $last_service = $_POST['last_service'] ?? '';
        $next_due = $_POST['next_due'] ?? '';
        $maintenance_type = $_POST['maintenance_type'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        
        // Fetch client phone number matching their email node to keep synchronization integrity
        $phoneStmt = $conn->prepare("SELECT phone, name FROM users WHERE email = ?");
        $phoneStmt->bind_param("s", $client_email);
        $phoneStmt->execute();
        $clientProfile = $phoneStmt->get_result()->fetch_assoc();
        $phoneStmt->close();
        
        $client_phone = $clientProfile['phone'] ?? '01234567899';
        $client_name = $clientProfile['name'] ?? 'Client';

        if (!empty($client_email) && !empty($asset_id) && !empty($next_due)) {
            $insertStmt = $conn->prepare("INSERT INTO maintenance_schedules (client_email, client_name, phone, asset_type, asset_id, last_service, next_due, maintenance_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssssssss", $client_email, $client_name, $client_phone, $asset_type, $asset_id, $last_service, $next_due, $maintenance_type, $status);
            
            if ($insertStmt->execute()) {
                $actionMessage = "Success! Maintenance task logged and published onto client portal lanes.";
            } else {
                $actionError = "Database Error: Could not save schedule parameters.";
            }
            $insertStmt->close();
        } else {
            $actionError = "Validation Error: Please fill in all mandatory form input variables.";
        }
    }
}

// --------------------------------------------------------------------
// CALCULATE UPPER VIEW COMPONENT SUMS (DYNAMIC COUNTERS)
// --------------------------------------------------------------------
$totalScheduled = 0;
$totalOverdue = 0;

$qSched = $conn->query("SELECT COUNT(*) as total FROM maintenance_schedules");
if ($qSched) { $totalScheduled = $qSched->fetch_assoc()['total'] ?? 0; }

$qOver = $conn->query("SELECT COUNT(*) as total FROM maintenance_schedules WHERE status = 'Overdue'");
if ($qOver) { $totalOverdue = $qOver->fetch_assoc()['total'] ?? 0; }

// Fetch clients to populate select dropdown input field
$clientsList = $conn->query("SELECT email, name FROM users WHERE role = 'client' OR role = 'user' ORDER BY name ASC");

// Fetch active schedules record ledger
$schedulesLedger = $conn->query("SELECT * FROM maintenance_schedules ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maintenance Schedule Control Hub | ARN QuickFix Ltd.</title>
  
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
    .manager-navbar {
      background-color: #FFFFFF;
      border-bottom: 1px solid var(--border-light);
      padding: 16px 45px;
    }
    .brand-accent {
      font-weight: 800;
      font-size: 24px;
      color: var(--deep-navy);
      text-decoration: none;
    }
    .brand-accent span { color: var(--primary-cyan); }
    
    .figma-card-layout {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 4px 15px rgba(15, 23, 42, 0.01);
    }
    .metric-box-sub {
      border: 1px solid var(--border-light);
      border-radius: 12px;
      background: #FFFFFF;
      padding: 20px;
      text-align: left;
    }
    .metric-value-large {
      font-size: 32px;
      font-weight: 800;
      color: var(--deep-navy);
      line-height: 1;
    }
    .form-label-custom {
      font-size: 12px;
      font-weight: 700;
      color: var(--slate-gray);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .form-control-custom, .form-select-custom {
      height: 44px;
      background-color: #F8FAFC;
      border: 1px solid #CBD5E1;
      border-radius: 8px;
      font-size: 13.5px;
      color: var(--deep-navy);
      padding: 10px 14px;
    }
    .form-control-custom:focus, .form-select-custom:focus {
      border-color: var(--primary-cyan);
      box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.12);
      background-color: #FFFFFF;
    }
    .btn-save-schedule {
      background-color: var(--primary-cyan);
      color: #FFFFFF;
      font-weight: 700;
      font-size: 13px;
      height: 44px;
      border: none;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .btn-save-schedule:hover { background-color: #00AEC6; transform: translateY(-1px); }
    
    .status-badge-pill {
      font-size: 11px;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
      text-transform: uppercase;
    }
    .status-overdue { background-color: #FEF2F2; color: #EF4444; border: 1px solid #FEE2E2; }
    .status-active { background-color: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; }
    .status-completed { background-color: #F0FDF4; color: #16A34A; border: 1px solid #DCFCE7; }
  </style>
  
  <script>
    // Module: Dynamic Dropdown Asset ID String Prefixer interlock matching your system rules
    function updateAssetIdPlaceholder() {
        const assetType = document.getElementById('asset_type').value;
        const assetIdField = document.getElementById('asset_id');
        if (assetType === 'Elevator') { assetIdField.placeholder = "e.g., ELV-9-C"; }
        else if (assetType === 'AC') { assetIdField.placeholder = "e.g., AC-202"; }
        else if (assetType === 'Generator') { assetIdField.placeholder = "e.g., GEN-303"; }
    }
  </script>
</head>
<body>

  <!-- ================= TOP NAVIGATION BAR ================= -->
  <nav class="navbar manager-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <a href="manager-dashboard.php" class="brand-accent d-flex align-items-center gap-2">
        <img src="img/logo.svg.svg" alt="Logo" style="height: 35px; width: auto;" onerror="this.style.display='none';">
        <span>ARN <span>QuickFix Ltd.</span></span>
      </a>
    </div>
    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2 bg-light px-3 py-1.5 rounded-pill border" style="border-color: #E2E8F0 !important;">
        <div style="width: 8px; height: 8px; background-color: #10B981;" class="rounded-circle"></div>
        <span class="small fw-semibold text-secondary" style="font-size: 13px;">
          Manager: <strong class="text-dark fw-bold"><?php echo htmlspecialchars($managerName); ?></strong>
        </span>
      </div>
      <a href="manager-dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-bold" style="font-size: 12.5px; height: 34px; display: flex; align-items: center;">Back to Hub</a>
    </div>
  </nav>

  <!-- ================= MASTER CANVAS WORKING SHEET CONTAINER ================= -->
  <div class="container py-5" style="max-width: 1140px;">
    
    <!-- Header Labels Row -->
    <div class="mb-4">
      <h2 class="fw-bold m-0" style="font-size: 26px; letter-spacing: -0.5px;">Maintenance Schedule</h2>
      <p class="text-muted m-0 small fw-medium mt-1">Manager assigns periodic preventative maintenance operations. Clients trace entries live inside portals.</p>
    </div>

    <!-- Alert Messaging Row -->
    <?php if (!empty($actionMessage)): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #10B981 !important; font-size:13.5px; color:#065F46;">
        🎉 <?php echo $actionMessage; ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($actionError)): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-4 fw-bold font-monospace" style="border-left: 5px solid #EF4444 !important; font-size:13.5px; color:#991B1B;">
        ⚠️ Operational Limit: <?php echo $actionError; ?>
      </div>
    <?php endif; ?>

    <!-- ================= SECTION A: METRIC WIDGETS ROW (MATCHES FIGMA) ================= -->
    <div class="row g-4 mb-5">
      <div class="col-md-6">
        <div class="metric-box-sub">
          <div class="small fw-bold text-secondary text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Total Scheduled</div>
          <div class="metric-value-large"><?php echo $totalScheduled; ?></div>
        </div>
      </div>
      
      <!-- Box 2: Overdue Alert Count Metric Card -->
      <div class="col-md-6">
        <div class="metric-box-sub" style="border-left: 3px solid #EF4444 !important;">
          <div class="small fw-bold text-danger text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Overdue</div>
          <div class="metric-value-large text-danger"><?php echo $totalOverdue; ?></div>
        </div>
      </div>
    </div> <!-- Close Section A Metric Row -->

    <!-- ================= SECTION B: ADD MAINTENANCE CARD VIEW PANEL ================= -->
    <div class="figma-card-layout mb-5">
      <h4 class="fw-bold mb-4 text-dark" style="font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Add Maintenance</h4>
      
      <form action="overdue_maintenance.php" method="POST">
        <input type="hidden" name="action_type" value="add_schedule">
        
        <div class="row g-3">
          <!-- Input 1: Client Email Target Selector Dropdown -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Client Target Email</label>
            <select name="client_email" class="form-select form-select-custom w-100" required>
              <option value="" disabled selected hidden>Select Client Node</option>
              <?php if ($clientsList && $clientsList->num_rows > 0): ?>
                <?php while ($c = $clientsList->fetch_assoc()): ?>
                  <option value="<?php echo htmlspecialchars($c['email']); ?>"><?php echo htmlspecialchars($c['name'] . " (" . $c['email'] . ")"); ?></option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </div>

          <!-- Input 2: Asset Machinery Classification Selector -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Asset Type</label>
            <select name="asset_type" id="asset_type" class="form-select form-select-custom w-100" onchange="updateAssetIdPlaceholder()" required>
              <option value="" disabled selected hidden>Select Asset Type</option>
              <option value="Elevator">Elevator (Vertical Transport)</option>
              <option value="AC">Air Conditioner (HVAC)</option>
              <option value="Generator">Power Generator (Grid Fallback)</option>
            </select>
          </div>

          <!-- Input 3: Asset Identification Prefix Field -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Asset ID</label>
            <input type="text" name="asset_id" id="asset_id" class="form-control form-control-custom" placeholder="Select Asset Type First" required>
          </div>

          <!-- Input 4: Last Serviced Log Calendar Date Picker -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Last Service Date</label>
            <input type="date" name="last_service" class="form-control form-control-custom">
          </div>

          <!-- Input 5: Next Calibration Threshold Target Date Picker -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Next Due Date</label>
            <input type="date" name="next_due" class="form-control form-control-custom" required>
          </div>

          <!-- Input 6: Operation Interval Window Option Selector Menu -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Maintenance Type</label>
            <select name="maintenance_type" class="form-select form-select-custom w-100" required>
              <option value="" disabled selected hidden>Select Maintenance Interval</option>
              <option value="Monthly">Monthly Cycle Servicing</option>
              <option value="Quarterly">Quarterly System Optimization</option>
              <option value="Half-Yearly">Half-Yearly Deep Diagnostics</option>
              <option value="Annually">Annual Structural Overhaul</option>
            </select>
          </div>

          <!-- Input 7: State Status Flag Selector Menu -->
          <div class="col-md-4">
            <label class="form-label form-label-custom">Initial Status</label>
            <select name="status" class="form-select form-select-custom w-100">
              <option value="Active" selected>Active / In Queue</option>
              <option value="Overdue">Overdue / Delayed Action</option>
              <option value="Completed">Completed / Closed Log</option>
            </select>
          </div>

          <!-- Form Submit Button Box Container Alignment -->
          <div class="col-md-8 d-flex align-items-end justify-content-end">
            <button type="submit" class="btn btn-save-schedule w-100 fw-bold text-uppercase mt-4 mt-md-0" style="max-width: 280px; background-color: var(--primary-cyan); color: #FFFFFF;">Save Schedule</button>
          </div>
        </div>
      </form>
    </div>

    <!-- ================= SECTION C: ACTIVE SCHEDULES DATA GRID LISTBOARD ================= -->
    <div class="figma-card-layout">
      <div class="table-responsive">
        <table class="table align-middle m-0">
          <thead>
            <tr>
              <th style="width: 55px; text-align: center;">SL</th>
              <th>Client Information</th>
              <th>Machinery Specs</th>
              <th>Service Logs Calendar</th>
              <th>Operational Cycle</th>
              <th style="width: 140px; text-align: center;">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($schedulesLedger && $schedulesLedger->num_rows > 0): 
              $serialNumberCounter = 1;
              while ($row = $schedulesLedger->fetch_assoc()):
                $statusClass = 'status-active';
                if (isset($row['status']) && strtolower($row['status']) === 'overdue') { $statusClass = 'status-overdue'; }
                elseif (isset($row['status']) && strtolower($row['status']) === 'completed') { $statusClass = 'status-completed'; }
            ?>
              <tr>
                <!-- Clean Serial Incremental Counting Line Index Row (SL) -->
                <td class="font-monospace fw-bold text-secondary text-center"><?php echo $serialNumberCounter++; ?></td>
                
                <td>
                  <span class="d-block fw-bold text-dark mb-0.5"><?php echo htmlspecialchars($row['client_name'] ?? 'Client User'); ?></span>
                  <span class="text-muted font-monospace small d-block" style="font-size: 11.5px;"><?php echo htmlspecialchars($row['phone'] ?? '01XXXXXXXXX'); ?></span>
                </td>
                
                <td>
                  <span class="d-block text-dark fw-bold mb-0.5"><?php echo htmlspecialchars($row['asset_type'] ?? 'Asset'); ?></span>
                  <span class="badge bg-light text-dark font-monospace border" style="font-size: 11px;">ID: <strong><?php echo htmlspecialchars($row['asset_id'] ?? 'N/A'); ?></strong></span>
                </td>
                
                <td>
                  <span class="small d-block text-secondary mb-1" style="font-size: 12.5px;">Last: <strong class="text-dark font-monospace"><?php echo (!empty($row['last_service']) && $row['last_service'] !== '0000-00-00') ? date('d-m-Y', strtotime($row['last_service'])) : 'None Recorded'; ?></strong></span>
                  <span class="small d-block text-secondary" style="font-size: 12.5px;">Next: <strong class="text-danger font-monospace fw-bold"><?php echo date('d-m-Y', strtotime($row['next_due'])); ?></strong></span>
                </td>
                
                <td>
                  <span class="fw-semibold text-dark" style="font-size: 13px;"><i class="fa fa-rotate me-1 text-muted"></i> <?php echo htmlspecialchars($row['maintenance_type'] ?? 'Periodic'); ?></span>
                </td>
                
                <td class="text-center">
                  <span class="status-badge-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status'] ?? 'Active'); ?></span>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Fallback state if database has zero historical maintenance entries -->
            <tr>
              <td colspan="6" class="text-center py-5 text-muted font-monospace fw-bold" style="background-color: #FFFFFF;">
                🍃 Zero maintenance parameters are currently registered inside your database cluster grid.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div> <!-- Close Master Canvas Layout Wrapper Container Container -->

  <!-- Framework Compiled Engine Injector Libraries -->
  <script src="https://jsdelivr.net"></script>
</body>
</html>
<?php 
// Terminate your database integration connection thread cleanly on page exit
if (isset($conn)) { 
    $conn->close(); 
} 
?>