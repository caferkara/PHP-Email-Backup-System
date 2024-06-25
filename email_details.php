<?php
include(__DIR__ . '/includes/db_functions.php');

// Check if 'id' parameter is set in the URL
if (!isset($_GET['id'])) {
    echo "Email ID is missing.";
    exit;
}

$email_id = $_GET['id'];
$email = getEmailById($email_id);

if (!$email) {
    echo "Email not found.";
    exit;
}

// Get folder from URL parameter or default to 'inbox'
$folder = isset($_GET['folder']) ? $_GET['folder'] : 'inbox';

// Get page number from URL parameter, default to 1
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Number of emails to display per page
$limit = PHP_INT_MAX;  // Set limit to a very large number to fetch all emails

// Calculate offset
$offset = 0;  // Since we're fetching all emails, offset is 0

// Get emails for the current page
$emails = getEmails($folder, $limit, $offset);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Inbox Folder - Email Details</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/favicon.ico"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/ck.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="index.php" class="logo">
              <img
                src="assets/img/logo.png"
                alt="navbar brand"
                class="navbar-brand"
                height="auto"
                width="auto" 
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Folders</h4>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="index.php">
                  <i class="fas fa-envelope"></i>
                  <p>Inbox (<?php echo getTotalEmailsByFolder('inbox'); ?>)</p>
                </a>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="sent.php">
                  <i class="fas fa-arrow-right"></i>
                  <p>Sent (<?php echo getTotalSentEmailsCount(); ?>)</p>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
              <a href="index.php" class="logo">
                <img
                  src="assets/img/logo_png"
                  alt="navbar brand"
                  class="navbar-brand"
                  height="auto"
                  width="auto"
                />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
            <!-- End Logo Header -->
          </div>
          <!-- Navbar Header -->
          <nav
            class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom"
          >
            <div class="container-fluid">
              <nav
                class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex"
              >
              </nav>
                <span class="op-7">Welcome to Email Backup System</span>
              </ul>
            </div>
          </nav>
          <!-- End Navbar -->
        </div>

        <div class="container">
          <div class="page-inner">
            <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Email Details</h3>
              </div>
              </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-body">
                   <strong>Subject:</strong> <?php echo htmlspecialchars($email['subject'], ENT_QUOTES, 'UTF-8'); ?>
            <br><strong>From:</strong> <?php echo htmlspecialchars($email['from_email'], ENT_QUOTES, 'UTF-8'); ?>
            <br><strong>To:</strong> <?php echo htmlspecialchars($email['to_email'], ENT_QUOTES, 'UTF-8'); ?>
            <br><strong>CC:</strong> <?php echo htmlspecialchars($email['cc_email'], ENT_QUOTES, 'UTF-8'); ?>
            <br><strong>Date:</strong> <?php echo htmlspecialchars(date_format(date_create($email['date']), 'Y-m-d h:i A'), ENT_QUOTES, 'UTF-8'); ?>
            <br><hr>
            <br><div><?php echo nl2br(htmlspecialchars($email['body'], ENT_QUOTES, 'UTF-8')); ?></div>
                  </div>

                </div>
              </div>
            </div>


          </div>
        </div>

        <footer class="footer">
          <div class="container-fluid d-flex justify-content-between">
            <nav class="pull-left">
              <ul class="nav">
                <li class="nav-item">
                &copy; <span id="copyright-year"></span> - Email Backup System - <a target="_blank" href="https://caferkara.com.tr/projects" target="_blank">Support</a>
                </li>
              </ul>
            </nav>
            <div>
              Version 1.1
            </div>
          </div>
        </footer>
      </div>
    </div>
    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/ck.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        var currentYear = new Date().getFullYear();
        document.getElementById('copyright-year').textContent = currentYear;
    }); 
    </script>
  </body>
</html>
