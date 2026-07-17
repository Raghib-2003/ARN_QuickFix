<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports & Metrics | Manager</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{ --sonic:#00C2CB; }
    body{ background:#f7fbfc; }
    .card-soft{ border:1px solid rgba(0,0,0,.08); box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:16px; }
  </style>
</head>

<body class="p-4">

<div class="container">
  <h3 class="fw-bold mb-4">Reports & Performance Metrics</h3>

  <div class="row g-4">

    <div class="col-md-4">
      <div class="card card-soft p-4">
        <h6 class="text-muted">Total Requests</h6>
        <h2 class="fw-bold">124</h2>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-soft p-4">
        <h6 class="text-muted">Avg. Resolution Time</h6>
        <h2 class="fw-bold">3.2 Days</h2>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-soft p-4">
        <h6 class="text-muted">Recurring Faults</h6>
        <h2 class="fw-bold">18%</h2>
      </div>
    </div>

  </div>

  <button class="btn btn-outline-secondary rounded-pill mt-4">
    <i class="fa-solid fa-file-export me-2"></i>Export Report (PDF / CSV)
  </button>

</div>

</body>
</html>