<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}
$clientEmail = $_SESSION['email'];
$clientName = $_SESSION['name'];

// Establishes your clean high-speed local database network path link
$conn = new mysqli("127.0.0.1", "root", "", "arn_quickfix");
if ($conn->connect_error) { 
    die("Database Connection Error Trace: " . $conn->connect_error); 
}

// ====================================================================
// UNIFIED PRINT SELECTOR FIXED ENGINE (PASTED SAFELY HERE)
// ====================================================================
// This fetches ALL active and past entries (including your 3 completed jobs)
// matching this specific client profile account, ordered chronologically.
$queryStr = "SELECT id, asset_id, asset_type, asset_brand, problem_category, priority, status, phone, location, payment_method, allocated_part, part_price, amount, created_at 
             FROM service_requests 
             WHERE client_email = '$clientEmail' 
             ORDER BY id DESC";

$result = $conn->query($queryStr);
?>
<!-- Your HTML sheet template print layout tags continue exactly right below here -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Complete Service Requests Ledger Statement Summary</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, sans-serif; color: #1E293B; }
    .report-header { border-bottom: 3px solid #0F172A; padding-bottom: 15px; margin-bottom: 30px; }
    @media print {
      body { padding: 0; margin: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body onload="window.print();"> <!-- Opens print / save interface immediately on load -->

  <div class="container-fluid py-4 px-4">
    
    <!-- Report Top Header Banner -->
    <div class="report-header d-flex justify-content-between align-items-end">
      <div>
         <img src="img/logo.svg.svg" alt="Logo" class="report-logo" onerror="this.src='img/logo.png'; this.onerror=function(){this.style.display='none';};"><h1 class="fw-bold m-0" style="color: #00C2CB; letter-spacing: -0.5px;">ARN QuickFix Ltd.</h1>
        
        <p class="text-muted m-0 small fw-semibold font-monospace text-uppercase" style="font-size: 11px;">Complete Customer Service Requests Master Ledger Statement</p>
      </div>
      <div class="text-end">
        <h5 class="fw-bold m-0 text-dark">Master Report Profile</h5>
        <span class="small font-monospace text-secondary" style="font-size: 12px;">Email: <?php echo htmlspecialchars($clientEmail); ?></span>
      </div>
    </div>

    <!-- Client Account Context Card Box Container -->
    <div class="mb-4 bg-light p-3 rounded-3 border border-secondary-subtle" style="font-size: 13.5px; background-color: #F8FAFC !important;">
      <div class="row g-2">
        <div class="col-sm-6"><strong>Client Name Profile:</strong> <span class="text-dark fw-bold"><?php echo htmlspecialchars($clientName); ?></span></div>
        <div class="col-sm-6 text-sm-end"><strong>Generated Timestamp Log:</strong> <span class="font-monospace text-secondary fw-bold"><?php echo date('Y-m-d H:i:s'); ?></span></div>
      </div>
    </div>

    <!-- Expanded Complete Parameter Data Ledger Table Grid Matrix -->
    <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle" style="font-size: 12.5px;">
        <thead class="table-dark text-uppercase font-monospace" style="font-size: 11.5px;">
          <tr>
            <th class="py-3" style="width: 50px; font-weight: 700 !important;">SL</th>
            <th class="py-3" style="font-weight: 700 !important;">Asset ID</th>
            <th class="py-3" style="font-weight: 700 !important;">Asset Type</th>
            <th class="py-3" style="font-weight: 700 !important;">Brand Name</th>
            <th class="py-3" style="font-weight: 700 !important;">Problem Category</th>
            <th class="py-3" style="font-weight: 700 !important;">Priority</th>
            <th class="py-3" style="font-weight: 700 !important;">Contact Phone</th>
            <th class="py-3" style="font-weight: 700 !important;">Operational Location</th>
            <th class="py-3" style="font-weight: 700 !important;">Billing Method</th>
            <!-- ADDED AMOUNT REPORT COLUMN -->
            <th class="py-3" style="font-weight: 700 !important;">Amount</th>
            <th class="py-3" style="font-weight: 700 !important;">Status</th>
            <th class="py-3" style="font-weight: 700 !important;">Created Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $conn->prepare("SELECT asset_id, asset_type, asset_brand, problem_category, priority, phone, location, payment_method, amount, status, created_at FROM service_requests WHERE client_email = ? ORDER BY id DESC");
          if ($stmt) 
              $stmt->bind_param("s", $clientEmail);
              $stmt->execute();
              $result = $stmt->get_result();
              
              if ($result->num_rows > 0) {
    $sl = 1;
    // SINGLE UNIFIED LOOP: Fixes pricing calculation logic variables and removes broken code blocks
    while ($row = $result->fetch_assoc()) {
        $currentProblem = trim($row['problem_category'] ?? '');
        $allocatedPart = trim($row['allocated_part'] ?? '');
        $partPrice = (float)($row['part_price'] ?? 0.00);
        $finalAmount = (float)($row['amount'] ?? 0.00);

        // Fetch our baseline service pricing parameters matching user selections
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

        echo "<tr>";
        echo "<td class='text-secondary font-monospace fw-bold'>" . $sl++ . "</td>";
        echo "<td class='fw-bold text-dark'>#" . htmlspecialchars($row['asset_id']) . "</td>";
        echo "<td class='fw-bold text-dark'>" . htmlspecialchars($row['asset_type']) . "</td>";
        echo "<td class='fw-semibold text-secondary'>" . htmlspecialchars($row['asset_brand']) . "</td>";
        echo "<td class='fw-medium text-dark'>" . htmlspecialchars($row['problem_category']) . "</td>";
        echo "<td class='fw-semibold'>" . htmlspecialchars($row['priority']) . "</td>";
        echo "<td class='font-monospace text-dark fw-semibold'>" . htmlspecialchars($row['phone']) . "</td>";
        echo "<td class='fw-semibold text-dark'>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td class='fw-semibold text-secondary'>" . htmlspecialchars($row['payment_method']) . "</td>";
        
                // ====================================================================
        // DYNAMIC AMOUNT PRINT CELL (FIXED COMPOSITE INTERLOCK)
        // ====================================================================
        if ($row['status'] !== 'completed' && $row['status'] !== 'complaint_raised') {
            echo "<td><span class='text-muted small font-monospace fw-bold'>Pending Work</span></td>";
        } else {
            // If the final column is empty, automatically add Base Labor Fee + Installed Part Price!
            $displayBillNumeric = ($finalAmount > 0.00) ? $finalAmount : ($baseRateNumeric + $partPrice);
            echo "<td class='fw-bold text-dark font-monospace'>৳" . number_format($displayBillNumeric, 2) . "</td>";
        }
        
        // ====================================================================
        // FIXED DYNAMIC COLOR STATUS COLUMN (CLEAN INLINE INTERLOCK)
        // ====================================================================
        $currentStatusText = strtolower(trim($row['status'] ?? 'pending'));
        $printStatusLabel = ucfirst($currentStatusText);
        $statusTextHexColor = "#D97706"; // Default Golden Amber for Pending

        if ($currentStatusText === 'completed') {
            $statusTextHexColor = "#10B981"; // Fresh Emerald Green
        } elseif ($currentStatusText === 'processing') {
            $statusTextHexColor = "#3B82F6"; // Clear Operations Blue
        } elseif ($currentStatusText === 'complaint_raised') {
            $statusTextHexColor = "#EF4444"; // Bold Crimson Red
            $printStatusLabel = "Complaint";
        }

        // Output your status column using your custom hexadecimal color assignments seamlessly
        echo "<td class='text-capitalize fw-bold' style='color: {$statusTextHexColor} !important;'>";
        echo htmlspecialchars($printStatusLabel);
        echo "</td>";
        
        echo "<td class='font-monospace small text-muted'>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='12' class='text-center text-muted py-4 fw-bold font-monospace'>No active service tracking requests history logged under this profile.</td></tr>";
}

// FIXED: Removed the broken crashing $stmt->close() statement cleanly!
?>

        
        </tbody>
      </table>
    </div>
    
  </div>

</body>
</html>
<?php $conn->close(); ?>
