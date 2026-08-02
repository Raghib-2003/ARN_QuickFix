<?php
// 1. Initialize Active User Session and Force Authorization Guard Rails
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email']) || !isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

$clientEmail = $_SESSION['email'];
$clientName = $_SESSION['name'];

// 2. Establish High-Performance Local Database Integration Network Link
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "arn_quickfix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connectivity Failure Trace: " . $conn->connect_error);
}
// FIXED: Added status to the field list so your badge color conditions compute accurately!
$logQuery = $conn->prepare("SELECT asset_id, asset_type, asset_brand, problem_category, allocated_part, part_price, priority, status, location, payment_method, amount, created_at FROM service_requests WHERE client_email = ? ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Client Requests | ARN QuickFix Ltd.</title>
  
  <!-- High performance local file framework loaders -->
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
    .all-requests-navbar {
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
    .action-icon-link {
      background: none;
      border: 1px solid var(--border-gray);
      color: #64748B;
      font-size: 13px;
      font-weight: 500;
      padding: 6px 18px;
      border-radius: 20px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .action-icon-link:hover {
      border-color: var(--primary-cyan);
      color: var(--primary-cyan);
    }
    .ledger-panel {
      background: #FFFFFF;
      border: 1px solid var(--border-gray);
      border-radius: 12px;
      padding: 35px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01);
    }
    /* Explicit status color tag styling matching your Figma visual columns */
    .status-badge-submitted {
      background-color: var(--primary-cyan);
      color: #FFFFFF;
      font-size: 11px;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 4px;
      text-transform: capitalize;
    }
  </style>
</head>
<body>

  <!-- ================= MASTER NAVIGATION BAR (MATCHES FIGMA MINIMALIST RAIL) ================= -->
  <nav class="navbar all-requests-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <a href="client-dashboard.php" class="brand-accent d-flex align-items-center gap-2">
        <img src="img/logo.svg.svg" alt="Logo" style="height: 38px; width: auto;" onerror="this.style.display='none';">
        <span>ARN QuickFix Ltd.</span>
      </a>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-secondary small fw-medium"><i class="fa fa-user-circle me-1"></i> Client: <strong class="text-dark"><?php echo $clientName; ?></strong></span>
      <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" style="font-size:12px;">Logout</a>
    </div>
  </nav>

  <!-- ================= ALL REQUESTS MATRIX LEDGER CONTAINER ================= -->
  <div class="container py-5">
    
    <!-- Title Section Header containing structural navigation back links -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold m-0 text-dark" style="font-size: 26px; letter-spacing: -0.5px;">All Requests</h2>
      <a href="client-dashboard.php" class="action-icon-link"><i class="fa fa-arrow-left me-1"></i> Back</a>
    </div>

    <!-- Master Data Layout Grid Panel Block -->
    <div class="ledger-panel">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
          <!-- Custom High Contrast Headers Row directly mapping your Figma specs -->
          <thead>
            <tr class="text-secondary" style="border-bottom: 2px solid var(--border-gray); font-weight: 700;">
              <th class="py-3" style="font-weight: 700 !important; width: 60px;">SL</th>
              <th class="py-3" style="font-weight: 700 !important;">Asset ID</th>
              <th class="py-3" style="font-weight: 700 !important;">Asset Type</th>
              <th class="py-3" style="font-weight: 700 !important;">Asset Brand</th>
              <th class="py-3" style="font-weight: 700 !important;">Category</th>
              <th class="py-3" style="font-weight: 700 !important;">Priority</th>
              <th class="py-3" style="font-weight: 700 !important;">Status</th>
              <th class="py-3" style="font-weight: 700 !important;">Phone</th>
              <th class="py-3" style="font-weight: 700 !important;">Location</th>
              <th class="py-3" style="font-weight: 700 !important;">Payment</th>
              <th class="py-3" style="font-weight: 700 !important;">Amount</th>
              <th class="py-3" style="font-weight: 700 !important;">Created</th>
            </tr>
          </thead>
                   <tbody>
            <?php
            // UPDATED QUERY: Selects the amount field string safely
            $stmt = $conn->prepare("SELECT asset_id, asset_type, asset_brand, problem_category, priority, status, phone, location, payment_method, amount, created_at FROM service_requests WHERE client_email = ? ORDER BY id DESC");
            if ($stmt) {
                $stmt->bind_param("s", $clientEmail);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
    $sl = 1;
    // SINGLE CLEAN LOOP: Resolves the duplicate row layout issue permanently
    while ($row = $result->fetch_assoc()) {
        $currentProblem = trim($row['problem_category'] ?? '');
        $allocatedPart = trim($row['allocated_part'] ?? '');
        $partPrice = (float)($row['part_price'] ?? 0.00);
        $finalAmount = (float)($row['amount'] ?? 0.00); 

        // Fetch our baseline service pricing parameters matching user selections
        $baseRateNumeric = 0.00;
        $priceGuideTag = "";
        
        switch ($currentProblem) {
            case 'Component Repair':    $priceGuideTag = "৳4,500"; $baseRateNumeric = 4500.00; break;
            case 'Part Replacement':     $priceGuideTag = "৳3,000"; $baseRateNumeric = 3000.00; break;
            case 'Modernization':        $priceGuideTag = "৳15,000"; $baseRateNumeric = 15000.00; break;
            case 'Routine Servicing':    $priceGuideTag = "৳2,000"; $baseRateNumeric = 2000.00; break;
            case 'Emergency Breakdown':  $priceGuideTag = "৳5,000"; $baseRateNumeric = 5000.00; break;
            case 'Basic Servicing':      $priceGuideTag = "৳600";   $baseRateNumeric = 600.00; break;
            case 'Deep Cleaning':        $priceGuideTag = "৳1,200"; $baseRateNumeric = 1200.00; break;
            case 'Duct Cleaning':        $priceGuideTag = "৳5,000"; $baseRateNumeric = 5000.00; break;
            case 'Gas Refill':           $priceGuideTag = "৳2,500"; $baseRateNumeric = 2500.00; break;
            case 'Electrical Repair':    $priceGuideTag = "৳1,500"; $baseRateNumeric = 1500.00; break;
            case 'Compressor Repair':    $priceGuideTag = "৳4,000"; $baseRateNumeric = 4000.00; break;
            case 'Preventative Inspection': $priceGuideTag = "৳3,500"; $baseRateNumeric = 3500.00; break;
            case 'Fault Code Diagnostic':   $priceGuideTag = "৳1,800"; $baseRateNumeric = 1800.00; break;
            case 'Engine Rebuild':          $priceGuideTag = "৳25,000"; $baseRateNumeric = 25000.00; break;
            case 'Component Repairs':       $priceGuideTag = "৳6,000"; $baseRateNumeric = 6000.00; break;
            case 'Advanced Testing':        $priceGuideTag = "৳8,000"; $baseRateNumeric = 8000.00; break;
            case 'Fuel Polishing':          $priceGuideTag = "৳4,500"; $baseRateNumeric = 4500.00; break;
            default: $priceGuideTag = ""; $baseRateNumeric = 0.00; break;
        }

        // Generate the dynamic inventory part allocated notice badge array
        $inventoryBadgeHtml = "";
        if (!empty($allocatedPart)) {
            $inventoryBadgeHtml = "<div class='mt-1'><span class='badge font-monospace' style='font-size: 10px; padding: 2px 6px; background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; border-radius: 4px; display: inline-block;'>📦 Part: " . htmlspecialchars($allocatedPart) . " (+৳" . number_format($partPrice) . ")</span></div>";
        } else {
            if (!empty($priceGuideTag)) {
                $inventoryBadgeHtml = "<div class='mt-1'><span class='badge font-monospace' style='font-size: 10px; padding: 2px 6px; background-color: #ECFEFF; color: #0891B2; border: 1px solid #CFFAFE; border-radius: 4px; display: inline-block;'>Base Rate: " . $priceGuideTag . "</span></div>";
            }
        }

        // Setup status colors cleanly
        $statusClass = "bg-warning text-dark"; 
        if ($row['status'] === 'processing') { $statusClass = "bg-primary text-white"; }
        elseif ($row['status'] === 'completed') { $statusClass = "bg-success text-white"; }
        elseif ($row['status'] === 'complaint_raised') { $statusClass = "bg-danger text-white"; }
        
        echo "<tr style='border-bottom: 1px solid #F1F5F9;'>";
        echo "<td class='text-secondary fw-semibold'>" . $sl++ . "</td>";
        echo "<td class='fw-bold text-dark'>#" . htmlspecialchars($row['asset_id']) . "</td>";
        echo "<td class='fw-bold text-dark'>" . htmlspecialchars($row['asset_type']) . "</td>";
        echo "<td class='fw-semibold text-secondary'>" . htmlspecialchars($row['asset_brand']) . "</td>";
        echo "<td class='fw-semibold text-dark'><div>" . htmlspecialchars($currentProblem) . "</div>" . $inventoryBadgeHtml . "</td>";
        echo "<td class='fw-semibold text-secondary'>" . htmlspecialchars($row['priority']) . "</td>";
        echo "<td><span class='badge " . $statusClass . " text-capitalize px-3 py-1.5 fw-bold'>" . htmlspecialchars($row['status']) . "</span></td>";
        echo "<td class='font-monospace text-dark'>" . htmlspecialchars($row['phone']) . "</td>";
        echo "<td class='fw-semibold text-dark'>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td class='fw-semibold text-secondary'>" . htmlspecialchars($row['payment_method']) . "</td>";
        
        // DYNAMIC ACCURATE BILLING LOGIC:
        // If the job is ongoing, display "Pending Work"
        if ($row['status'] !== 'completed' && $row['status'] !== 'complaint_raised') {
            echo "<td><span class='text-muted small font-monospace fw-bold' style='color: #94A3B8 !important; font-size:12px;'>Pending Work</span></td>";
        } else {
            // SMART FALLBACK: If the column amount is still 0, use the official Base Rate automatic calculation!
            $displayBill = ($finalAmount > 0.00) ? $finalAmount : $baseRateNumeric;
            echo "<td class='fw-bold text-dark font-monospace' style='color: #0F172A !important;'>৳" . number_format($displayBill, 2) . "</td>";
        }
        
        echo "<td class='text-muted small fw-medium font-monospace'>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='12' class='text-center text-muted py-4 fw-bold font-monospace'>No active service tracking requests logs found for this account node.</td></tr>";
}

                }
                $stmt->close();

            ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>


  <!-- <script src="https://jsdelivr.net"></script> -->
</body>
</html>
<?php $conn->close(); ?>
