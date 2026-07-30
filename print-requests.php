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
?>
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
          if ($stmt) {
              $stmt->bind_param("s", $clientEmail);
              $stmt->execute();
              $result = $stmt->get_result();
              
              if ($result->num_rows > 0) {
                  $sl = 1;
                  while ($row = $result->fetch_assoc()) {
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
                      
                      // DYNAMIC AMOUNT PRINT CELL
                      if (is_null($row['amount']) || $row['amount'] == 0.00) {
                          echo "<td><span class='text-muted small font-monospace fw-bold'>Pending Work</span></td>";
                      } else {
                          echo "<td class='fw-bold text-dark font-monospace'>৳" . number_format($row['amount'], 2) . "</td>";
                      }
                      
                      echo "<td class='text-capitalize fw-bold' style='color: #00C2CB;'>" . htmlspecialchars($row['status']) . "</td>";
                      echo "<td class='font-monospace small text-muted'>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='12' class='text-center text-muted py-4 fw-bold font-monospace'>No active service tracking requests history logged under this profile.</td></tr>";
              }
              $stmt->close();
          }
          ?>
        </tbody>
      </table>
    </div>
    
  </div>

</body>
</html>
<?php $conn->close(); ?>
