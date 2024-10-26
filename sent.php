<?php
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

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Sent Folder</title>
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
                <h3 class="fw-bold mb-3">Sent</h3>
              </div>
            <div class="ms-md-auto py-2 py-md-0">
    <form id="refreshForm" style="display: inline;">
        <button type="button" class="btn btn-primary btn-round" onclick="window.location.href='fetch_sent_emails.php'">Sync Emails</button>
    </form>
</div>
              </div>
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="basic-datatables" class="display table table-striped table-hover">
    <thead>
        <tr>
            <th>Subject</th>
            <th>From</th>
            <th>Date</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th>Subject</th>
            <th>From</th>
            <th>Date</th>
        </tr>
    </tfoot>
    <tbody>
        <?php
        // Include the configuration file for the database connection
        require_once 'config.php';

        // Set the timezone to America/Bogota
        $bogotaTimezone = new DateTimeZone('America/Bogota');

        // Query to fetch all emails, ordered by date descending
        $query = "SELECT * FROM sent_emails ORDER BY date DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Convert the date to America/Bogota timezone
            $date = new DateTime($row['date']); // Original date from the database
            $date->setTimezone($bogotaTimezone); // Set the desired timezone

            echo '<tr>';
            echo '<td><a href="view_sent_email.php?id='.$row['id'].'" target="_blank">'.$row['subject'].'</a></td>';
            echo '<td>'.$row['from_address'].'</td>';
            echo '<td>'.$date->format('M d, Y, h:i A').'</td>'; // Format the date in America/Bogota timezone
            echo '</tr>';
        }
        ?>
    </tbody>
</table>
                    </div>
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