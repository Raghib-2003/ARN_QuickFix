<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

/* Only manager/admin can access */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
  header("Location: login.php");
  exit;
}

$manager_name = $_SESSION['name'] ?? 'Manager';

/* KPIs from client_requests table */
$new_requests = 0;
$in_progress  = 0;
$overdue      = 0;

/* ✅ Technician updates counter (UNREAD only) */
$tech_updates = 0;

try {
  // check technician_notes exists
  $stmt = $pdo->query("
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'technician_notes'
  ");
  $has_notes = (int)$stmt->fetchColumn() > 0;

  if ($has_notes) {

    // check is_read column exists (to avoid errors if not added yet)
    $stmt = $pdo->query("
      SELECT COUNT(*)
      FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'technician_notes'
        AND column_name = 'is_read'
    ");
    $has_is_read = (int)$stmt->fetchColumn() > 0;

    if ($has_is_read) {
      // ✅ count only unread notes
      $stmt = $pdo->query("SELECT COUNT(*) FROM technician_notes WHERE is_read = 0");
      $tech_updates = (int)$stmt->fetchColumn();
    } else {
      // fallback: count all notes if is_read column not added yet
      $stmt = $pdo->query("SELECT COUNT(*) FROM technician_notes");
      $tech_updates = (int)$stmt->fetchColumn();
    }
  }
} catch (Throwable $e) {
  $tech_updates = 0;
}

try {
  // New = Submitted
  $stmt = $pdo->query("SELECT COUNT(*) FROM client_requests WHERE LOWER(status)='submitted'");
  $new_requests = (int)$stmt->fetchColumn();

  // In progress = Assigned or Processing
  $stmt = $pdo->query("
    SELECT COUNT(*) FROM client_requests
    WHERE LOWER(status) IN ('assigned','processing')
  ");
  $in_progress = (int)$stmt->fetchColumn();

  // Overdue maintenance (if table exists)
  $stmt = $pdo->query("SHOW TABLES LIKE 'maintenance_schedules'");
  $has_maint = (bool)$stmt->fetchColumn();

  if ($has_maint) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_schedules WHERE next_date < CURDATE()");
    $overdue = (int)$stmt->fetchColumn();
  } else {
    $overdue = 0;
  }

} catch (Throwable $e) {
  // keep default numbers
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manager Dashboard | Sonic Elevator Ltd.</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --sonic:#00C2CB; --dark:#0f172a; }
    body{ background:#f7fbfc; }
    .brand{ color:var(--sonic); font-weight:800; letter-spacing:.3px; }
    .topbar{ background:linear-gradient(120deg, rgba(0,194,203,.12), rgba(255,255,255,1)); border-bottom:1px solid rgba(15,23,42,.08); }
    .card-soft{ border:1px solid rgba(15,23,42,.08); box-shadow:0 10px 30px rgba(2,8,23,.06); border-radius:16px; }
    .icon-pill{ width:54px; height:54px; border-radius:14px; display:grid; place-items:center; background:rgba(0,194,203,.14); color:var(--sonic); }
    .btn-sonic{ background:var(--sonic); border:none; font-weight:700; }
    .btn-sonic:hover{ background:#06aeb6; }
    .muted{ color:#64748b; }
    .quicklink{ transition:.2s; }
    .quicklink:hover{ transform:translateY(-2px); }
  </style>
</head>

<body>

  <!-- Topbar -->
  <div class="topbar py-3">
    <div class="container d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-elevator fs-4" style="color:#00C2CB;"></i>
        <div>
          <div class="brand">Sonic Elevator Ltd.</div>
          <small class="muted">Manager Portal</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="badge rounded-pill text-bg-light border">
          <i class="fa-solid fa-user-tie me-1" style="color:#00C2CB;"></i>
          <?php echo htmlspecialchars($manager_name); ?>
        </span>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm rounded-pill">
          <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
        </a>
      </div>
    </div>
  </div>

  <div class="container py-5">

    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
      <div>
        <h2 class="fw-bold mb-1">Dashboard</h2>
        
      </div>

      <div class="d-flex gap-2">
        <!-- ✅ FIX: go to manager-tech-updates.php -->
        <a href="manager-tech-updates.php" class="btn btn-sonic rounded-pill px-4 position-relative">
          <i class="fa-solid fa-bell me-2"></i>Technician Updates

          <?php if ($tech_updates > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?php echo (int)$tech_updates; ?>
            </span>
          <?php endif; ?>
        </a>

        <a href="reports-overview.php" class="btn btn-outline-dark rounded-pill px-4">
          <i class="fa-solid fa-chart-line me-2"></i>Reports
        </a>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="row g-4 mb-4">

      <div class="col-md-4">
        <div class="card card-soft p-4 h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-pill"><i class="fa-solid fa-bell fs-5"></i></div>
            <div>
              <div class="muted">New Requests</div>
              <div class="fs-3 fw-bold mb-0"><?php echo (int)$new_requests; ?></div>
            </div>
          </div>
          <hr class="my-4">
          <small class="muted">New client complaints waiting for review/assignment.</small>

          <div class="mt-3">
            <a class="btn btn-outline-dark rounded-pill w-100" href="manager-requests.php">
              View Submitted Requests
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-soft p-4 h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-pill"><i class="fa-solid fa-spinner fs-5"></i></div>
            <div>
              <div class="muted">In Progress</div>
              <div class="fs-3 fw-bold mb-0"><?php echo (int)$in_progress; ?></div>
            </div>
          </div>
          <hr class="my-4">
          <small class="muted">Requests currently being handled by technicians.</small>

          <div class="mt-3">
            <a class="btn btn-outline-dark rounded-pill w-100" href="manager-requests.php">
              View Active Work
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-soft p-4 h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-pill"><i class="fa-solid fa-triangle-exclamation fs-5"></i></div>
            <div>
              <div class="muted">Overdue Alerts</div>
              <div class="fs-3 fw-bold mb-0"><?php echo (int)$overdue; ?></div>
            </div>
          </div>
          <hr class="my-4">
          <a class="btn btn-sonic rounded-pill w-100" href="maintenance-overview.php">
            <i class="fa-solid fa-calendar-days me-2"></i>Open Maintenance
          </a>

          <?php if ($overdue === 0): ?>
            <small class="d-block mt-2 muted">No overdue maintenance found.</small>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Main module links -->
    <div class="row g-4">

      <div class="col-lg-4">
        <a class="text-decoration-none" href="manager-requests.php">
          <div class="card card-soft p-4 h-100 quicklink">
            <div class="d-flex align-items-center justify-content-between">
              <div class="icon-pill"><i class="fa-solid fa-folder-open fs-5"></i></div>
              <i class="fa-solid fa-arrow-right muted"></i>
            </div>
            <h4 class="mt-3 mb-2 fw-bold text-dark">Service Requests</h4>
            <p class="muted mb-0">Review complaints, assign technicians, and track status.</p>
          </div>
        </a>
      </div>

      <div class="col-lg-4">
        <a class="text-decoration-none" href="maintenance-overview.php">
          <div class="card card-soft p-4 h-100 quicklink">
            <div class="d-flex align-items-center justify-content-between">
              <div class="icon-pill"><i class="fa-solid fa-list-check fs-5"></i></div>
              <i class="fa-solid fa-arrow-right muted"></i>
            </div>
            <h4 class="mt-3 mb-2 fw-bold text-dark">Maintenance</h4>
            <p class="muted mb-0">View schedules, overdue tasks, and maintenance history.</p>
          </div>
        </a>
      </div>

      <div class="col-lg-4">
        <a class="text-decoration-none" href="reports-overview.php">
          <div class="card card-soft p-4 h-100 quicklink">
            <div class="d-flex align-items-center justify-content-between">
              <div class="icon-pill"><i class="fa-solid fa-chart-pie fs-5"></i></div>
              <i class="fa-solid fa-arrow-right muted"></i>
            </div>
            <h4 class="mt-3 mb-2 fw-bold text-dark">Reports & Metrics</h4>
            <p class="muted mb-0">Analyze trends, performance, and resolution times.</p>
          </div>
        </a>
      </div>

    </div>

  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>