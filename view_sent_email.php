<?php
$id = $_GET['id'];

// Include the database configuration
require_once 'config.php';

try {
    // Fetch the total number of received emails
    $stmt_count_received = $pdo->prepare("SELECT COUNT(*) as total FROM emails");
    $stmt_count_received->execute();
    $total_received_emails = $stmt_count_received->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch the total number of sent emails
    $stmt_count_sent = $pdo->prepare("SELECT COUNT(*) as total FROM sent_emails");
    $stmt_count_sent->execute();
    $total_sent_emails = $stmt_count_sent->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch email details
    $stmt = $pdo->prepare("SELECT * FROM sent_emails WHERE id = ?");
    $stmt->execute([$id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$email) {
        die('Email not found.');
    }

    // Create a new HTML Purifier instance
    require_once 'HTMLPurifier/HTMLPurifier.auto.php';
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);

    // Sanitize the message body
    $clean_message = $purifier->purify($email['message']);

    $date = new DateTime($email['date'], new DateTimeZone('UTC')); // Assume the IMAP date is in UTC
    $date->setTimezone(new DateTimeZone('America/Bogota'));
    $formatted_date = $date->format('M d, Y, h:i A');

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo htmlspecialchars($email['subject'] ?? ''); ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport"/>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon"/>

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
                  <p>Inbox</p>
                </a>
              </li>
              <li class="nav-item active">
                <a data-bs-toggle="collapse" href="sent.php">
                  <i class="fas fa-arrow-right"></i>
                  <p>Sent</p>
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
                <span class="op-7"><strong>Total Inbox Email:</strong> <?php echo htmlspecialchars($total_received_emails); ?> | <strong>Total Sent Email:</strong> <?php echo htmlspecialchars($total_sent_emails); ?></span>
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
                   <strong>Subject:</strong> <?php echo htmlspecialchars($email['subject'] ?? ''); ?>
            <br><strong>From:</strong> <?php echo htmlspecialchars($email['from_address'] ?? ''); ?>
            <br><strong>To:</strong> <?php echo htmlspecialchars($email['to_address'] ?? ''); ?>
            <br><strong>CC:</strong> <?php echo htmlspecialchars($email['cc_address'] ?? ''); ?>
            <br><strong>Date:</strong> <?php echo $formatted_date; ?>
            <br><hr>
            <br><div><?php echo $clean_message; ?></div>

            <?php
// Handle attachments
$attachments = explode(',', $email['attachments']);
$has_attachments = array_filter($attachments);
if (!empty($has_attachments)) {
    echo '<p><strong>Attachment(s):</strong></p>';
    foreach ($attachments as $attachment) {
        if (!empty($attachment)) {
            echo '<a href="sent_attachment_download.php?file=' . urlencode($attachment) . '">' . htmlspecialchars($attachment) . '</a><br>';
        }
    }
} else {
    echo '<p><strong>Attachment:</strong> None</p>';
}
?>
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
              Version 1.2
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

      $(document).ready(function () {
        $("#basic-datatables").DataTable({});
      });

    </script>
  </body>
</html>
