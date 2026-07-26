<?php
require_once "config.php";
$error = "";
?>

<head>
    <meta charset="utf-8">
    <title>Pricing | ARN QuickFix Ltd. </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700&family=Roboto:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Topbar Start -->
    <div class="container-fluid py-2 border-bottom d-none d-lg-block">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-lg-start mb-2 mb-lg-0">
                    <div class="d-inline-flex align-items-center">
                        <a class="text-decoration-none text-body pe-3" href="#!"><i
                                class="bi bi-telephone me-2"></i>+012
                            345 6789</a>
                        <span class="text-body">|</span>
                        <a class="text-decoration-none text-body px-3" href="#!"><i
                                class="bi bi-envelope me-2"></i>info@example.com</a>
                    </div>
                </div>
                <div class="col-md-6 text-center text-lg-end">
                    <div class="d-inline-flex align-items-center">
                        <a class="text-body px-2" href="#!">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a class="text-body px-2" href="#!">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a class="text-body px-2" href="#!">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a class="text-body px-2" href="#!">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a class="text-body ps-2" href="#!">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid sticky-top bg-white shadow-sm mb-5">
        <div class="container">
            <nav class="navbar navbar-expand-lg bg-white navbar-light py-3 py-lg-0">
                     <a href="index.php" class="navbar-brand d-flex align-items-center">
                          <img src="img/logo.svg.svg"
                             alt="Sonic Elevator Ltd"
                                 class="navbar-logo">
                                 
                                 <h1 class="m-0  text-primary">ARN QuickFix Ltd.</h1>
                     </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0">
                        <a href="index.php" class="nav-item nav-link">Home</a>
                        <a href="about.php" class="nav-item nav-link">About</a>
                        <a href="service.php" class="nav-item nav-link">Service</a>
                        <a href="price.php" class="nav-item nav-link active">Pricing</a>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                            <div class="dropdown-menu m-0">
                                <a href="blog.php" class="dropdown-item">Blog Grid</a>
                                <a href="detail.php" class="dropdown-item">Blog Detail</a>
                                <a href="team.php" class="dropdown-item">The Team</a>
                                <a href="testimonial.php" class="dropdown-item">Testimonial</a>
                                <a href="appointment.php" class="dropdown-item">Appointment</a>
                                <a href="search.php" class="dropdown-item">Search</a>
                            </div>
                        </div>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->

<!-- Pricing Plan Start -->
<div class="container-fluid py-5" id="pricing">
  <div class="container">

    <div class="text-center mx-auto mb-5" style="max-width: 600px;">
      <h5 class="d-inline-block text-primary text-uppercase border-bottom border-5">
        Pricing Plans
      </h5>
      <h1 class="display-4">Our Service Packages</h1>
      <p class="mb-0">
        Transparent engineering packages and maintenance pricing across all machinery sectors.
      </p>
    </div>

    <div class="row g-4 justify-content-center">

      <!-- Plan 1: Elevator Services -->
      <div class="col-lg-4 col-md-6">
        <div class="bg-light rounded text-center p-4 h-100 shadow-sm border">
          <i class="fa fa-building fa-3x text-primary mb-3"></i>
          <h4 class="mb-2">Elevator & Lift</h4>
          <p class="text-muted">Installation & Modernization</p>

          <h2 class="text-primary mb-3">
            ৳ 9.5 – 22 Lakh
          </h2>

          <ul class="list-unstyled mb-4 lh-lg text-start ps-3">
            <li>✔ Residential & Commercial</li>
            <li>✔ Auto Door Systems</li>
            <li>✔ Safety Sensor Calibration</li>
            <li>✔ 1 Year Free Service Warranty</li>
          </ul>

          <a href="#contact" class="btn btn-primary rounded-pill px-4 py-2 w-100">
            Get Quotation
          </a>
        </div>
      </div>

      <!-- Plan 2: AC & HVAC Services (Highlighted/Primary Card) -->
      <div class="col-lg-4 col-md-6">
        <div class="bg-primary text-white rounded text-center p-4 h-100 shadow">
          <i class="fa fa-snowflake fa-3x mb-3 text-white"></i>
          <h4 class="mb-2 text-white">Air Conditioning</h4>
          <p class="text-white-50">Inverter, VRF & HVAC Systems</p>

          <h2 class="mb-3 text-white">
            ৳ 45k – 3.5 Lakh
          </h2>

          <ul class="list-unstyled mb-4 lh-lg text-start ps-3">
            <li>✔ Split, Cassette & VRF Setup</li>
            <li>✔ Precise Gas Refilling</li>
            <li>✔ Ducting & Ventilation Design</li>
            <li>✔ Compressor Replacement</li>
          </ul>

          <a href="#contact" class="btn btn-light rounded-pill px-4 py-2 w-100 text-primary fw-bold">
            Request Survey
          </a>
        </div>
      </div>

      <!-- Plan 3: Industrial Generators -->
      <div class="col-lg-4 col-md-6">
        <div class="bg-light rounded text-center p-4 h-100 shadow-sm border">
          <i class="fa fa-bolt fa-3x text-primary mb-3"></i>
          <h4 class="mb-2">Power Generators</h4>
          <p class="text-muted">Diesel & Gas Substation Units</p>

          <h2 class="text-primary mb-3">
            ৳ 2.5 – 12 Lakh
          </h2>

          <ul class="list-unstyled mb-4 lh-lg text-start ps-3">
            <li>✔ 10 kVA to 500+ kVA Units</li>
            <li>✔ Automatic Transfer Switch (ATS)</li>
            <li>✔ Soundproof Canopy Fitting</li>
            <li>✔ Engine Overhauling</li>
          </ul>

          <a href="#contact" class="btn btn-primary rounded-pill px-4 py-2 w-100">
            Get Generator Quote
          </a>
        </div>
      </div>

      <!-- Plan 4: Combined Corporate AMC (Optional Extra Center-Row Card) -->
      <div class="col-lg-6 col-md-10 mt-4">
        <div class="bg-dark text-white rounded p-4 shadow-sm border">
          <div class="row align-items-center text-center text-md-start">
            <div class="col-md-8">
              <h4 class="text-info mb-1"><i class="fa fa-tools me-2"></i>Unified Corporate AMC</h4>
              <p class="text-white-50 small mb-md-0">Annual Maintenance Contract for Elevators, ACs, and Generators combined.</p>
            </div>
            <div class="col-md-4 text-md-end text-center">
              <h3 class="text-info mb-2">Custom Pricing</h3>
              <a href="#contact" class="btn btn-outline-info btn-sm rounded-pill px-3">Contact Enterprise</a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="text-center mt-5">
      <small class="text-muted">
        * Estimates depend directly on machine capacity (tonnage/kVA/stops), brand parameters, site conditions, and specific engineering requests.
      </small>
    </div>

  </div>
</div>
<!-- Pricing Plan End -->


    <!-- Footer Start -->
<div class="container-fluid bg-dark text-light mt-5 py-5">
    <div class="container py-5">
        <div class="row g-5">

            <!-- Company Info -->
            <div class="col-lg-3 col-md-6">
                <h4 class="d-inline-block text-primary text-uppercase
                           border-bottom border-5 border-secondary mb-4">
                    Get In Touch
                </h4>
                <p class="mb-4">
                    ARN QuickFix Ltd. delivers safe, reliable, and innovative building infrastructure solutions including professional installation, preventive maintenance, modernization, and 24/7 emergency support across our complete lineup of elevators, air conditioning networks, and backup power generators.
                </p>
                <p class="mb-2">
                    <i class="fa fa-map-marker-alt text-primary me-3"></i>
                    Dhaka, Bangladesh
                </p>
                <p class="mb-2">
                    <i class="fa fa-envelope text-primary me-3"></i>
                    info@arnquickfix.com
                </p>
                <p class="mb-0">
                    <i class="fa fa-phone-alt text-primary me-3"></i>
                    +880 1303100396
                </p>
            </div>


      <!-- Quick Links -->
<!-- Quick Links -->
<div class="col-lg-3 col-md-6">
    <h4 class="d-inline-block text-primary text-uppercase border-bottom border-5 border-secondary mb-4">
        Quick Links
    </h4>
    <div class="d-flex flex-column justify-content-start">
        <a class="text-light mb-2" href="index.php"><i class="fa fa-angle-right me-2"></i>Home</a>
        <a class="text-light mb-2" href="about.php"><i class="fa fa-angle-right me-2"></i>About Us</a>
        <a class="text-light mb-2" href="service.php"><i class="fa fa-angle-right me-2"></i>Our Services</a>
        <a class="text-light mb-2" href="pricing.php"><i class="fa fa-angle-right me-2"></i>Maintenance Plans</a>
        <a class="text-light mb-2" href="blog.php"><i class="fa fa-angle-right me-2"></i>Insights & Blog</a>
        <a class="text-light" href="contact.php"><i class="fa fa-angle-right me-2"></i>Contact Us</a>
    </div>
</div>

<!-- Services -->
<div class="col-lg-3 col-md-6">
    <h4 class="d-inline-block text-primary text-uppercase border-bottom border-5 border-secondary mb-4">
        Our Services
    </h4>
    <div class="d-flex flex-column justify-content-start">
    <a class="text-light mb-2" href="#"><i class="fa fa-angle-right me-2"></i>Elevator Maintenance</a>
    <a class="text-light mb-2" href="#"><i class="fa fa-angle-right me-2"></i>Air Conditioning (AC) Servicing</a>
    <a class="text-light mb-2" href="#"><i class="fa fa-angle-right me-2"></i>Backup Generator Support</a>
    <a class="text-light mb-2" href="#"><i class="fa fa-angle-right me-2"></i>Preventive AMC Contracts</a>
    <a class="text-light" href="#"><i class="fa fa-angle-right me-2"></i>24/7 Emergency Dispatch</a>
</div>

</div>

<!-- Newsletter -->
<div class="col-lg-3 col-md-6">
    <h4 class="d-inline-block text-primary text-uppercase border-bottom border-5 border-secondary mb-4">
        Newsletter
    </h4>
    <p>Subscribe for multi-asset maintenance reminders safety tips, maintenance reminders, and company updates.</p>
    <form method="post" action="">
        <div class="input-group">
            <input type="email" name="email" class="form-control p-3 border-0" placeholder="Your Email Address">
            <button class="btn btn-primary" type="submit">Subscribe</button>
        </div>
    </form>

    <h6 class="text-primary text-uppercase mt-4 mb-3">Follow Us</h6>
    <div class="d-flex">
        <a class="btn btn-lg btn-primary btn-lg-square rounded-circle me-2" href="#"><i class="fab fa-facebook-f"></i></a>
        <a class="btn btn-lg btn-primary btn-lg-square rounded-circle me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
        <a class="btn btn-lg btn-primary btn-lg-square rounded-circle me-2" href="#"><i class="fab fa-twitter"></i></a>
        <a class="btn btn-lg btn-primary btn-lg-square rounded-circle" href="#"><i class="fab fa-instagram"></i></a>
    </div>
</div>

</div>
</div>
</div>

<!-- Footer Bottom -->
<div class="container-fluid bg-dark text-light border-top border-secondary py-4">
    <div class="container">
        <div class="row g-5">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-md-0">
                    &copy; <span class="text-primary">ARN QuickFix Ltd.</span> All Rights Reserved.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">
                    Designed & Developed for Vertical Mobility Excellence
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Footer End -->


    <!-- Back to Top -->
    <a href="#!" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>