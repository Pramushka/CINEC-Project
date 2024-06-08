<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../Login_S.php");
    exit();
}

// Get the student ID from the session
$student_id = $_SESSION['student_id'];

// Include the database helper file
require_once 'db.helper.php';

// Fetch the marks for the logged-in student
$studentMarksSql = "SELECT MODULE_ID, MARKS FROM markes WHERE STUDENT_TABLE_ID = ?";
$studentMarksStmt = $conn->prepare($studentMarksSql);
$studentMarksStmt->bind_param("i", $student_id);
$studentMarksStmt->execute();
$studentMarksResult = $studentMarksStmt->get_result();

$studentMarks = [];
while ($row = $studentMarksResult->fetch_assoc()) {
    $studentMarks[$row['MODULE_ID']] = $row['MARKS'];
}

// Fetch the average marks for each module the student has marks in
$moduleIds = implode(',', array_keys($studentMarks));
$averageMarksSql = "SELECT MODULE_ID, AVG(MARKS) as AVG_MARKS FROM markes WHERE MODULE_ID IN ($moduleIds) GROUP BY MODULE_ID";
$averageMarksResult = $conn->query($averageMarksSql);

$averageMarks = [];
while ($row = $averageMarksResult->fetch_assoc()) {
    $averageMarks[$row['MODULE_ID']] = $row['AVG_MARKS'];
}

// Prepare data for the chart
$modules = array_keys($studentMarks);
$studentMarksData = array_values($studentMarks);
$averageMarksData = array_values($averageMarks);

// Fetch the recent marks received by the logged-in student
$recentMarksSql = "
    SELECT 
        m.NAME as MODULE_NAME, 
        b.BATCH_NO, 
        mk.MARKS, 
        mk.UPDATE_ON, 
        mk.UPDATE_BY 
    FROM markes mk
    JOIN module m ON mk.MODULE_ID = m.ID
    JOIN student_table s ON mk.STUDENT_TABLE_ID = s.ID
    JOIN batch b ON s.BATCH_ID = b.ID
    WHERE mk.STUDENT_TABLE_ID = ?
    ORDER BY mk.UPDATE_ON DESC
    LIMIT 5";
$recentMarksStmt = $conn->prepare($recentMarksSql);
$recentMarksStmt->bind_param("i", $student_id);
$recentMarksStmt->execute();
$recentMarksResult = $recentMarksStmt->get_result();

$recentMarks = [];
while ($row = $recentMarksResult->fetch_assoc()) {
    $recentMarks[] = $row;
}

$recentMarksStmt->close();
$studentMarksStmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Marks-Manger Student Dashbaord</title>
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
</head>
<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">  
          <a class="navbar-brand brand-logo" href="S_dashboard.php">
                <img src="images/logo-cinec.webp" alt="logo" style="height: 40px; width: auto;"/>
                <span style="font-size: 18px; margin-left: -2px; color: #000000; vertical-align: middle;">Marks-Manger</span>
            </a>
            <a class="navbar-brand brand-logo-mini" href="S_dashboard.php">
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
              <a class="dropdown-item" href="logout_S.php">
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
            <a class="nav-link" href="S_dashboard.php">
              <i class="mdi mdi-home menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
              <i class="mdi mdi-circle-outline menu-icon"></i>
              <span class="menu-title">Nav title</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="#">nav item</a></li>
                <li class="nav-item"> <a class="nav-link" href="#"> nav item</a></li>
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
                        </div>
                        <div class="d-flex py-3 border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                          <i class="mdi mdi-counter mr-3 icon-lg text-danger"></i>
                          <div class="d-flex flex-column justify-content-around">
                            <small class="mb-1 text-muted">Current GPA Prediction</small>
                            <h5 class="mr-2 mb-0">2.2</h5>
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
        <p class="card-title">Marks Compared to Average</p>
        <p class="mb-4">In here a chart will be the average going through on the batch and the users current state comparison</p>
        <div id="cash-deposits-chart-legend" class="d-flex justify-content-center pt-3"></div>
        <canvas id="marksComparisonChart"></canvas>
    </div>
</div>
            </div>
            <div class="col-md-5 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <p class="card-title">Your To Do List</p>
                  <div id="total-sales-chart-legend"></div>                  
                </div>
                <canvas id="#"></canvas>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 stretch-card">
            <div class="card">
    <div class="card-body">
        <p class="card-title">Recent Marks Received</p>
        <div class="table-responsive">
            <table id="recent-purchases-listing" class="table">
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Batch</th>
                        <th>Marks Provided</th>
                        <th>Updated On</th>
                        <th>Updated By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMarks as $mark): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mark['MODULE_NAME']); ?></td>
                            <td><?php echo htmlspecialchars($mark['BATCH_NO']); ?></td>
                            <td><?php echo htmlspecialchars($mark['MARKS']); ?></td>
                            <td><?php echo htmlspecialchars($mark['UPDATE_ON']); ?></td>
                            <td><?php echo htmlspecialchars($mark['UPDATE_BY']); ?></td>
                        </tr>
                    <?php endforeach; ?>
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
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('marksComparisonChart').getContext('2d');
        var marksComparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($modules); ?>,
                datasets: [
                    {
                        label: 'Your Marks',
                        data: <?php echo json_encode($studentMarksData); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Batch Average Marks',
                        data: <?php echo json_encode($averageMarksData); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
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
                        display: true
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

