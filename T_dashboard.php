<?php
// Start the session
session_start();

// Include the database helper file
require_once 'db.helper.php';

// Get the logged-in teacher's ID
$teacherId = $_SESSION['teacher_id'];

// Fetch the recent 5 marks data for the logged-in teacher
$marksSql = "SELECT 
                s.FIRST_NAME, s.LAST_NAME, 
                b.BATCH_NO, 
                m.NAME as MODULE_NAME, 
                mk.MARKS, mk.UPDATE_ON 
            FROM markes mk
            JOIN student_table s ON mk.STUDENT_TABLE_ID = s.ID
            JOIN batch b ON s.BATCH_ID = b.ID
            JOIN module m ON mk.MODULE_ID = m.ID
            WHERE mk.TEACHER_ID = ?
            ORDER BY mk.UPDATE_ON DESC
            LIMIT 5";
$stmt = $conn->prepare($marksSql);
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

function getGrade($marks) {
    if ($marks >= 85) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 75) return 'A-';
    if ($marks >= 70) return 'B+';
    if ($marks >= 65) return 'B';
    if ($marks >= 60) return 'B-';
    if ($marks >= 55) return 'C+';
    if ($marks >= 50) return 'C';
    if ($marks >= 45) return 'C-';
    if ($marks >= 40) return 'D+';
    if ($marks >= 35) return 'D';
    return 'E';
}

// Fetch the count, sum, and average of marks provided by the logged-in teacher
$countMarksSql = "SELECT COUNT(*) as totalMarksCount, SUM(MARKS) as totalMarksSum, AVG(MARKS) as averageMarks FROM markes WHERE TEACHER_ID = ?";
$countStmt = $conn->prepare($countMarksSql);
$countStmt->bind_param("i", $teacherId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalMarksCount = 0;
$totalMarksSum = 0;
$averageMarks = 0;

if ($countResult->num_rows > 0) {
    $countRow = $countResult->fetch_assoc();
    $totalMarksCount = $countRow['totalMarksCount'];
    $totalMarksSum = $countRow['totalMarksSum'];
    $averageMarks = $countRow['averageMarks'];
}
$countStmt->close();

// Fetch the average marks for each subject taught by the logged-in teacher
$avgMarksSql = "SELECT m.NAME as MODULE_NAME, AVG(mk.MARKS) as AVG_MARKS
                FROM markes mk
                JOIN module m ON mk.MODULE_ID = m.ID
                WHERE mk.TEACHER_ID = ?
                GROUP BY mk.MODULE_ID";
$avgStmt = $conn->prepare($avgMarksSql);
$avgStmt->bind_param("i", $teacherId);
$avgStmt->execute();
$avgResult = $avgStmt->get_result();

$subjects = [];
$avgMarks = [];

if ($avgResult->num_rows > 0) {
    while($row = $avgResult->fetch_assoc()) {
        $subjects[] = $row['MODULE_NAME'];
        $avgMarks[] = $row['AVG_MARKS'];
    }
}
$avgStmt->close();

// Fetch the latest announcement
$latestAnnouncement = null;
$announcementSql = "SELECT Title, Note, ImageLink, CreatedBy, CreatedOn FROM announcement WHERE IsDeleted = 0 ORDER BY CreatedOn DESC LIMIT 1";
$announcementResult = $conn->query($announcementSql);

if ($announcementResult && $announcementResult->num_rows > 0) {
    $latestAnnouncement = $announcementResult->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Marks-Manger Teacher Dashbaord</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- plugin css for this page -->
  <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
        .announcement-card {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .announcement-image {
            width: 90%;
            height: 40%;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .announcement-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        .announcement-description {
            font-size: 16px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">  
          <a class="navbar-brand brand-logo" href="T_dashboard.php">
                <img src="images/logo-cinec.webp" alt="logo" style="height: 40px; width: auto;"/>
                <span style="font-size: 18px; margin-left: -2px; color: #000000; vertical-align: middle;">Marks-Manger</span>
            </a>
            <a class="navbar-brand brand-logo-mini" href="T_dashboard.php">
                <img src="images/logo-cinec.webp" alt="logo" style="height: 30px; width: auto;"/>
          </a>
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-sort-variant"></span>
          </button>
        </div>  
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item dropdown mr-1">
            <a class="nav-link count-indicator dropdown-toggle d-flex justify-content-center align-items-center" id="messageDropdown" href="#" data-toggle="dropdown">
              <i class="mdi mdi-message-text mx-0"></i>
              <span class="count"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="messageDropdown">
              <p class="mb-0 font-weight-normal float-left dropdown-header">Messages</p>
              <a class="dropdown-item">
                <div class="item-thumbnail">
                    <img src="images/faces/face4.jpg" alt="image" class="profile-pic">
                </div>
                <div class="item-content flex-grow">
                  <h6 class="ellipsis font-weight-normal">David Grey
                  </h6>
                  <p class="font-weight-light small-text text-muted mb-0">
                    The meeting is cancelled
                  </p>
                </div>
              </a>
              <a class="dropdown-item">
                <div class="item-thumbnail">
                    <img src="images/faces/face2.jpg" alt="image" class="profile-pic">
                </div>
                <div class="item-content flex-grow">
                  <h6 class="ellipsis font-weight-normal">Tim Cook
                  </h6>
                  <p class="font-weight-light small-text text-muted mb-0">
                    New product launch
                  </p>
                </div>
              </a>
              <a class="dropdown-item">
                <div class="item-thumbnail">
                    <img src="images/faces/face3.jpg" alt="image" class="profile-pic">
                </div>
                <div class="item-content flex-grow">
                  <h6 class="ellipsis font-weight-normal"> Johnson
                  </h6>
                  <p class="font-weight-light small-text text-muted mb-0">
                    Upcoming board meeting
                  </p>
                </div>
              </a>
            </div>
          </li>
          <li class="nav-item dropdown mr-4">
            <a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center notification-dropdown" id="notificationDropdown" href="#" data-toggle="dropdown">
              <i class="mdi mdi-bell mx-0"></i>
              <span class="count"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="notificationDropdown">
              <p class="mb-0 font-weight-normal float-left dropdown-header">Notifications</p>
              <a class="dropdown-item">
                <div class="item-thumbnail">
                  <div class="item-icon bg-success">
                    <i class="mdi mdi-information mx-0"></i>
                  </div>
                </div>
                <div class="item-content">
                  <h6 class="font-weight-normal">Application Error</h6>
                  <p class="font-weight-light small-text mb-0 text-muted">
                    Just now
                  </p>
                </div>
              </a>
              <a class="dropdown-item">
                <div class="item-thumbnail">
                  <div class="item-icon bg-warning">
                    <i class="mdi mdi-settings mx-0"></i>
                  </div>
                </div>
                <div class="item-content">
                  <h6 class="font-weight-normal">Settings</h6>
                  <p class="font-weight-light small-text mb-0 text-muted">
                    Private message
                  </p>
                </div>
              </a>
              <a class="dropdown-item">
                <div class="item-thumbnail">
                  <div class="item-icon bg-info">
                    <i class="mdi mdi-account-box mx-0"></i>
                  </div>
                </div>
                <div class="item-content">
                  <h6 class="font-weight-normal">New user registration</h6>
                  <p class="font-weight-light small-text mb-0 text-muted">
                    2 days ago
                  </p>
                </div>
              </a>
            </div>
          </li>
          <li class="nav-item nav-profile dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
            <img src="images/usericon.png" alt="profile"/>
              <span class="nav-profile-name">
                <?php
                        // Display the logged-in user's name
                        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                            echo htmlspecialchars($_SESSION['first_name'] . ' ' . htmlspecialchars($_SESSION['last_name']));
                        } else {
                            echo "No username found";
                        }
                ?>    
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
              <a class="dropdown-item">
                <i class="mdi mdi-settings text-primary"></i>
                Settings
              </a>
              <a class="dropdown-item">
                <i class="mdi mdi-logout text-primary"></i>
                Logout
              </a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
    </nav>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.html -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item">
            <a class="nav-link" href="T_dashboard.php">
              <i class="mdi mdi-home menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
              <i class="mdi mdi-circle-outline menu-icon"></i>
              <span class="menu-title">Student Marks</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="pages/marks/Addmarks.php">Add Marks</a></li>
                <li class="nav-item"> <a class="nav-link" href="pages/marks/MarkList.php">Mark List</a></li>
              </ul>
            </div>
          </li>        
          
        </ul>
      </nav>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-md-12 grid-margin">
              <div class="d-flex justify-content-between flex-wrap">
                <div class="d-flex align-items-end flex-wrap">
                  <div class="mr-md-3 mr-xl-5">
                    <h2>Welcome back,</h2>
                    <p class="mb-md-0">Your analytics dashboard.</p>
                  </div>
                  <div class="d-flex">
                    <i class="mdi mdi-home text-muted hover-cursor"></i>
                    <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Dashboard&nbsp;/&nbsp;</p>
                    <p class="text-primary mb-0 hover-cursor">Analytics</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
          <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body dashboard-tabs p-0">
              <ul class="nav nav-tabs px-4" role="tablist">
                  <li class="nav-item">
                      <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Overview</a>
                  </li>
              </ul>
              <div class="tab-content py-0 px-0">
                  <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                      <div class="d-flex flex-wrap justify-content-xl-between">
                      <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                            <i class="mdi mdi-flag mr-3 icon-lg text-danger"></i>
                            <div class="d-flex flex-column justify-content-around">
                                <small class="mb-1 text-muted">Overall Subject Marks Provided</small>
                                <h5 class="mr-2 mb-0"><?php echo $totalMarksCount; ?></h5>
                            </div>
                        </div>
                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                            <i class="mdi mdi-download mr-3 icon-lg text-warning"></i>
                            <div class="d-flex flex-column justify-content-around">
                                <small class="mb-1 text-muted">Total Marks Provided</small>
                                <h5 class="mr-2 mb-0"><?php echo $totalMarksSum; ?></h5>
                            </div>
                        </div>
                        <div class="d-flex py-3 border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                            <i class="mdi mdi-flag mr-3 icon-lg text-danger"></i>
                            <div class="d-flex flex-column justify-content-around">
                                <small class="mb-1 text-muted">Average Marks Provided</small>
                                <h5 class="mr-2 mb-0"><?php echo number_format($averageMarks, 2); ?></h5>
                            </div>
                        </div>
                      </div>
                  </div>
                </div>
              </div>
            </div>
             </div>
            </div>
          <div class="row">
            <div class="col-md-7 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <p class="card-title">Marks Average</p>
                  <p class="mb-4">In here a chart will be provided that this teacher's subjects marks average</p>
                <div id="cash-deposits-chart-legend" class="d-flex justify-content-center pt-3"></div>
                  <canvas id="marksAverageChart"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-5 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <p class="card-title">Announcement</p>
            <div class="announcement-card">
                <?php if ($latestAnnouncement): ?>
                    <img src="<?php echo htmlspecialchars($latestAnnouncement['ImageLink']); ?>" alt="<?php echo htmlspecialchars($latestAnnouncement['Title']); ?>" class="announcement-image">
                    <div class="announcement-title"><?php echo htmlspecialchars($latestAnnouncement['Title']); ?></div>
                    <div class="announcement-description"><?php echo htmlspecialchars($latestAnnouncement['Note']); ?></div>
                <?php else: ?>
                    <p>No announcements available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
          </div>
          <div class="row">
            <div class="col-md-12 stretch-card">
              <div class="card">
                <div class="card-body">
                  <p class="card-title">Recent five Marks Provided</p>
                  <div class="table-responsive">
                    <table id="recent-purchases-listing" class="table">
                      <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Batch</th>
                            <th>Subject</th>
                            <th>Marks Provided</th>
                            <th>Updated On</th>
                        </tr>
                      </thead>
                      <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'] . '</td>';
                                    echo '<td>' . $row['BATCH_NO'] . '</td>';
                                    echo '<td>' . $row['MODULE_NAME'] . '</td>';
                                    echo '<td>' . getGrade($row['MARKS']) . '</td>';
                                    echo '<td>' . $row['UPDATE_ON'] . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5">No recent marks found.</td></tr>';
                            }
                            $stmt->close();
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
        <footer class="footer">
          
        </footer>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('marksAverageChart').getContext('2d');
        var marksAverageChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($subjects); ?>,
                datasets: [{
                    label: 'Average Marks',
                    data: <?php echo json_encode($avgMarks); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
  <!-- plugins:js -->
  <script src="vendors/base/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page-->
  <script src="vendors/chart.js/Chart.min.js"></script>
  <script src="vendors/datatables.net/jquery.dataTables.js"></script>
  <script src="vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
  <!-- End plugin js for this page-->
  <!-- inject:js -->
  <script src="js/off-canvas.js"></script>
  <script src="js/hoverable-collapse.js"></script>
  <script src="js/template.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="js/dashboard.js"></script>
  <script src="js/data-table.js"></script>
  <script src="js/jquery.dataTables.js"></script>
  <script src="js/dataTables.bootstrap4.js"></script>
  <!-- End custom js for this page-->
</body>

</html>

