<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

/* Manager / Admin protection */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
  header("Location: login.php");
  exit;
}

/* -------------------- Load technicians list -------------------- */
$technicians = [];
try {
  $stTech = $pdo->prepare("SELECT id, name FROM users WHERE LOWER(role)='technician' ORDER BY name ASC");
  $stTech->execute();
  $technicians = $stTech->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $technicians = [];
}

/* -------------------- Update request (status + assignment) -------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $id = (int)($_POST['id'] ?? 0);
  $status = trim($_POST['status'] ?? "");
  $technician_id = trim($_POST['technician_id'] ?? ""); // may be "" (no change)

  $allowed = ['Submitted','Assigned','Processing','Pending','Completed','Closed'];
  if ($id > 0 && in_array($status, $allowed)) {

    // If manager selected a technician, assign it + set assigned_at.
    // If status is still Submitted but technician chosen, force status to Assigned (optional but recommended).
    $assigning = ($technician_id !== "");
    if ($assigning && $status === "Submitted") {
      $status = "Assigned";
    }

    try {
      if ($assigning) {
        $stmt = $pdo->prepare("
          UPDATE client_requests
          SET status = ?,
              technician_id = ?,
              assigned_at = NOW()
          WHERE id = ?
        ");
        $stmt->execute([$status, (int)$technician_id, $id]);
      } else {
        // Only update status (do not touch technician assignment)
        $stmt = $pdo->prepare("UPDATE client_requests SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
      }
    } catch (Throwable $e) {
      // You can show $e->getMessage() for debugging if you want
    }
  }
}

/* -------------------- Fetch all client requests -------------------- */
$stmt = $pdo->query("
  SELECT cr.*,
         u.name AS client_name,
         t.name AS technician_name
  FROM client_requests cr
  LEFT JOIN users u ON u.id = cr.user_id
  LEFT JOIN users t ON t.id = cr.technician_id
  ORDER BY cr.id DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Badge helpers */
function priority_badge($p){
  return match(strtolower($p)){
    'high' => 'danger',
    'medium' => 'warning',
    'low' => 'secondary',
    default => 'dark'
  };
}
function status_badge($s){
  return match(strtolower($s)){
    'submitted' => 'warning',
    'assigned' => 'info',
    'processing' => 'primary',
    'pending' => 'secondary',
    'completed' => 'success',
    'closed' => 'dark',
    default => 'secondary'
  };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Service Requests | Manager</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --sonic:#00C2CB; }
    body{ background:#f7fbfc; }
    .card-soft{ border:1px solid rgba(0,0,0,.08); box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:16px; }
    .btn-sonic{ background:var(--sonic); border:none; font-weight:700; }
  </style>
</head>

<body class="p-4">

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Client Service Requests</h3>
    <a href="manager-dashboard.php" class="btn btn-outline-dark btn-sm">Back</a>
  </div>

  <div class="card card-soft p-4">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th>Request ID</th>
            <th>Client</th>
            <th>Elevator</th>
            <th>Issue</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Technician</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>

        <?php if (!$requests): ?>
          <tr>
            <td colspan="8" class="text-center text-muted">No service requests found.</td>
          </tr>
        <?php endif; ?>

        <?php foreach ($requests as $r): ?>
          <tr>
            <td><strong>SR-<?php echo (int)$r['id']; ?></strong></td>

            <td><?php echo htmlspecialchars($r['client_name'] ?? 'Client'); ?></td>

            <td><?php echo htmlspecialchars($r['elevator_id'] ?? '-'); ?></td>

            <td><?php echo htmlspecialchars($r['category'] ?? '-'); ?></td>

            <td>
              <span class="badge bg-<?php echo priority_badge($r['priority'] ?? ''); ?>">
                <?php echo htmlspecialchars($r['priority'] ?? '-'); ?>
              </span>
            </td>

            <td>
              <span class="badge bg-<?php echo status_badge($r['status'] ?? ''); ?>">
                <?php echo htmlspecialchars($r['status'] ?? '-'); ?>
              </span>
            </td>

            <td>
              <small class="text-secondary d-block mb-1">
                <?php echo htmlspecialchars($r['technician_name'] ?? 'Not Assigned'); ?>
              </small>

              <form method="post" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">

                <!-- status -->
                <select name="status" class="form-select form-select-sm" style="min-width:140px;">
                  <?php
                    $statuses = ['Submitted','Assigned','Processing','Pending','Completed','Closed'];
                    foreach ($statuses as $s) {
                      $sel = ($s === ($r['status'] ?? '')) ? 'selected' : '';
                      echo "<option $sel>$s</option>";
                    }
                  ?>
                </select>

                <!-- technician -->
                <select name="technician_id" class="form-select form-select-sm" style="min-width:190px;">
                  <option value="">(keep current)</option>
                  <?php foreach ($technicians as $t): ?>
                    <?php
                      $sel = ((int)$r['technician_id'] === (int)$t['id']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo (int)$t['id']; ?>" <?php echo $sel; ?>>
                      <?php echo htmlspecialchars($t['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <button name="update_status" class="btn btn-sm btn-sonic">
                  <i class="fa-solid fa-check"></i>
                </button>
              </form>
            </td>

            <td>
              <small class="text-secondary">
                <?php echo !empty($r['assigned_at']) ? htmlspecialchars($r['assigned_at']) : '-'; ?>
              </small>
            </td>
          </tr>
        <?php endforeach; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>