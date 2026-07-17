<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'client') {
  header("Location: login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$rows = [];

try {
  $st = $pdo->prepare("
    SELECT id, elevator_id, last_service_date, next_date, maintenance_type, status, created_at
    FROM maintenance_schedules
    WHERE user_id = ?
    ORDER BY next_date ASC, id DESC
  ");
  $st->execute([$user_id]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // ignore (show empty)
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Maintenance | Client</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold mb-0">My Maintenance Schedule</h3>
    <a href="client-dashboard.php" class="btn btn-outline-dark rounded-pill">Back</a>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Elevator</th>
            <th>Last Service</th>
            <th>Next Due</th>
            <th>Type</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="text-secondary">No maintenance schedules yet.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>#<?php echo (int)$r['id']; ?></td>
              <td><?php echo htmlspecialchars($r['elevator_id']); ?></td>
              <td><?php echo $r['last_service_date'] ? htmlspecialchars($r['last_service_date']) : "-"; ?></td>
              <td><?php echo htmlspecialchars($r['next_date']); ?></td>
              <td><?php echo htmlspecialchars($r['maintenance_type']); ?></td>
              <td><?php echo htmlspecialchars($r['status']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>