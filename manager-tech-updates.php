<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Intercepts the clear click, updates the session memory safely, and returns to dashboard
if (isset($_POST['action_type']) && $_POST['action_type'] === 'mark_all_tech_read') {
    $_SESSION['tech_muted_until'] = date('Y-m-d H:i:s');
    header("Location: manager-dashboard.php");
    exit();
}
// ... (Your existing database connection and query lines continue exactly as before)


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

// Connect to Database
$conn = new mysqli("127.0.0.1", "root", "", "arn_quickfix");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// Pull active processing/completed ticket rows where a technician has taken field action
// FIXED QUERY: Fetches your inventory parameters right out of your main service requests table
$techFeed = $conn->query("SELECT id, asset_id, asset_type, asset_brand, location, allocated_part, part_price, status, created_at AS updated_at FROM service_requests WHERE status IN ('processing', 'completed') ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technician Updates | ARN QuickFix Ltd.</title>
  
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
    
    .feed-container-card {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 20px;
      padding: 35px;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.015);
    }
    .log-row-card {
      border: 1px solid var(--border-light);
      border-radius: 12px;
      transition: all 0.2s ease;
    }
    .log-row-card:hover {
      transform: translateX(4px);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.015);
    }
    .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      display: inline-block;
    }
    .pulse-active { background-color: #2563EB; animation: pulse-blink 1.5s infinite; }
    .pulse-complete { background-color: #10B981; }
    @keyframes pulse-blink {
      50% { opacity: 0.4; }
    }
  </style>
</head>
<body>

  <!-- ================= TOP NAVIGATION BAR ================= -->
  <nav class="navbar manager-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <a href="manager-dashboard.php" class="brand-accent d-flex align-items-center gap-2">
        <img src="img/logo.svg.svg" alt="Logo" style="height: 55px; width: auto;" onerror="this.style.display='none';">
        <span>  ARN QuickFix Ltd. </span>
      </a> 
    </div>
    
    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2 me-2 bg-light px-3 py-1.5 rounded-pill border" style="border-color: #E2E8F0 !important;">
        <div style="width: 8px; height: 8px; background-color: #10B981;" class="rounded-circle"></div>
        <span class="small fw-semibold text-secondary" style="font-size: 13px;">
          Manager: <strong class="text-dark fw-bold"><?php echo htmlspecialchars($managerName); ?></strong>
        </span>
      </div>
      <a href="manager-dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-bold" style="font-size: 12.5px; height: 34px; display: flex; align-items: center;">Back to Hub</a>
    </div>
  </nav>

  <!-- ================= MASTER TELEMETRY FEED CANVAS ================= -->
  <div class="container py-5" style="max-width: 900px;">
    
    <!-- Header Content Row -->
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="fw-bold m-0" style="font-size: 26px; letter-spacing: -0.5px;">Live Technician Field Stream</h2>
        <p class="text-muted m-0 small fw-medium mt-1">Real-time tactical audit of operations updates, site arrivals, and repair logs directly from field crews.</p>
      </div>
          <!-- Header Content Row -->
    <div class="d-flex justify-content-between align-items-end mb-4">
    
      
      <!-- ================= CLEAN DEDICATED MARK ALL READ ACTION BUTTON ================= -->
      <form action="manager-tech-updates.php" method="POST" class="m-0">
        <input type="hidden" name="action_type" value="mark_all_tech_read">
        <button type="submit" class="btn btn-sm px-3 fw-bold rounded-pill text-uppercase d-flex align-items-center gap-1.5" 
                style="font-size: 11px; height: 34px; background-color: #ECFEFF; color: #0891B2; border: 1px solid #CFFAFE; transition: all 0.2s;">
          <i class="fa-solid fa-check-double"></i> Mark All Read
        </button>
      </form>
    </div>

    </div>

    <!-- ================= STREAM CARDS TIMELINE Repositories ================= -->
    <div class="feed-container-card">
      <div class="d-flex flex-column gap-3.5">
                <?php
        if ($techFeed && $techFeed->num_rows > 0):
            while ($tLog = $techFeed->fetch_assoc()):
                $isDone = ($tLog['status'] === 'completed');
                $feedBg = $isDone ? '#F0FDF4' : '#EFF6FF';
                $feedBorder = $isDone ? '#DCFCE7' : '#BFDBFE';
                $dotClass = $isDone ? 'pulse-complete' : 'pulse-active';
                $statusTextLabel = $isDone ? 'Servicing Complete' : 'Active Deployment';
                
                // Extract technician name from location string pattern mapping
                $locStr = $tLog['location'] ?? '';
                $techEngineer = "Field Crew Engineer";
                if (preg_match('/\(Assigned to:\s*([^)]+)\)/', $locStr, $matches)) {
                    $techEngineer = trim($matches[1]);
                }
        ?>
          <!-- Individual Feed Row Card (FIXED TAG LAYOUT STRUCTURE) -->
          <div class="p-4 log-row-card mb-3" style="background-color: <?php echo $feedBg; ?>; border-color: <?php echo $feedBorder; ?>; border-radius: 12px; border-style: solid; border-width: 1px;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="d-flex align-items-center gap-2">
                <span class="pulse-dot <?php echo $dotClass; ?>"></span>
                <h5 class="m-0 fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($techEngineer); ?></h5>
                <span class="badge text-uppercase text-secondary font-monospace border bg-white" style="font-size: 10px;"><?php echo $statusTextLabel; ?></span>
              </div>
              <span class="text-muted small font-monospace fw-semibold" style="font-size: 12px;">
                <i class="fa-regular fa-clock me-1 text-muted"></i><?php echo date('d-m-Y | h:i A', strtotime($tLog['updated_at'])); ?>
              </span>
            </div>
            
            <!-- Dynamic Real-Time Text Description Block -->
            <p class="m-0 text-secondary" style="font-size: 13.5px; line-height: 1.5; font-weight: 500;">
              <?php 
                $allocatedPart = trim($tLog['allocated_part'] ?? '');
                $partPrice = (float)($tLog['part_price'] ?? 0.00);

                if($isDone): 
              ?>
                Successfully finalized all system calibrations, resolved diagnostic nodes, and closed out maintenance repair operations for unit <strong class="text-dark">#<?php echo htmlspecialchars($tLog['asset_id']); ?></strong> (<?php echo htmlspecialchars($tLog['asset_brand'] . ' ' . $tLog['asset_type']); ?>).
                
                <!-- LIVE PARTS ALLOCATION NOTICE INSIDE THE FEED -->
                <span class="d-block mt-2 font-monospace small p-2 rounded" style="background-color: #FFFFFF; border: 1px solid #DCFCE7; color: #15803D; font-size: 12px; width: fit-content;">
                  📦 <strong>Warehouse Stock Drawn:</strong> <?php echo !empty($allocatedPart) ? htmlspecialchars($allocatedPart) : 'Standard Consumables'; ?> (+৳<?php echo number_format($partPrice, 2); ?>)
                </span>

              <?php else: ?>
                Arrived on site location, initialized hardware fault code telemetry tracing, and shifted operational status to processing for unit <strong class="text-dark">#<?php echo htmlspecialchars($tLog['asset_id']); ?></strong>.
                
                <span class="d-block mt-2 font-monospace small p-2 rounded" style="background-color: #FFFFFF; border: 1px solid #BFDBFE; color: #1D4ED8; font-size: 12px; width: fit-content;">
                  ⏳ <strong>Current Action:</strong> Investigating failure nodes & checking local parts inventory compatibility...
                </span>
              <?php endif; ?>
            </p>
          </div>
        <?php 
            endwhile; 
        else: 
        ?>
          <div class="text-center py-5 text-muted font-monospace fw-bold">
            📡 Standby Mode: Waiting for incoming connection logs from field crew engineer terminal links.
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div> <!-- Close Master Canvas Layout Container Wrapper -->

  <!-- Application Engine Injector Libraries -->
  <script src="https://jquery.com"></script>
  <script src="https://jsdelivr.net"></script>
</body>
</html>
<?php 
if (isset($conn)) { 
    $conn->close(); 
} 
?>
