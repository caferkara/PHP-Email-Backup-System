<?php

include(__DIR__ . '/includes/db_functions.php');

$config = include(__DIR__ . '/config/config.php');

// Extract database connection details from configuration
$db_host = $config['db_host'];
$db_name = $config['db_name'];
$db_user = $config['db_user'];
$db_password = $config['db_password'];

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Number of emails to display per page
$limit = PHP_INT_MAX;  // Set limit to a very large number to fetch all emails

// Initialize variables for search
$search = '';
$whereClause = '';

// Get page number from URL parameter, default to 1
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Calculate offset
$offset = ($page - 1) * $limit;

// Query to fetch emails from database with search conditions
$sql = "SELECT * FROM sent_emails $whereClause ORDER BY email_date DESC LIMIT $offset, $limit";
$result = $conn->query($sql);

$emails = array();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Ensure 'id' column name exists in the fetched row
        $email_id = isset($row['id']) ? $row['id'] : '';
        $subject = isset($row['subject']) ? htmlspecialchars(mb_strimwidth($row['subject'], 0, 50, '...'), ENT_QUOTES, 'UTF-8') : '';
        $from_email = isset($row['from_email']) ? htmlspecialchars(mb_strimwidth($row['from_email'], 0, 20, '...'), ENT_QUOTES, 'UTF-8') : '';
        $to_email = isset($row['to_email']) ? htmlspecialchars(mb_strimwidth($row['to_email'], 0, 20, '...'), ENT_QUOTES, 'UTF-8') : '';
        $cc_email = isset($row['cc_email']) ? htmlspecialchars(mb_strimwidth($row['cc_email'], 0, 20, '...'), ENT_QUOTES, 'UTF-8') : '';
        $date = isset($row['email_date']) ? $row['email_date'] : '';

        $email = array(
            'id' => $email_id,
            'subject' => $subject,
            'from_email' => $from_email,
            'to_email' => $to_email,
            'cc_email' => $cc_email,
            'date' => $date
        );
        $emails[] = $email;
    }
}

// Get the total number of emails for pagination
$totalEmails = getTotalSentEmailsCount($search);

// Close the connection
$conn->close();

// Function to format date according to your timezone
function formatDate($dateString) {
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    // Change 'America/Bogota' to your desired timezone
    $date->setTimezone(new DateTimeZone('America/Bogota'));
    return $date->format('Y-m-d h:i A');
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Sent Folder</title>
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
                <h3 class="fw-bold mb-3">Sent Folder</h3>
              </div>
            <div class="ms-md-auto py-2 py-md-0">
    <form id="refreshForm" style="display: inline;">
        <input type="hidden" name="folder" value="sent">
        <button type="button" class="btn btn-primary btn-round" onclick="startRefresh()">Refresh</button>
    </form>
</div>
              </div>
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">

                    <div id="loading-bar" style="display: none; margin-top: 10px;">
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"><span class="progress-text">0%</span></div>
        </div>
    </div>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table
                        id="basic-datatables"
                        class="display table table-striped table-hover">
                        <thead>
                          <tr>
                            <th>Subject</th>
                            <th>From</th>
                            <th>To</th>
                            <th>CC</th>
                            <th>Date</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tfoot>
                          <tr>
                            <th>Subject</th>
                            <th>From</th>
                            <th>To</th>
                            <th>CC</th>
                            <th>Date</th>
                            <th>Action</th>
                          </tr>
                        </tfoot>
                        <tbody>
                          <?php
                    if (!empty($emails)) {
                        foreach ($emails as $email): ?>
                          <tr>
                            <td><?php echo $email['subject']; ?></td>
                            <td><?php echo $email['from_email']; ?></td>
                            <td><?php echo $email['to_email']; ?></td>
                            <td><?php echo $email['cc_email']; ?></td>
                            <td><?php echo formatDate($email['date']); ?></td>
                            <td><a href="sent_email_details.php?id=<?php echo $email['id']; ?>" class="details-link">Read Email</a></td>
                          </tr>
                          <?php endforeach;
                    } else { ?>
                      <?php } ?>
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

      $(document).ready(function () {
        $("#basic-datatables").DataTable({});
      });

    </script>
<script>
function startRefresh() {
    const folder = 'sent';

    $('#loading-bar').show();
    $('#loading-bar .progress-bar').css('width', '0%');
    $('#loading-bar .progress-text').text('0%');

    let currentProgress = 0;
    let progressInterval = setInterval(function() {
        currentProgress += 10;
        $('#loading-bar .progress-bar').css('width', currentProgress + '%');
        $('#loading-bar .progress-text').text(currentProgress + '%');
        if (currentProgress >= 100) {
            clearInterval(progressInterval);
        }
    }, 4000);

    $.ajax({
        url: 'refresh_sent.php',
        type: 'POST',
        data: { folder: folder },
        success: function() {
            clearInterval(progressInterval);
            location.reload(); // Refresh the page after completion
        },
        error: function() {
            $('#loading-bar').hide();
            clearInterval(progressInterval);
            alert('Failed to refresh emails.');
        }
    });
}
</script>
  </body>
</html>
