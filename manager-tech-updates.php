<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

/* Only manager/admin can access */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
  header("Location: login.php");
  exit;
}

/* ---------- Helpers ---------- */
function table_exists(PDO $pdo, string $table): bool {
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = ?
  ");
  $stmt->execute([$table]);
  return (int)$stmt->fetchColumn() > 0;
}

function col_exists(PDO $pdo, string $table, string $col): bool {
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = ?
      AND column_name = ?
  ");
  $stmt->execute([$table, $col]);
  return (int)$stmt->fetchColumn() > 0;
}

function status_badge(string $s): string {
  $map = [
    'submitted'  => 'secondary',
    'assigned'   => 'info',
    'processing' => 'primary',
    'pending'    => 'warning',
    'completed'  => 'success',
    'closed'     => 'dark',
  ];
  $key = strtolower(trim($s));
  $c = $map[$key] ?? 'secondary';
  return "<span class=\"badge rounded-pill text-bg-$c\">" . htmlspecialchars(ucfirst($key)) . "</span>";
}

function priority_badge(string $p): string {
  $map = ['low'=>'secondary','medium'=>'warning','high'=>'danger','critical'=>'danger'];
  $key = strtolower(trim($p));
  $c = $map[$key] ?? 'dark';
  return "<span class=\"badge rounded-pill text-bg-$c\">" . htmlspecialchars(ucfirst($key)) . "</span>";
}

/* ---------- Guards ---------- */
if (!table_exists($pdo, "technician_notes")) {
  die("Table <b>technician_notes</b> not found. Create it first.");
}

/* check optional columns */
$has_is_read = col_exists($pdo, "technician_notes", "is_read");
$has_suggest = col_exists($pdo, "technician_notes", "suggested_status");

/* ---------- Actions ---------- */
$success = "";
$error = "";

/* Mark a note as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
  $note_id = (int)($_POST['note_id'] ?? 0);

  if ($note_id > 0 && $has_is_read) {
    try {
      $st = $pdo->prepare("UPDATE technician_notes SET is_read = 1 WHERE id = ?");
      $st->execute([$note_id]);
      $success = "✅ Marked as read.";
    } catch (Throwable $e) {
      $error = "❌ Failed to mark as read.";
    }
  } else {
    $error = "❌ is_read column not found. Run ALTER TABLE to add it.";
  }
}

/* Manager updates FINAL status in client_requests */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_final_status'])) {
  $request_id = (int)($_POST['request_id'] ?? 0);
  $final_status = trim($_POST['final_status'] ?? '');

  $allowed = ['Submitted','Assigned','Processing','Pending','Completed','Closed'];
  if ($request_id <= 0 || !in_array($final_status, $allowed)) {
    $error = "❌ Invalid request/status.";
  } else {
    try {
      $st = $pdo->prepare("UPDATE client_requests SET status = ? WHERE id = ?");
      $st->execute([$final_status, $request_id]);
      $success = "✅ Client status updated (final).";
    } catch (Throwable $e) {
      $error = "❌ Failed to update client status.";
    }
  }
}

/* ---------- Fetch notes ---------- */
try {
  $sql = "
    SELECT
      tn.id AS note_id,
      tn.request_id,
      tn.technician_id,
      tn.note,
      tn.created_at,
      " . ($has_is_read ? "tn.is_read" : "0 AS is_read") . ",
      " . ($has_suggest ? "tn.suggested_status" : "NULL AS suggested_status") . ",

      cr.elevator_id,
      cr.category,
      cr.priority,
      cr.status AS client_status,

      tech.name AS technician_name
    FROM technician_notes tn
    LEFT JOIN client_requests cr ON cr.id = tn.request_id
    LEFT JOIN users tech ON tech.id = tn.technician_id
    ORDER BY " . ($has_is_read ? "tn.is_read ASC," : "") . " tn.id DESC
    LIMIT 100
  ";
  $notes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $notes = [];
  $error = "❌ Failed to load technician updates.";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Technician Updates | Manager</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --sonic:#00C2CB; --border:rgba(15,23,42,.08); }
    body{ background:#f7fbfc; }
    .card-soft{ background:#fff; border:1px solid var(--border); border-radius:16px; box-shadow:0 10px 30px rgba(2,8,23,.06); }
    .btn-sonic{ background:var(--sonic); border:none; font-weight:700; }
    .btn-sonic:hover{ background:#06aeb6; }
    .muted{ color:#64748b; }
    .unread{ border-left:6px solid #dc3545; }
  </style>
</head>

<body class="p-4">

<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-1"><i class="fa-solid fa-bell me-2" style="color:var(--sonic)"></i>Technician Updates</h3>
      <div class="muted">Technicians send notes here. Manager approves and updates final client status.</div>
    </div>
    <a href="manager-dashboard.php" class="btn btn-outline-dark rounded-pill">
      <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <div class="card-soft p-4">
    <?php if (empty($notes)): ?>
      <div class="text-secondary">No technician updates found.</div>
    <?php else: ?>

      <?php foreach ($notes as $n): ?>
        <?php
          $isUnread = ((int)($n['is_read'] ?? 0) === 0);
          $reqId = (int)$n['request_id'];
        ?>
        <div class="p-3 mb-3 rounded-3 bg-light <?php echo $isUnread ? 'unread' : ''; ?>">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-bold">
                Request #<?php echo $reqId; ?>
                <span class="ms-2 text-secondary">Elevator:</span> <?php echo htmlspecialchars($n['elevator_id'] ?? '-'); ?>
              </div>
              <div class="muted">
                Technician: <b><?php echo htmlspecialchars($n['technician_name'] ?? ('#'.$n['technician_id'])); ?></b>
                • <?php echo htmlspecialchars($n['created_at'] ?? ''); ?>
              </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
              <?php echo priority_badge($n['priority'] ?? ''); ?>
              <?php echo status_badge($n['client_status'] ?? ''); ?>
            </div>
          </div>

          <hr class="my-3">

          <div class="mb-2">
            <div class="fw-semibold">Technician Note</div>
            <div class="bg-white border rounded-3 p-3">
              <?php echo nl2br(htmlspecialchars($n['note'] ?? '')); ?>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
            <div class="muted">
              Suggested Status:
              <b><?php echo htmlspecialchars($n['suggested_status'] ?? '—'); ?></b>
              <?php if ($isUnread): ?>
                <span class="badge text-bg-danger ms-2">Unread</span>
              <?php else: ?>
                <span class="badge text-bg-secondary ms-2">Read</span>
              <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2">

              <?php if ($has_is_read && $isUnread): ?>
                <form method="POST" class="m-0">
                  <input type="hidden" name="note_id" value="<?php echo (int)$n['note_id']; ?>">
                  <button class="btn btn-outline-dark btn-sm rounded-pill" name="mark_read">
                    <i class="fa-solid fa-check me-1"></i>Mark as Read
                  </button>
                </form>
              <?php endif; ?>

              <!-- Final client status update -->
              <form method="POST" class="m-0 d-flex gap-2 align-items-center">
                <input type="hidden" name="request_id" value="<?php echo $reqId; ?>">
                <select name="final_status" class="form-select form-select-sm" style="width:180px" required>
                  <?php
                    $statuses = ['Submitted','Assigned','Processing','Pending','Completed','Closed'];
                    foreach ($statuses as $s) {
                      $sel = (strtolower($s) === strtolower($n['client_status'] ?? '')) ? 'selected' : '';
                      echo "<option $sel>$s</option>";
                    }
                  ?>
                </select>
                <button class="btn btn-sonic btn-sm rounded-pill" name="update_final_status">
                  <i class="fa-solid fa-floppy-disk me-1"></i>Update Client
                </button>
              </form>

            </div>
          </div>

        </div>
      <?php endforeach; ?>

    <?php endif; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>