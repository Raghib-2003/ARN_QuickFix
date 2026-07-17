<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

/* Only manager/admin can access */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
  header("Location: login.php");
  exit;
}

/* Helpers */
function table_exists(PDO $pdo, string $table): bool {
  $sql = "SELECT COUNT(*) FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_name = ?";
  $st = $pdo->prepare($sql);
  $st->execute([$table]);
  return (int)$st->fetchColumn() > 0;
}

function col_exists(PDO $pdo, string $table, string $col): bool {
  $sql = "SELECT COUNT(*) FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
  $st = $pdo->prepare($sql);
  $st->execute([$table, $col]);
  return (int)$st->fetchColumn() > 0;
}

function badge_for_due(?string $nextDate): string {
  if (!$nextDate) return '<span class="badge bg-secondary">Unknown</span>';

  $today = new DateTime(date("Y-m-d"));
  $next  = new DateTime($nextDate);

  if ($next < $today) return '<span class="badge bg-danger">Overdue</span>';

  $diff = (int)$today->diff($next)->format("%a");
  if ($diff <= 7) return '<span class="badge bg-warning text-dark">Due Soon</span>';

  return '<span class="badge bg-success">On Schedule</span>';
}

$table = "maintenance_schedules";
$error = "";
$success = "";

/* Validate table + required columns */
$requiredCols = ["user_id","elevator_id","last_service_date","next_date","maintenance_type","status","created_at"];
if (!table_exists($pdo, $table)) {
  $error = "Maintenance table <b>$table</b> not found. Create it first.";
} else {
  foreach ($requiredCols as $c) {
    if (!col_exists($pdo, $table, $c)) {
      $error = "Column <b>$c</b> missing in <b>$table</b>. Fix table structure first.";
      break;
    }
  }
}

/* Handle add schedule */
if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
  $user_id = (int)($_POST['user_id'] ?? 0);
  $elevator_id = trim($_POST['elevator_id'] ?? "");
  $last_service_date = trim($_POST['last_service_date'] ?? "");
  $next_date = trim($_POST['next_date'] ?? "");
  $maintenance_type = trim($_POST['maintenance_type'] ?? "");
  $status = trim($_POST['status'] ?? "On Schedule");

  if ($user_id <= 0 || $elevator_id === "" || $next_date === "" || $maintenance_type === "") {
    $error = "Please fill required fields (Client, Elevator ID, Next Due, Type).";
  } else {
    try {
      $sql = "INSERT INTO $table (user_id, elevator_id, last_service_date, next_date, maintenance_type, status)
              VALUES (?, ?, ?, ?, ?, ?)";
      $st = $pdo->prepare($sql);
      $st->execute([
        $user_id,
        $elevator_id,
        ($last_service_date !== "" ? $last_service_date : null),
        $next_date,
        $maintenance_type,
        $status
      ]);
      $success = "✅ Maintenance schedule added successfully.";
    } catch (Throwable $e) {
      $error = "❌ Could not add schedule. Check DB column types.";
    }
  }
}

/* Load rows */
$rows = [];
$total = 0;
$overdue = 0;

if (!$error) {
  try {
    $sql = "SELECT id, user_id, elevator_id, last_service_date, next_date, maintenance_type, status, created_at
            FROM $table
            ORDER BY next_date ASC, id DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $total = count($rows);
    foreach ($rows as $r) {
      if (!empty($r['next_date']) && $r['next_date'] < date("Y-m-d")) $overdue++;
    }
  } catch (Throwable $e) {
    $error = "Something went wrong loading maintenance data.";
  }
}

/* Load clients for dropdown */
$clients = [];
try {
  $st = $pdo->query("SELECT id, name, email FROM users WHERE role='client' ORDER BY name ASC");
  $clients = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Maintenance Overview | Manager</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --sonic:#00C2CB; }
    body{ background:#f7fbfc; }
    .card-soft{ border:1px solid rgba(0,0,0,.08); box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:16px; }
    .btn-sonic{ background:var(--sonic); border:none; font-weight:700; }
    .btn-sonic:hover{ background:#06aeb6; }
    .muted{ color:#64748b; }
  </style>
</head>

<body class="p-4">
<div class="container">

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
      <h3 class="fw-bold mb-1">Maintenance Schedule</h3>
      <p class="muted mb-0">Manager assigns periodic maintenance → Client sees it in their portal.</p>
    </div>

    <div class="d-flex gap-2">
      <a href="manager-dashboard.php" class="btn btn-outline-dark rounded-pill">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
      </a>
      <a href="manager-requests.php" class="btn btn-sonic rounded-pill px-4">
        <i class="fa-solid fa-clipboard-check me-2"></i>Requests
      </a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <?php if (!$error): ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card card-soft p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="muted">Total Scheduled</div>
              <div class="fs-3 fw-bold"><?php echo $total; ?></div>
            </div>
            <i class="fa-solid fa-calendar-check fs-2" style="color:var(--sonic)"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card card-soft p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="muted">Overdue</div>
              <div class="fs-3 fw-bold"><?php echo $overdue; ?></div>
            </div>
            <i class="fa-solid fa-triangle-exclamation fs-2 text-danger"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Add schedule -->
    <div class="card card-soft p-4 mb-4">
      <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus me-2" style="color:var(--sonic)"></i>Add Maintenance</h5>

      <form method="POST" class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Client *</label>
          <select name="user_id" class="form-select" required>
            <option value="">Select client</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>">
                <?php echo htmlspecialchars($c['name'] . " (" . $c['email'] . ")"); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Elevator ID *</label>
          <input name="elevator_id" class="form-control" placeholder="Example: ELV-DHK-21" required>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Maintenance Type *</label>
          <input name="maintenance_type" class="form-control" placeholder="Monthly / Quarterly / Annual" required>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Last Service Date</label>
          <input type="date" name="last_service_date" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Next Due Date *</label>
          <input type="date" name="next_date" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option>On Schedule</option>
            <option>Due Soon</option>
            <option>Overdue</option>
            <option>Completed</option>
          </select>
        </div>

        <div class="col-12">
          <button class="btn btn-sonic rounded-pill px-4" type="submit" name="add_schedule">
            <i class="fa-solid fa-floppy-disk me-2"></i>Save Schedule
          </button>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="card card-soft p-4">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Client</th>
              <th>Elevator</th>
              <th>Last Service</th>
              <th>Next Due</th>
              <th>Type</th>
              <th>Status</th>
              <th>Due Check</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-secondary">No maintenance schedules found.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="fw-semibold">#<?php echo (int)$r['id']; ?></td>
                <td><?php echo (int)$r['user_id']; ?></td>
                <td><?php echo htmlspecialchars($r['elevator_id']); ?></td>
                <td class="text-secondary"><?php echo $r['last_service_date'] ? htmlspecialchars($r['last_service_date']) : "-"; ?></td>
                <td><?php echo htmlspecialchars($r['next_date']); ?></td>
                <td><?php echo htmlspecialchars($r['maintenance_type']); ?></td>
                <td><span class="badge bg-dark"><?php echo htmlspecialchars($r['status']); ?></span></td>
                <td><?php echo badge_for_due($r['next_date']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>