<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * REQUIRED SESSION VALUES (set these after login):
 * $_SESSION['user_id']
 * $_SESSION['role']  // must be "client"
 */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
  header("Location: login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$error = "";
$success = "";

/** ---------- Helpers ---------- */
function table_exists(PDO $pdo, string $table): bool {
  $sql = "
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = ?
    LIMIT 1
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
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

/** ---------- Load Client Info ---------- */
$client_name = "Client";
$client_email = "";
try {
  $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  if ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $client_name = $u['name'] ?? $client_name;
    $client_email = $u['email'] ?? "";
  }
} catch (Throwable $e) {}

/** ---------- Handle New Request Submission ---------- */
$table = "client_requests";

if (!table_exists($pdo, $table)) {
  $error = "❌ Table 'client_requests' not found in database. Please create it first.";
}

if ($error === "" && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {

  $elevator_id    = trim($_POST['elevator_id'] ?? "");
  $category       = trim($_POST['category'] ?? "");
  $priority       = trim($_POST['priority'] ?? "");
  $description    = trim($_POST['description'] ?? "");
  $contact_method = trim($_POST['contact_method'] ?? "Phone");

  if ($elevator_id === "" || $category === "" || $priority === "" || $description === "") {
    $error = "Please fill all required fields.";
  } else {
    try {
      // ✅ This matches your real DB columns exactly
      $stmt = $pdo->prepare("
        INSERT INTO client_requests
          (user_id, elevator_id, category, priority, description, contact_method, status)
        VALUES
          (:user_id, :elevator_id, :category, :priority, :description, :contact_method, 'Submitted')
      ");

      $stmt->execute([
        ":user_id"        => $user_id,
        ":elevator_id"    => $elevator_id,
        ":category"       => $category,
        ":priority"       => $priority,
        ":description"    => $description,
        ":contact_method" => $contact_method
      ]);

      $success = "✅ Request submitted successfully!";
    } catch (PDOException $e) {
      // show real reason (so you can debug instantly)
      $error = "❌ Could not submit request: " . $e->getMessage();
    }
  }
}

/** ---------- Fetch KPIs + Recent Requests ---------- */
$total_requests = 0;
$open_requests  = 0;
$overdue_maint  = 0;
$recent_requests = [];

try {
  // Total Requests (this client only)
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_requests WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $total_requests = (int)$stmt->fetchColumn();

  // Open Requests
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM client_requests
    WHERE user_id = ?
      AND LOWER(status) IN ('submitted','assigned','processing','pending')
  ");
  $stmt->execute([$user_id]);
  $open_requests = (int)$stmt->fetchColumn();

  // Recent (latest 5)
  $stmt = $pdo->prepare("
    SELECT id, elevator_id, category, priority, status, created_at
    FROM client_requests
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 5
  ");
  $stmt->execute([$user_id]);
  $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Maintenance (optional table)
  if (table_exists($pdo, "maintenance_schedules")) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_schedules WHERE next_date < CURDATE()");
    $overdue_maint = (int)$stmt->fetchColumn();
  }
} catch (Throwable $e) {
  // keep defaults
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Client Dashboard | Sonic Elevator Ltd.</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --sonic:#00C2CB; --ink:#0f172a; --muted:#64748b; --bg:#f7fbfc;
      --card:#ffffff; --border:rgba(15,23,42,.08);
    }
    body{ background:var(--bg); color:var(--ink); }
    .topbar{ background:linear-gradient(120deg, rgba(0,194,203,.14), #fff); border-bottom:1px solid var(--border); }
    .brand{ font-weight:900; letter-spacing:.2px; color:var(--sonic); }
    .card-soft{ background:var(--card); border:1px solid var(--border); border-radius:18px; box-shadow:0 12px 30px rgba(2,8,23,.06); }
    .btn-sonic{ background:var(--sonic); border:none; font-weight:800; border-radius:999px; }
    .btn-sonic:hover{ background:#06aeb6; }
    .icon-pill{ width:52px; height:52px; border-radius:16px; display:grid; place-items:center; background:rgba(0,194,203,.14); color:var(--sonic); }
    .muted{ color:var(--muted); }
    .table td, .table th{ vertical-align:middle; }
    .section-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
  </style>
</head>

<body>

<!-- Top Bar -->
<div class="topbar py-3">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i class="fa-solid fa-elevator fs-4" style="color:var(--sonic)"></i>
      <div>
        <div class="brand">Sonic Elevator Ltd.</div>
        <small class="muted">Client Portal</small>
      </div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <span class="badge rounded-pill text-bg-light border">
        <i class="fa-solid fa-user me-1" style="color:var(--sonic)"></i>
        <?php echo htmlspecialchars($client_name); ?>
      </span>

      <a class="btn btn-outline-dark btn-sm rounded-pill" href="logout.php">
        <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
      </a>
    </div>
  </div>
</div>

<div class="container py-5">

  <!-- Header -->
  <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
      <h2 class="fw-bold mb-1">Client Dashboard</h2>
      
    </div>

    <div class="d-flex flex-wrap gap-2">
      <a href="#newRequest" class="btn btn-sonic px-4 py-2">
        <i class="fa-solid fa-circle-plus me-2"></i>New Request
      </a>
      <a href="#recentRequests" class="btn btn-outline-dark rounded-pill px-4 py-2">
        <i class="fa-solid fa-list-check me-2"></i>Recent Requests
      </a>
      <a href="#maintenance" class="btn btn-outline-dark rounded-pill px-4 py-2">
        <i class="fa-solid fa-calendar-check me-2"></i>Maintenance
      </a>
      <a href="#downloads" class="btn btn-outline-dark rounded-pill px-4 py-2">
        <i class="fa-solid fa-download me-2"></i>Downloads
      </a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card-soft p-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-pill"><i class="fa-solid fa-clipboard-list fs-5"></i></div>
          <div>
            <div class="muted">Total Requests</div>
            <div class="fs-2 fw-bold mb-0"><?php echo (int)$total_requests; ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card-soft p-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-pill"><i class="fa-solid fa-hourglass-half fs-5"></i></div>
          <div>
            <div class="muted">Open Requests</div>
            <div class="fs-2 fw-bold mb-0"><?php echo (int)$open_requests; ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card-soft p-4 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-pill"><i class="fa-solid fa-triangle-exclamation fs-5"></i></div>
          <div>
            <div class="muted">Overdue Maintenance</div>
            <div class="fs-2 fw-bold mb-0"><?php echo (int)$overdue_maint; ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- New Request -->
    <div class="col-lg-5" id="newRequest">
      <div class="card-soft p-4">
        <div class="section-title mb-2">
          <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-paper-plane me-2" style="color:var(--sonic)"></i>
            Create Service Request
          </h5>
        </div>

        <form method="POST" class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold">Elevator ID *</label>
            <input name="elevator_id" class="form-control" placeholder="Example: ELV-DHK-21" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Problem Category *</label>
            <select name="category" class="form-select" required>
              <option value="">Select</option>
              <option>Door Issue</option>
              <option>Noise/Vibration</option>
              <option>Leveling Problem</option>
              <option>Power/Control Fault</option>
              <option>Emergency/Trapped</option>
              <option>Other</option>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Priority *</label>
            <select name="priority" class="form-select" required>
              <option value="">Select</option>
              <option>Low</option>
              <option>Medium</option>
              <option>High</option>
              <option>Critical</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Description *</label>
            <textarea name="description" class="form-control" rows="3" required></textarea>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Preferred Contact</label>
            <select name="contact_method" class="form-select">
              <option>Phone</option>
              <option>Email</option>
              <option>WhatsApp</option>
            </select>
          </div>

          <div class="col-12 d-grid">
            <button class="btn btn-sonic py-3" type="submit" name="submit_request">
              <i class="fa-solid fa-circle-check me-2"></i>Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Recent Requests -->
    <div class="col-lg-7" id="recentRequests">
      <div class="card-soft p-4">
        <div class="section-title mb-2">
          <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-list me-2" style="color:var(--sonic)"></i>
            Recent Requests
          </h5>
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr class="text-secondary">
                <th>ID</th>
                <th>Elevator</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_requests)): ?>
                <tr class="text-secondary"><td colspan="6">No requests yet.</td></tr>
              <?php else: ?>
                <?php foreach ($recent_requests as $r): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars("#".$r['id']); ?></td>
                    <td><?php echo htmlspecialchars($r['elevator_id'] ?? "-"); ?></td>
                    <td><?php echo htmlspecialchars($r['category'] ?? "-"); ?></td>
                    <td><?php echo htmlspecialchars($r['priority'] ?? "-"); ?></td>
                    <td><?php echo status_badge($r['status'] ?? "Submitted"); ?></td>
                    <td class="text-secondary"><?php echo htmlspecialchars($r['created_at'] ?? ""); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
          <a href="client-requests.php" class="btn btn-outline-dark rounded-pill">
            View All Requests <i class="fa-solid fa-arrow-right ms-2"></i>
          </a>
        </div>
      </div>

<!-- Maintenance -->
<div class="card-soft p-4 mt-4" id="maintenance">
  <div class="section-title mb-2">
    <h5 class="fw-bold mb-0">
      <i class="fa-solid fa-calendar-check me-2" style="color:var(--sonic)"></i>
      Maintenance Overview
    </h5>
  </div>

  <p class="muted mb-3">
    Upcoming and overdue elevator maintenance schedules 
  </p>

  <?php
  $maintenance = [];
  try {
    if (table_exists($pdo, "maintenance_schedules")) {
      $stmt = $pdo->prepare("
        SELECT elevator_id, last_service_date, next_date, maintenance_type, status
        FROM maintenance_schedules
        WHERE user_id = ?
        ORDER BY next_date ASC
        LIMIT 5
      ");
      $stmt->execute([$user_id]);
      $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (Throwable $e) {
    $maintenance = [];
  }
  ?>

  <?php if (!table_exists($pdo, "maintenance_schedules")): ?>
    <div class="alert alert-warning mb-0">
      Maintenance table not found yet. Please create <b>maintenance_schedules</b>.
    </div>

  <?php elseif (empty($maintenance)): ?>
    <div class="alert alert-info mb-0">
      No maintenance schedules assigned to you yet.
    </div>

  <?php else: ?>
    <div class="d-flex flex-column gap-2">
      <?php foreach ($maintenance as $m): ?>
        <?php
          $st = strtolower(trim($m['status'] ?? ''));
          $badgeClass = match($st) {
            'overdue'   => 'danger',
            'due soon'  => 'warning',
            'completed' => 'success',
            default     => 'info',
          };
        ?>
        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 bg-white">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($m['elevator_id']) ?></div>
            <small class="muted">
              Type: <?= htmlspecialchars($m['maintenance_type'] ?? '-') ?>
              &nbsp;|&nbsp;
              Last: <?= !empty($m['last_service_date']) ? htmlspecialchars($m['last_service_date']) : 'N/A' ?>
              &nbsp;|&nbsp;
              Next: <?= htmlspecialchars($m['next_date']) ?>
            </small>
          </div>

          <span class="badge rounded-pill text-bg-<?= $badgeClass ?>">
            <?= htmlspecialchars($m['status'] ?? 'Scheduled') ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-end mt-3">
    <a href="client-maintenance.php" class="btn btn-outline-dark rounded-pill">
      View Maintenance Details <i class="fa-solid fa-arrow-right ms-2"></i>
    </a>
  </div>
</div>

<!-- Downloads -->
<div class="card-soft p-4 mt-4" id="downloads">
  <h5 class="fw-bold mb-2">
    <i class="fa-solid fa-download me-2" style="color:var(--sonic)"></i>
    Downloads
  </h5>

  <p class="muted mb-3">
    Open a printable report and use <b>Ctrl + P</b> → <b>Save as PDF</b>.
  </p>

  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-dark rounded-pill" target="_blank" href="print-requests.php">
      <i class="fa-solid fa-file-pdf me-2"></i>Service Requests (PDF)
    </a>

    <!-- client version, NOT manager version -->
    <a class="btn btn-outline-dark rounded-pill" target="_blank" href="client-maintenance.php">
      <i class="fa-solid fa-file-pdf me-2"></i>Maintenance Overview (PDF)
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>