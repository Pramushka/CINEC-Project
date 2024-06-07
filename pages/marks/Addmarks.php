<?php
// Start the session
session_start();

// Include the database helper file
require_once '../../db.helper.php';

// Fetch batch data
$batchSql = "SELECT ID, BATCH_NO FROM batch";
$batchResult = $conn->query($batchSql);

// Fetch student data
$studentSql = "SELECT ID, FIRST_NAME, LAST_NAME FROM student_table";
$studentResult = $conn->query($studentSql);

// Fetch module data
$moduleSql = "SELECT ID, NAME FROM module";
$moduleResult = $conn->query($moduleSql);

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batchId = $_POST['batchNo'];
    $studentId = $_POST['studentName'];
    $moduleId = $_POST['module'];
    $marks = $_POST['marks'];
    $teacherId = $_SESSION['teacher_id']; // Assuming teacher_id is stored in session upon login
    $updateBy = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $updateOn = date('Y-m-d');

    // Get the batch ID of the student
    $studentBatchSql = "SELECT BATCH_ID FROM student_table WHERE ID = ?";
    $studentBatchStmt = $conn->prepare($studentBatchSql);
    $studentBatchStmt->bind_param("i", $studentId);
    $studentBatchStmt->execute();
    $studentBatchResult = $studentBatchStmt->get_result();

    if ($studentBatchResult->num_rows > 0) {
        $studentBatchRow = $studentBatchResult->fetch_assoc();
        $batchId = $studentBatchRow['BATCH_ID'];
    } else {
        echo "<script>
                alert('Invalid student selected.');
                window.history.back();
              </script>";
        exit();
    }

    // Check if a record with the same studentId, moduleId, and batchId already exists
    $checkSql = "SELECT ID FROM markes WHERE STUDENT_TABLE_ID = ? AND MODULE_ID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $studentId, $moduleId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Record exists, update it
        $row = $checkResult->fetch_assoc();
        $markId = $row['ID'];

        $updateSql = "UPDATE markes SET MARKS = ?, UPDATE_ON = ?, UPDATE_BY = ?, TEACHER_ID = ? WHERE ID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("dssii", $marks, $updateOn, $updateBy, $teacherId, $markId);

        if ($updateStmt->execute()) {
            echo "<script>
                    alert('Marks updated successfully!');
                    window.location.href = '../../T_dashboard.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: " . $updateStmt->error . "');
                    window.history.back();
                  </script>";
        }

        $updateStmt->close();
    } else {
        // Record does not exist, insert a new one
        $insertSql = "INSERT INTO markes (MARKS, UPDATE_ON, UPDATE_BY, MODULE_ID, STUDENT_TABLE_ID, TEACHER_ID) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("dssiii", $marks, $updateOn, $updateBy, $moduleId, $studentId, $teacherId);

        if ($insertStmt->execute()) {
            echo "<script>
                    alert('Marks added successfully!');
                    window.location.href = '../../T_dashboard.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: " . $insertStmt->error . "');
                    window.history.back();
                  </script>";
        }

        $insertStmt->close();
    }

    $checkStmt->close();
    $conn->close();
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Marks-Manager AddMarks Form</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../../vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../../vendors/base/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- plugin css for this page -->
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="../../css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="../../images/favicon.png" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:../../partials/_navbar.html -->
    
    
    
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">  
        <a class="navbar-brand brand-logo" href="T_dashboard.php">
                <img src="../../images/logo-cinec.webp" alt="logo" style="height: 40px; width: auto;"/>
                <span style="font-size: 18px; margin-left: -2px; color: #000000; vertical-align: middle;">Marks-Manger</span>
            </a>
            <a class="navbar-brand brand-logo-mini" href="T_dashboard.php">
                <img src="../../images/logo-cinec.webp" alt="logo" style="height: 30px; width: auto;"/>
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
                    <img src="../../images/faces/face4.jpg" alt="image" class="profile-pic">
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
                    <img src="../../images/faces/face2.jpg" alt="image" class="profile-pic">
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
                    <img src="../../images/faces/face3.jpg" alt="image" class="profile-pic">
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
              <img src="../../images/usericon.png" alt="profile"/>
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
      <!-- partial:../../partials/_sidebar.html -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item">
            <a class="nav-link" href="../../T_dashboard.php">
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
                <li class="nav-item"> <a class="nav-link" href="Addmarks.php">Add Marks</a></li>
                <li class="nav-item"> <a class="nav-link" href="MarkList.php">Mark List</a></li>
              </ul>
            </div>
          </li>      
        </ul>
      </nav>
      <!-- partial -->
      <div class="main-panel">        
        <div class="content-wrapper">
        <div class="row">
    <div class="col-12 grid-margin stretch-card">
    <div class="card">
    <div class="card-body">
        <h4 class="card-title">Marks Adding Form</h4>
        <p class="card-description">
            From this form is for the teacher to add marks for each student <br> when Adding marks please enter the numeric value
        </p>
        <form class="forms-sample" action="AddMarks.php" method="POST">
            <div class="form-group">
                <label for="batchNo">Batch No</label>
                <select class="form-control" id="batchNo" name="batchNo">
                    <?php
                    if ($batchResult->num_rows > 0) {
                        while($batchRow = $batchResult->fetch_assoc()) {
                            echo '<option value="'.$batchRow['ID'].'">'.$batchRow['BATCH_NO'].'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="studentName">Student Name</label>
                <select class="form-control" id="studentName" name="studentName">
                    <?php
                    if ($studentResult->num_rows > 0) {
                        while($studentRow = $studentResult->fetch_assoc()) {
                            echo '<option value="'.$studentRow['ID'].'">'.$studentRow['FIRST_NAME'].' '.$studentRow['LAST_NAME'].'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="module">Module</label>
                <select class="form-control" id="module" name="module">
                    <?php
                    if ($moduleResult->num_rows > 0) {
                        while($moduleRow = $moduleResult->fetch_assoc()) {
                            echo '<option value="'.$moduleRow['ID'].'">'.$moduleRow['NAME'].'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="marks">Marks</label>
                <input type="number" class="form-control" id="marks" name="marks" placeholder="Enter Marks" min="0" max="100" style="width: 150px; height: 40px; border-radius: 4px; border: 1px solid #ced4da;">
            </div>      
            <button type="submit" class="btn btn-primary mr-2">Submit</button>
            <button type="reset" class="btn btn-light">Cancel</button>
        </form>
      </div>
    </div>

        </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:../../partials/_footer.html -->
        <footer class="footer">
          
        </footer>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="../../vendors/base/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="../../js/off-canvas.js"></script>
  <script src="../../js/hoverable-collapse.js"></script>
  <script src="../../js/template.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="../../js/file-upload.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(document).ready(function() {
        $('#batchNo').select2({
            placeholder: 'Select Batch No',
            allowClear: true
        });
        $('#studentName').select2({
            placeholder: 'Select Student Name',
            allowClear: true
        });
        $('#module').select2({
            placeholder: 'Select Module',
            allowClear: true
        });
    });
</script>

</body>

</html>
