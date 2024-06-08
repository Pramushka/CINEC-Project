<?php
session_start();

// Include the database helper file
require_once '../../db.helper.php';

// Check if email and password are set in the POST request
if (isset($_POST['email']) && isset($_POST['password'])) {
    // Get the email and password from the POST request
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute the SQL query
    $sql = "SELECT * FROM STUDENT_TABLE WHERE EMAIL = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any rows were returned
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['Password'];

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Store user's name and ID in session variables
            $_SESSION['student_id'] = $row['ID'];
            $_SESSION['first_name'] = $row['FIRST_NAME'];
            $_SESSION['last_name'] = $row['LAST_NAME'];
            $_SESSION['logged_in'] = true;
            
            // Redirect to the student dashboard
            header("Location: ../../S_dashboard.php");
            exit();
        } else {
            echo "Invalid email or password.";
        }
    } else {
        echo "Invalid email or password.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "Please provide email and password.";
}
?>





<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Marks-ManagerStudent Login</title>
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
</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-stretch auth auth-img-bg">
        <div class="row flex-grow">
          <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="auth-form-transparent text-left p-3">
              <div class="brand-logo">
              </div>
              <h4>Welcome back!</h4>
                <h6 class="font-weight-light">Happy to see you again!</h6>
                <form class="pt-3" action="login_S.php" method="post">
                    <div class="form-group">
                        <label for="exampleInputEmail">Email</label>
                        <div class="input-group">
                            <div class="input-group-prepend bg-transparent">
                                <span class="input-group-text bg-transparent border-right-0">
                                    <i class="mdi mdi-account-outline text-primary"></i>
                                </span>
                            </div>
                            <input type="email" class="form-control form-control-lg border-left-0" id="exampleInputEmail" name="email" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputPassword">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend bg-transparent">
                                <span class="input-group-text bg-transparent border-right-0">
                                    <i class="mdi mdi-lock-outline text-primary"></i>
                                </span>
                            </div>
                            <input type="password" class="form-control form-control-lg border-left-0" id="exampleInputPassword" name="password" placeholder="Password" required>                        
                        </div>
                    </div>
                    <div class="my-2 d-flex justify-content-between align-items-center">
                        
                    </div>
                    <div class="my-3">
                        <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">LOGIN</button>
                    </div>
                    
                    <div class="text-center mt-4 font-weight-light">
                        Don't have an account? <a href="register_S.php" class="text-primary">Create</a>
                    </div>
                </form>
            </div>
          </div>
          <div class="col-lg-6 login-half-bg d-flex flex-row">
          </div>
        </div>
      </div>
      <!-- content-wrapper ends -->
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
</body>

</html>
