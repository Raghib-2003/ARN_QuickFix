<?php
// 1. Initialize Active User Session and Force Authorization Guard Rails
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Restrict access directly to Authorized Managers only
if (!isset($_SESSION['email']) || (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'manager')) {
    // If testing without session memory locally right now, you can keep this line commented out
    // header("Location: login.php");
    // exit();
}

// FIXED VARIABLE CAPTURE: Fallback cleanly to their actual email handle if their name hasn't been set yet
$managerEmail = $_SESSION['email'] ?? 'manager@arnquickfix.com';
$managerName = !empty($_SESSION['name']) ? $_SESSION['name'] : strstr($managerEmail, '@', true);


// 2. High-Performance Database Integration Connection
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "arn_quickfix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// 3. Dynamic Calculation Counters (Pulls live totals from MySQL)
$newRequestsCount = 0;
$inProgressCount = 0;
$overdueAlertsCount = 0;

$qNew = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE status IN ('pending', 'submitted')");
if ($qNew) { $newRequestsCount = $qNew->fetch_assoc()['total'] ?? 0; }

$qProgress = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE status = 'processing'");
if ($qProgress) { $inProgressCount = $qProgress->fetch_assoc()['total'] ?? 0; }

$qOverdue = $conn->query("SELECT COUNT(*) as total FROM maintenance_schedules WHERE status = 'Overdue'");
if ($qOverdue) { $overdueAlertsCount = $qOverdue->fetch_assoc()['total'] ?? 0; }
// Count Technician Updates (FIXED: Counts all active processing jobs on technician terminals)
$techUpdatesCount = 0;
$qTech = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE status = 'processing'");
if ($qTech) { 
    $techUpdatesCount = $qTech->fetch_assoc()['total'] ?? 0; 
}
// Count Total Completed Reports Statements 
$reportsCount = 0;
$qRep = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE status = 'completed'");
if ($qRep) { $reportsCount = $qRep->fetch_assoc()['total'] ?? 0; }

// Count Total Customer Complaint Tickets Registered
$complaintsCount = 0;
$qComp = $conn->query("SELECT COUNT(*) as total FROM complaints");
if ($qComp) { $complaintsCount = $qComp->fetch_assoc()['total'] ?? 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Dashboard Terminal | ARN QuickFix</title>
  
  <!-- CRITICAL VECTOR INTERLOCK: Pulls all FontAwesome 6 icon and arrow metrics instantly from the CDN -->
  <!-- <link rel="stylesheet" href="https://cloudflare.com"> -->

  
  <!-- FIXED FRAMEWORK LINK: Case-aligned mapping matching your lowercase rename selection -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">

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
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: var(--deep-navy);
      -webkit-font-smoothing: antialiased;
    }
    .manager-navbar {
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
    .brand-accent span {
      color: var(--primary-cyan);
    }
    
    /* Sleek Pill Badges Row Style */
    .btn-action-badge {
      font-size: 12px;
      font-weight: 700;
      padding: 8px 18px;
      border-radius: 30px;
      text-transform: uppercase;
      text-decoration: none;
      letter-spacing: 0.3px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .badge-cyan { background-color: #ECFEFF; color: #0891B2; border: 1px solid #CFFAFE; }
    .badge-cyan:hover { background-color: var(--primary-cyan); color: #FFF; transform: translateY(-1px); }
    .badge-slate { background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
    .badge-slate:hover { background-color: #64748B; color: #FFF; transform: translateY(-1px); }
    .badge-light { background-color: #FFFFFF; color: #0F172A; border: 1px solid #CBD5E1; }
    .badge-light:hover { border-color: var(--primary-cyan); color: var(--primary-cyan); transform: translateY(-1px); }
    .badge-red { background-color: #FEF2F2; color: #EF4444; border: 1px solid #FEE2E2; }
    .badge-red:hover { background-color: #EF4444; color: #FFF; transform: translateY(-1px); }

    /* Counter Metric Cards Redesign */
    .metric-container-card {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 16px;
      padding: 28px;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.015);
      position: relative;
      overflow: hidden;
      transition: all 0.25s ease;
    }
    .metric-container-card:hover {
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.03);
    }
    .metric-icon-box {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 20px;
    }
    .bg-light-cyan { background-color: #ECFEFF; color: var(--primary-cyan); }
    .bg-light-blue { background-color: #EFF6FF; color: #3B82F6; }
    .bg-light-red { background-color: #FEF2F2; color: #EF4444; }
    
    .metric-card-title {
      font-size: 15px;
      font-weight: 700;
      color: var(--slate-gray);
      margin-bottom: 4px;
    }
    .metric-card-number {
      font-size: 38px;
      font-weight: 800;
      color: var(--deep-navy);
      line-height: 1;
      margin-bottom: 8px;
    }
    .metric-card-desc {
      font-size: 12.5px;
      color: #64748B;
      font-weight: 500;
      margin-bottom: 20px;
      min-height: 36px;
    }
    .metric-card-action {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 700;
      color: var(--deep-navy);
      text-decoration: none;
      padding: 6px 14px;
      background-color: #F8FAFC;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .metric-container-card:hover .metric-card-action {
      background-color: var(--deep-navy);
      color: #FFFFFF;
    }

    /* Central Modern Workspace Matrix Panels */
    .master-navigation-panel {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 20px;
      padding: 35px 30px;
      height: 100%;
      display: flex;
      flex-direction: column;
      text-decoration: none;
      color: inherit;
      box-shadow: 0 4px 15px rgba(15, 23, 42, 0.01);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }
    .master-navigation-panel:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 30px rgba(15, 23, 42, 0.04);
      border-color: var(--primary-cyan);
    }
    .panel-visual-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    .panel-badge-icon {
      width: 54px;
      height: 54px;
      border-radius: 14px;
      background-color: #F8FAFC;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: var(--deep-navy);
      transition: all 0.25s;
    }
    .master-navigation-panel:hover .panel-badge-icon {
      background-color: #ECFEFF;
      color: var(--primary-cyan);
    }
    .panel-arrow {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: #F1F5F9;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      color: var(--slate-gray);
      transition: all 0.25s;
    }
    .master-navigation-panel:hover .panel-arrow {
      background-color: var(--primary-cyan);
      color: #FFFFFF;
      transform: rotate(-45deg);
    }
    .panel-main-heading {
      font-size: 21px;
      font-weight: 800;
      color: var(--deep-navy);
      margin-bottom: 8px;
      letter-spacing: -0.3px;
    }
    .panel-summary-text {
      font-size: 13.5px;
      color: var(--slate-gray);
      font-weight: 500;
      line-height: 1.5;
      margin: 0;
    }
    .transition-panel {
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    /* Creates an elite card rise and shadows expand when mouse hovers over panels */
    .transition-panel:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 25px rgba(15, 23, 42, 0.06) !important;
      border-color: #00C2CB !important;
    }
    /* Causes the integrated right arrows to shift forward and switch color automatically */
    .transition-panel:hover .panel-arrow-indicator {
      background-color: #00C2CB !important;
      color: #FFFFFF !important;
      transform: translateX(3px) rotate(-45deg);
    }
  </style>
</head>
<body>

  <!-- ================= TOP EXECUTIVES NAVIGATION BAR ================= -->
  <nav class="navbar manager-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <a href="index.php" class="brand-accent d-flex align-items-center gap-2">
        <img src="img/logo.svg.svg" alt="Logo" style="height: 60px; width: auto;" onerror="this.style.display='none';">
        <span>ARN <span>QuickFix Ltd.</span></span>
      </a>
      <span class="badge bg-dark rounded-pill font-monospace px-3 py-1.5 ms-2" style="font-size: 11px; letter-spacing: 0.5px;"></span>
    </div>
    
    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2 me-2 bg-light px-3 py-1.5 rounded-pill border" style="border-color: #E2E8F0 !important;">
        <!-- Pulsing Active Connection Node Dot -->
        <div style="width: 8px; height: 8px; background-color: #10B981;" class="rounded-circle"></div>
        <span class="small fw-semibold text-secondary" style="font-size: 13px;">
          Manager: <strong class="text-dark fw-bold"><?php echo $managerName; ?></strong>
        </span>
      </div>
      <!-- REQUIRED ATTRIBUTES: Check that data-bs-toggle and data-bs-target match your modal exactly -->
<a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold" onclick="return confirm('Are you sure you want to log out?');" style="font-size:12px;">Logout</a>
    </div>

    </div>
  </nav>

  <!-- ================= CENTRAL DESKTOP SHEET WRAPPER ================= -->
    <!-- ================= MASTER DESIGN DASHBOARD CANVASES LAYER ================= -->
  <div class="container py-5" style="max-width: 1140px;">
    
    <!-- Headline Section Header (WITH INTEGRATED LINK BUTTONS) -->
    <div class="d-flex justify-content-between align-items-center mb-5 pb-3" style="border-bottom: 2px solid #E2E8F0;">
      <div>
        <h2 class="fw-bold m-0" style="font-size: 28px; color: #0F172A; letter-spacing: -0.5px;">Manager Dashboard</h2>
        <p class="text-muted m-0 small fw-medium mt-1">Review operational incoming traffic metrics, assign engineers, and manage catalog pipelines.</p>
      </div>
      
      <!-- Premium Quick Action Buttons (Pill Shaped With Hover Transition Effects) -->
      <div class="d-flex gap-2">
       <a href="technician_updates.php" class="btn btn-sm px-2 py-1.5 fw-bold text-uppercase rounded-pill" style="font-size: 10px; background-color: #ECFEFF; color: #0891B2; border: 1px solid #CFFAFE; text-decoration: none; white-space: nowrap;">
          Tech Updates <span class="badge rounded-pill text-white" style="font-size: 9px; padding: 2px 5px; margin-left: 2px; background-color: #0891B2 !important; font-family: sans-serif; vertical-align: middle;"><?php echo $techUpdatesCount; ?></span>
        </a>
        <a href="manager_reports.php" class="btn btn-sm px-2 py-1.5 fw-bold text-uppercase rounded-pill" style="font-size: 10px; background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; text-decoration: none; white-space: nowrap;">
          Reports <span class="badge rounded-pill text-white" style="font-size: 9px; padding: 2px 5px; margin-left: 2px; background-color: #475569 !important; font-family: sans-serif; vertical-align: middle;"><?php echo $reportsCount; ?></span>
        </a>
        <a href="manager_complaints.php" class="btn btn-sm px-2 py-1.5 fw-bold text-uppercase rounded-pill" style="font-size: 10px; background-color: #FEF2F2; color: #EF4444; border: 1px solid #FEE2E2; text-decoration: none; white-space: nowrap;">
          Complaints <span class="badge rounded-pill text-white" style="font-size: 9px; padding: 2px 5px; margin-left: 2px; background-color: #EF4444 !important; font-family: sans-serif; vertical-align: middle;"><?php echo $complaintsCount; ?></span>
        </a>         
        <a href="manager-profile.php" class="btn btn-sm px-3 py-2 fw-bold text-uppercase rounded-pill" style="font-size: 11px; background-color: #FFFFFF; color: #0F172A; border: 1px solid #CBD5E1;">Profile</a>
      </div>
    </div>

    <!-- ================= SECTION A: UPPER COUNTER METRICS CARDS (WITH INTEGRATED ICON BOXES) ================= -->
    <div class="row g-4 mb-5">
      
      <!-- Box 1: New Requests Tracker Card -->
          <!-- ================= SECTION A: UPPER COUNTER METRICS CARDS (EASY VIEW) ================= -->
    <div class="row g-4 mb-5">
      
      <!-- Box 1: New Requests Tracker Card -->
      <div class="col-md-4">
        <div class="p-4 bg-white border rounded-4 shadow-sm h-100" style="border-color: #E2E8F0 !important;">
          <!-- UNIVERSAL SYSTEM EMOJI ICON: Works instantly offline without any CSS links -->
          <div class="d-flex align-items-center justify-content-center mb-3 rounded-3" style="width: 46px; height: 46px; background-color: #ECFEFF; font-size: 22px;">
            📩
          </div>
          <div class="fw-bold small mb-1" style="color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">New Requests</div>
          <div class="fw-bold mb-2" style="font-size: 36px; color: #0F172A; line-height: 1;"><?php echo $newRequestsCount; ?></div>
          <p class="text-secondary small mb-4" style="min-height: 36px; font-size: 12.5px; line-height: 1.5;">New client issue complaints queued and pending supervisor diagnostic review.</p>
          <a href="manage_requests.php?filter=new" class="btn btn-sm w-100 fw-bold d-flex align-items-center justify-content-between px-3 py-2 rounded-3" style="background-color: #F8FAFC; color: #1E293B; border: 1px solid #E2E8F0; font-size: 12px; text-decoration: none;">
            <span>View Submitted Requests</span>
            <!-- UNIVERSAL ARROW EMBED: Renders explicitly on any screen space -->
            <span style="font-weight: bold; font-family: monospace; color: #94A3B8;">➔</span>
          </a>
        </div>
      </div>

      <!-- Box 2: In Progress Tracker Card -->
      <div class="col-md-4">
        <div class="p-4 bg-white border rounded-4 shadow-sm h-100" style="border-color: #E2E8F0 !important;">
          <div class="d-flex align-items-center justify-content-center mb-3 rounded-3" style="width: 46px; height: 46px; background-color: #EFF6FF; font-size: 22px;">
            🛠️
          </div>
          <div class="fw-bold small mb-1" style="color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">In Progress</div>
          <div class="fw-bold mb-2" style="font-size: 36px; color: #0F172A; line-height: 1;"><?php echo $inProgressCount; ?></div>
          <p class="text-secondary small mb-4" style="min-height: 36px; font-size: 12.5px; line-height: 1.5;">Service logs currently handled and open on technician field terminals.</p>
          <a href="manage_requests.php?filter=progress" class="btn btn-sm w-100 fw-bold d-flex align-items-center justify-content-between px-3 py-2 rounded-3" style="background-color: #F8FAFC; color: #1E293B; border: 1px solid #E2E8F0; font-size: 12px; text-decoration: none;">
            <span>View Active Work</span>
            <span style="font-weight: bold; font-family: monospace; color: #94A3B8;">➔</span>
          </a>
        </div>
      </div>

      <!-- Box 3: Overdue Alerts Tracker Card -->
      <div class="col-md-4">
        <div class="p-4 bg-white border rounded-4 shadow-sm h-100" style="border-color: #E2E8F0 !important;">
          <div class="d-flex align-items-center justify-content-center mb-3 rounded-3" style="width: 46px; height: 46px; background-color: #FEF2F2; font-size: 22px;">
            ⚠️
          </div>
          <div class="fw-bold small mb-1" style="color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Overdue Alerts</div>
          <div class="fw-bold mb-2 text-danger" style="font-size: 36px; line-height: 1;"><?php echo $overdueAlertsCount; ?></div>
          <p class="text-secondary small mb-4" style="min-height: 36px; font-size: 12.5px; line-height: 1.5;">Scheduled check intervals and calibrations missed by regional field crews.</p>
          <a href="overdue_maintenance.php" class="btn btn-sm w-100 fw-bold d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-danger" style="background-color: #FEF2F2; border: 1px solid #FEE2E2; font-size: 12px; text-decoration: none;">
            <span>Open Maintenance</span>
            <span style="font-weight: bold; font-family: monospace;">➔</span>
          </a>
        </div>
      </div>

    </div>


    <!-- ================= SECTION B: CENTRAL NAVIGATION WORKSPACE MATRIX PANELS ================= -->
        <!-- ================= SECTION B: CENTRAL NAVIGATION WORKSPACE MATRIX PANELS (EASY VIEW) ================= -->
    <div class="row g-4">
      
      <!-- Card 1: All Service Requests Operational Hub Link -->
      <div class="col-md-4">
        <a href="manager_all_requests.php" class="d-flex flex-column p-4 bg-white border rounded-4 text-decoration-none h-100 transition-panel" style="border-color: #E2E8F0; color: inherit;">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- NATIVE EMOJI ICON: Renders instantly everywhere -->
            <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 50px; height: 50px; font-size: 24px; background-color: #F8FAFC !important;">
              📁
            </div>
            <!-- Dynamic Right-Top Navigation Arrow Circle (Using a clean monospace text arrow) -->
            <div class="d-flex align-items-center justify-content-center rounded-circle panel-arrow-indicator" style="width: 30px; height: 30px; background-color: #F1F5F9; color: #64748B; font-weight: bold; font-family: monospace; font-size: 14px;">
              ➔
            </div>
          </div>
          <h4 class="fw-bold mb-2 text-dark" style="font-size: 20px; letter-spacing: -0.3px;">All Service Requests</h4>
          <p class="text-secondary small m-0 fw-medium" style="font-size: 13px; line-height: 1.5;">Review customer technical diagnostics reports, assign engineers, and monitor live status tracks.</p>
        </a>
      </div>

      <!-- Card 2: Inventory Tracking Hub Link -->
      <div class="col-md-4">
        <a href="manager_inventory.php" class="d-flex flex-column p-4 bg-white border rounded-4 text-decoration-none h-100 transition-panel" style="border-color: #E2E8F0; color: inherit;">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 50px; height: 50px; font-size: 24px; background-color: #F8FAFC !important;">
              📦
            </div>
            <div class="d-flex align-items-center justify-content-center rounded-circle panel-arrow-indicator" style="width: 30px; height: 30px; background-color: #F1F5F9; color: #64748B; font-weight: bold; font-family: monospace; font-size: 14px;">
              ➔
            </div>
          </div>
          <h4 class="fw-bold mb-2 text-dark" style="font-size: 20px; letter-spacing: -0.3px;">Inventory</h4>
          <p class="text-secondary small m-0 fw-medium" style="font-size: 13px; line-height: 1.5;">View parts, replacement components availability metrics, and adjust baseline machinery prices.</p>
        </a>
      </div>

      <!-- Card 3: Analytics, Reports & Metrics Link -->
      <div class="col-md-4">
        <a href="manager_metrics.php" class="d-flex flex-column p-4 bg-white border rounded-4 text-decoration-none h-100 transition-panel" style="border-color: #E2E8F0; color: inherit;">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 50px; height: 50px; font-size: 24px; background-color: #F8FAFC !important;">
              📈
            </div>
            <div class="d-flex align-items-center justify-content-center rounded-circle panel-arrow-indicator" style="width: 30px; height: 30px; background-color: #F1F5F9; color: #64748B; font-weight: bold; font-family: monospace; font-size: 14px;">
              ➔
            </div>
          </div>
          <h4 class="fw-bold mb-2 text-dark" style="font-size: 20px; letter-spacing: -0.3px;">Reports & Metrics</h4>
          <p class="text-secondary small m-0 fw-medium" style="font-size: 13px; line-height: 1.5;">Analyze core resolution metrics, structural timeline diagnostics trends, and crew efficiency scales.</p>
        </a>
      </div>
    </div> <!-- Closes Section B card layout row container -->
  </div> <!-- Closes the central workspace central grid sheet wrapper -->

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
