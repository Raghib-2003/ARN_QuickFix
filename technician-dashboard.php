<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'technician') {
  header("Location: login.php");
  exit;
}

$tech_id = (int)$_SESSION['user_id'];
$error = "";

/** Helpers */
function table_exists(PDO $pdo, string $table): bool {
  $sql = "SELECT 1 FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$table]);
  return (bool)$st->fetchColumn();
}
function status_badge(string $status): string {
  $s = strtolower(trim($status));
  $map = [
    "submitted"  => "secondary",
    "assigned"   => "info",
    "processing" => "warning",
    "pending"    => "dark",
    "completed"  => "success",
    "closed"     => "primary",
  ];
  $color = $map[$s] ?? "secondary";
  $label = ucfirst($s);
  return "<span class=\"badge rounded-pill text-bg-{$color}\">{$label}</span>";
}

/** Technician name */
$tech_name = "Technician";
try {
  $st = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
  $st->execute([$tech_id]);
  $tech_name = $st->fetchColumn() ?: $tech_name;
} catch (Throwable $e) {}

$table = "client_requests";

$assigned_count = 0;
$inprogress_count = 0;
$completed_week = 0;
$latest_request_id = null;
$assigned_rows = [];

if (!table_exists($pdo, $table)) {
  $error = "❌ Table <b>client_requests</b> not found.";
} else {
  try {
    // Assigned to THIS technician (anything not NULL)
    $st = $pdo->prepare("SELECT COUNT(*) FROM client_requests WHERE technician_id = ?");
    $st->execute([$tech_id]);
    $assigned_count = (int)$st->fetchColumn();

    // In progress (Assigned + Processing + Pending)
    $st = $pdo->prepare("
      SELECT COUNT(*)
      FROM client_requests
      WHERE technician_id = ?
        AND LOWER(status) IN ('assigned','processing','pending')
    ");
    $st->execute([$tech_id]);
    $inprogress_count = (int)$st->fetchColumn();

    // Completed in last 7 days (uses created_at because you don't have updated_at)
    $st = $pdo->prepare("
      SELECT COUNT(*)
      FROM client_requests
      WHERE technician_id = ?
        AND LOWER(status) = 'completed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $st->execute([$tech_id]);
    $completed_week = (int)$st->fetchColumn();

    // Latest request id (for Open Latest Request)
    $st = $pdo->prepare("
      SELECT id
      FROM client_requests
      WHERE technician_id = ?
      ORDER BY id DESC
      LIMIT 1
    ");
    $st->execute([$tech_id]);
    $latest_request_id = $st->fetchColumn();

    // Last 8 assigned requests
    $st = $pdo->prepare("
      SELECT id, elevator_id, category, priority, status, created_at
      FROM client_requests
      WHERE technician_id = ?
      ORDER BY id DESC
      LIMIT 8
    ");
    $st->execute([$tech_id]);
    $assigned_rows = $st->fetchAll(PDO::FETCH_ASSOC);

  } catch (Throwable $e) {
    $error = "❌ Error loading technician dashboard.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Technician Dashboard | Sonic Elevator Ltd.</title>

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
    .table td,.table th{ vertical-align:middle; }
  </style>
</head>

<body>

<div class="topbar py-3">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="fa-solid fa-elevator fs-4" style="color:#00C2CB;"></i>
      <div>
        <div class="brand">Sonic Elevator Ltd.</div>
        <small class="muted">Technician Portal</small>
      </div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <span class="badge rounded-pill text-bg-light border">
        <i class="fa-solid fa-user-gear me-1" style="color:#00C2CB;"></i>
        <?php echo htmlspecialchars($tech_name); ?>
      </span>
      <a href="logout.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
      </a>
    </div>
  </div>
</div>

<div class="container py-5">

  <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
      <h2 class="fw-bold mb-1">Dashboard</h2>
      
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a href="<?php echo $latest_request_id ? ('technician-request-details.php?id='.(int)$latest_request_id) : '#'; ?>"
         class="btn btn-sonic rounded-pill px-4 <?php echo $latest_request_id ? '' : 'disabled'; ?>">
        <i class="fa-solid fa-folder-open me-2"></i>Open Latest Request
      </a>

      <a href="technician-requests.php" class="btn btn-outline-dark rounded-pill px-4">
        <i class="fa-solid fa-list-check me-2"></i>My Requests
      </a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card card-soft p-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-pill"><i class="fa-solid fa-clipboard-list fs-5"></i></div>
          <div>
            <div class="muted">Assigned Requests</div>
            <div class="fs-3 fw-bold mb-0"><?php echo (int)$assigned_count; ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-soft p-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-pill"><i class="fa-solid fa-spinner fs-5"></i></div>
          <div>
            <div class="muted">In Progress</div>
            <div class="fs-3 fw-bold mb-0"><?php echo (int)$inprogress_count; ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-soft p-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-pill"><i class="fa-solid fa-circle-check fs-5"></i></div>
          <div>
            <div class="muted">Completed (7 Days)</div>
            <div class="fs-3 fw-bold mb-0"><?php echo (int)$completed_week; ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card card-soft p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="fw-bold mb-0">
        <i class="fa-solid fa-inbox me-2" style="color:var(--sonic)"></i>
        Latest Assigned Requests
      </h5>
      <a href="technician-requests.php" class="btn btn-outline-dark rounded-pill btn-sm">
        View All <i class="fa-solid fa-arrow-right ms-2"></i>
      </a>
    </div>

    <div class="table-responsive mt-3">
      <table class="table align-middle mb-0">
        <thead>
          <tr class="text-secondary">
            <th>ID</th>
            <th>Elevator</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Status</th>
            <th class="text-end">Open</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($assigned_rows)): ?>
            <tr class="text-secondary"><td colspan="6">No requests assigned yet.</td></tr>
          <?php else: ?>
            <?php foreach ($assigned_rows as $r): ?>
              <tr>
                <td class="fw-semibold"><?php echo htmlspecialchars("#".$r['id']); ?></td>
                <td><?php echo htmlspecialchars($r['elevator_id']); ?></td>
                <td><?php echo htmlspecialchars($r['category']); ?></td>
                <td><?php echo htmlspecialchars($r['priority']); ?></td>
                <td><?php echo status_badge($r['status']); ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-dark rounded-pill"
                     href="technician-request-details.php?id=<?php echo (int)$r['id']; ?>">
                    Open <i class="fa-solid fa-arrow-right ms-1"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>