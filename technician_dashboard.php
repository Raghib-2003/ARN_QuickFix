<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. GATEKEEPER SECURITY SHIELD: Ensure user is a recognized tech account role token
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || strpos($_SESSION['role'], 'tech_') !== 0) {
    header("Location: login.php");
    exit();
}

$techName = $_SESSION['name'] ?? 'Field Engineering Specialist';
$techEmail = $_SESSION['email'] ?? '';
$userRole = $_SESSION['role'] ?? 'tech_ac';

// Connect to Database
$conn = new mysqli("127.0.0.1", "root", "", "arn_quickfix");
if ($conn->connect_error) {
    die("Database Connection Node Failed: " . $conn->connect_error);
}

// ====================================================================
// MASTER ADAPTIVE COCKPIT REPOSITORY CONFIGURATOR
// ====================================================================
// Dynamically morphs themes, filters, and asset metrics on the fly!
$themePrimaryColor = '#06B6D4'; // AC Ice Cyan Default
$themeHoverColor   = '#0891B2';
$themeBgBadge      = '#ECFEFF';
$themeBadgeBorder  = '#CFFAFE';
$dashboardTitle    = "HVAC Systems Terminal";
$dashboardDesc     = "Dedicated crew terminal filtering cooling loops, compressor calibrations, and duct overhauls.";
$sqlFilterKeyword  = 'ac';
$sqlInvCategory    = 'ac';
$iconHeaderToken   = 'fa-snowflake';

if ($userRole === 'tech_generator') {
    $themePrimaryColor = '#D97706'; // Generator Industrial Amber
    $themeHoverColor   = '#B45309';
    $themeBgBadge      = '#FFFBEB';
    $themeBadgeBorder  = '#FEF3C7';
    $dashboardTitle    = "Power Generation Terminal";
    $dashboardDesc     = "Dedicated crew terminal filtering alternator rebuilds, fuel polishing, and governor tuning.";
    $sqlFilterKeyword  = 'generator';
    $sqlInvCategory    = 'generator';
    $iconHeaderToken   = 'fa-bolt';
} elseif ($userRole === 'tech_elevator') {
    $themePrimaryColor = '#64748B'; // Elevator Slate Gray
    $themeHoverColor   = '#475569';
    $themeBgBadge      = '#F8FAFC';
    $themeBadgeBorder  = '#E2E8F0';
    $dashboardTitle    = "Vertical Mobility Terminal";
    $dashboardDesc     = "Dedicated crew terminal filtering hoistway alignments, hoist motor repairs, and safety interlocks.";
    $sqlFilterKeyword  = 'elevator';
    $sqlInvCategory    = 'elevator';
    $iconHeaderToken   = 'fa-arrows-up-down';
}

// --------------------------------------------------------------------
// FORM ENGINE PROCESSING: FINALIZE TASK LOGS & STOCK REDUCTIONS
// --------------------------------------------------------------------
$actionSuccess = "";
$actionError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'complete_field_job') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $inventory_id = (int)($_POST['inventory_id'] ?? 0);
    $labor_fee = (float)($_POST['labor_fee'] ?? 0.00);

    if ($ticket_id > 0 && $labor_fee > 0) {
        $part_name = "";
        $part_price = 0.00;

        // Step A: Crosscheck warehouse stock matrix properties if a part was allocated
        if ($inventory_id > 0) {
            $invCheck = $conn->prepare("SELECT part_name, part_price, stock_qty FROM inventory WHERE id = ?");
            $invCheck->bind_param("i", $inventory_id);
            $invCheck->execute();
            $invItem = $invCheck->get_result()->fetch_assoc();
            $invCheck->close();

            if ($invItem) {
                if ((int)$invItem['stock_qty'] > 0) {
                    $part_name = $invItem['part_name'];
                    $part_price = (float)$invItem['part_price'];

                    // Deduct 1 stock unit cleanly via an internal database transaction query
                    $conn->query("UPDATE inventory SET stock_qty = stock_qty - 1 WHERE id = $inventory_id");
                } else {
                    $_SESSION['tech_err'] = "Stock Lockout: Selected component is out of stock! Restock via Inventory desk.";
                    header("Location: technician_dashboard.php");
                    exit();
                }
            }
        }

        // Step B: Formulate final cumulative billing ledger math matrix
        $finalTotalAmount = $labor_fee + $part_price;

        // Step C: Update service_requests table. Sets status to 'completed' and is_read to 0 for client alerts!
        $updateStmt = $conn->prepare("UPDATE service_requests SET status = 'completed', allocated_part = ?, part_price = ?, amount = ?, is_read = 0 WHERE id = ?");
        $updateStmt->bind_param("sddi", $part_name, $part_price, $finalTotalAmount, $ticket_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['tech_msg'] = "🎉 Success! Ticket #{$ticket_id} marked Completed. Invoice bill calculated to ৳" . number_format($finalTotalAmount, 2);
        } else {
            $_SESSION['tech_err'] = "Database Error: Failed updating row logs parameters.";
        }
        $updateStmt->close();
    } else {
        $_SESSION['tech_err'] = "Validation Failure: Please enter a valid field labor fee to compute statements.";
    }
    header("Location: technician_dashboard.php");
    exit();
}

if (isset($_SESSION['tech_msg'])) { $actionSuccess = $_SESSION['tech_msg']; unset($_SESSION['tech_msg']); }
if (isset($_SESSION['tech_err'])) { $actionError = $_SESSION['tech_err']; unset($_SESSION['tech_err']); }

// 1. ISOLATED QUERY LAYER: Pulls ONLY 'processing' jobs that match this tech's specialized asset group
$assignedJobs = $conn->query("SELECT id, client_email, asset_type, asset_brand, asset_id, problem_category, priority, location, created_at FROM service_requests WHERE status = 'processing' AND (LOWER(asset_type) LIKE '%$sqlFilterKeyword%' OR LOWER(asset_type) LIKE '%conditioner%') ORDER BY id DESC");

// 2. Fetch specific warehouse inventory stock components matching the technician's scope
$partsInventory = $conn->query("SELECT id, part_name, part_price, stock_qty FROM inventory WHERE LOWER(asset_category) LIKE '%$sqlInvCategory%' OR LOWER(asset_category) LIKE '%hvac%' OR LOWER(asset_category) LIKE '%engine%' ORDER BY part_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $dashboardTitle; ?> | Operational Desk</title>
  
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cloudflare.com" rel="stylesheet">

  <style>
    :root {
      --tech-accent: <?php echo $themePrimaryColor; ?>;
      --tech-hover: <?php echo $themeHoverColor; ?>;
      --deep-navy: #0F172A;
      --slate-border: #E2E8F0;
      --bg-canvas: #F8FAFC;
    }
    body { background-color: var(--bg-canvas); font-family: system-ui, -apple-system, sans-serif; color: var(--deep-navy); }
    
    /* INTERACTIVE NAVBAR CONTAINER STYLES */
    .tech-navbar { background-color: var(--deep-navy); padding: 18px 45px; border-bottom: 3px solid var(--tech-accent); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .tech-navbar .brand-text { color: #FFFFFF; font-weight: 800; font-size: 19px; text-decoration: none; text-transform: uppercase; letter-spacing: -0.3px; }
    .tech-navbar .brand-text span { color: var(--tech-accent); }
    
    .logout-link { color: #F1F5F9; font-weight: 700; font-size: 11px; letter-spacing: 0.5px; border: 1px solid #334155; padding: 6px 14px; border-radius: 50rem; transition: all 0.2s; text-decoration: none; }
    .logout-link:hover { background-color: #EF4444; color: #FFFFFF; border-color: #EF4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }

    /* CARD GRID ANIMATED ENVIRONMENT LAYOUT */
    .tech-card-layout {
      background: #FFFFFF; border: 1px solid var(--slate-border); border-radius: 14px; padding: 25px; transition: transform 0.25s, box-shadow 0.25s; box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.01); position: relative; overflow: hidden;
    }
    .tech-card-layout:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -5px rgba(15, 23, 42, 0.05); }
    
    .form-select-custom, .form-control-custom {
      height: 42px; background-color: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13.5px; padding: 0 12px; outline: none; transition: all 0.2s;
    }
    .form-select-custom:focus, .form-control-custom:focus { border-color: var(--tech-accent); background-color: #FFFFFF; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.08); }
    
    .btn-complete-task { background-color: var(--tech-accent); color: #FFFFFF; font-weight: 700; font-size: 12px; height: 42px; border: none; border-radius: 8px; transition: all 0.2s; letter-spacing: 0.3px; }
    .btn-complete-task:hover { background-color: var(--tech-hover); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
    
    .priority-badge { font-size: 9px; padding: 3px 8px; font-weight: 800; border-radius: 4px; letter-spacing: 0.3px; }
  </style>
</head>
<body>

  <!-- ================= MASTER DYNAMIC ADAPTIVE NAVBAR ================= -->
  <nav class="navbar tech-navbar d-flex align-items-center justify-content-between">
    <a href="technician_dashboard.php" class="brand-text">
      <i class="fa-solid <?php echo $iconHeaderToken; ?> me-2"></i> ARN <span><?php echo $dashboardTitle; ?></span>
    </a>
    <div class="d-flex align-items-center gap-3">
      <div class="text-white small fw-bold px-3 py-1.5 rounded-pill" style="background-color: rgba(255,255,255,0.04); border: 1px solid #334155;">
        Crew Specialist: <span style="color: var(--tech-accent);"><?php echo htmlspecialchars($techName); ?></span>
      </div>
      <a href="logout.php" class="logout-link text-uppercase"><i class="fa-solid fa-power-off me-1"></i> Exit Terminal</a>
    </div>
  </nav>

  <!-- ================= CANVAS APP WINDOW CONTAINER ================= -->
  <div class="container py-5" style="max-width: 1040px;">
    
    <!-- Desk Presentation Title Section Box Header Layout -->
    <!-- Desk Presentation Title Section Box Header Layout -->
    <div class="mb-5 d-flex align-items-center justify-content-between bg-white p-4 border rounded-4 shadow-sm">
      <div class="text-start">
        <h2 class="fw-bold m-0 text-dark" style="font-size: 24px; letter-spacing: -0.5px;"><?php echo $dashboardTitle; ?> Queue</h2>
        <p class="text-muted m-0 small fw-medium mt-1.5"><?php echo $dashboardDesc; ?></p>
      </div>
      <div class="text-end font-monospace">
        <div class="small fw-bold text-uppercase text-muted" style="font-size:10px; letter-spacing:0.5px;">Live Dispatches</div>
        <div class="fw-extrabold text-dark m-0" style="font-size:32px; font-weight:800; line-height:1;"><?php echo $assignedJobs ? $assignedJobs->num_rows : 0; ?></div>
      </div>
    </div>

    <!-- Live Status Alert Notifications Layer -->
    <?php if (!empty($actionSuccess)): ?>
      <div class="alert border-0 small py-3 px-4 font-monospace shadow-sm mb-4" style="background-color:#F0FDF4; color:#16A34A; border-left:4px solid #10B981 !important; font-size:13.5px;"><?php echo $actionSuccess; ?></div>
    <?php endif; ?>
    <?php if (!empty($actionError)): ?>
      <div class="alert border-0 small py-3 px-4 font-monospace shadow-sm mb-4" style="background-color:#FEF2F2; color:#EF4444; border-left:4px solid #EF4444 !important; font-size:13.5px;"><?php echo $actionError; ?></div>
    <?php endif; ?>

    <!-- DYNAMIC ASSIGNED JOBS CONSOLE CONTAINER -->
    <div class="row g-4">
      <?php if ($assignedJobs && $assignedJobs->num_rows > 0): ?>
        <?php while ($job = $assignedJobs->fetch_assoc()): 
            $ticketPriority = strtolower(trim($job['priority'] ?? 'medium'));
            $priorityBg = ($ticketPriority === 'high' || $ticketPriority === 'critical') ? '#FEF2F2' : '#EFF6FF';
            $priorityTextColor = ($ticketPriority === 'high' || $ticketPriority === 'critical') ? '#EF4444' : '#3B82F6';
        ?>
          <div class="col-12">
            <div class="tech-card-layout">
              <div class="row g-4 align-items-center">
                
                <!-- Section A: Live Ticket Context Specs -->
                <div class="col-lg-4 text-start">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge font-monospace" style="font-size:9.5px; font-weight:700; background-color: <?php echo $themeBgBadge; ?>; color: var(--tech-theme); border:1px solid <?php echo $themeBadgeBorder; ?>;">Ticket #<?php echo $job['id']; ?></span>
                    <span class="priority-badge text-uppercase font-monospace" style="background-color: <?php echo $priorityBg; ?>; color: <?php echo $priorityTextColor; ?>;"><?php echo $ticketPriority; ?> Priority</span>
                  </div>
                  <h5 class="fw-bold text-dark m-0" style="font-size:16px; letter-spacing:-0.2px;"><?php echo htmlspecialchars($job['asset_brand'] . ' ' . $job['asset_type']); ?></h5>
                  <div class="text-secondary font-monospace mt-1 fw-medium" style="font-size:12px;"><i class="fa-solid fa-hashtag text-muted me-1"></i> ID: <span class="text-dark font-sans fw-bold"><?php echo htmlspecialchars($job['asset_id']); ?></span></div>
                  <div class="text-muted font-monospace mt-1" style="font-size:11px;"><i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($job['client_email']); ?></div>
                  <div class="mt-2.5 pt-2 border-top small text-secondary" style="font-size:12px;"><strong>Issue Details:</strong> <span class="text-dark"><?php echo htmlspecialchars($job['problem_category']); ?></span></div>
                </div>

                <!-- Section B: Live Interface Action Input Forms Selection -->
                <div class="col-lg-8">
                  <form action="technician_dashboard.php" method="POST" class="row g-3 align-items-end text-start">
                    <input type="hidden" name="action_type" value="complete_field_job">
                    <input type="hidden" name="ticket_id" value="<?php echo $job['id']; ?>">
                    
                    <!-- Form Field 1: Dynamic Warehouse Component Input -->
                    <div class="col-md-5">
                      <label class="small fw-bold text-secondary text-uppercase mb-2" style="font-size:10px; letter-spacing:0.5px;"><i class="fa-solid fa-box-open me-1"></i> Warehouse Component Used</label>
                      <select name="inventory_id" class="form-select form-select-custom w-100" required>
                        <option value="0">No Extra Parts (Standard Consumables Only)</option>
                        <?php 
                          if ($partsInventory && $partsInventory->num_rows > 0):
                              $partsInventory->data_seek(0); // Reset tracking cursor back to index 0
                              while ($part = $partsInventory->fetch_assoc()):
                                  $qty = (int)$part['stock_qty'];
                        ?>
                          <option value="<?php echo $part['id']; ?>" <?php echo ($qty === 0) ? 'disabled' : ''; ?>>
                            <?php echo htmlspecialchars($part['part_name']); ?> (৳<?php echo number_format($part['part_price']); ?>) — Stock: <?php echo $qty; ?>
                          </option>
                        <?php 
                              endwhile;
                          endif;
                        ?>
                      </select>
                    </div>

                    <!-- Form Field 2: Labor Cost Input -->
                    <div class="col-md-4">
                      <label class="small fw-bold text-secondary text-uppercase mb-2" style="font-size:10px; letter-spacing:0.5px;"><i class="fa-solid fa-calculator me-1"></i> Labor Fee (৳)</label>
                      <input type="number" name="labor_fee" class="form-control form-control-custom font-monospace w-100" placeholder="e.g. 2500" min="1" required>
                    </div>

                    <!-- Form Field 3: Submission Action Button Toggle -->
                    <div class="col-md-3">
                      <button type="submit" class="btn btn-complete-task w-100 text-uppercase fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Finish Job</button>
                    </div>
                  </form>
                </div>

              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <!-- Clean, Zero Workload Workspace Fallback Canvas -->
        <div class="col-12">
          <div class="text-center py-5 px-4 border rounded-4 bg-white text-muted font-monospace fw-bold shadow-sm" style="border-style: dashed !important; border-width: 2px !important; border-color: #CBD5E1 !important;">
            <div class="mb-3" style="font-size: 38px; color: var(--tech-accent); opacity: 0.6;"><i class="fa-solid fa-circle-check"></i></div>
            <div style="font-size: 14px; text-transform: uppercase; color: #475569; letter-spacing: 0.5px;">Workload Operations Clear</div>
            <p class="m-0 small text-secondary font-sans fw-normal mt-1">Excellent job! There are currently no active repair dispatches assigned under this technician classification profile.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://jquery.com"></script>
</body>
</html>
<?php if (isset($conn)) { $conn->close(); } ?>
