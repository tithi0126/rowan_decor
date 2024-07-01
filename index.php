<?php
 
ob_start();
include ("db_connect.php");
$obj=new DB_connect();
date_default_timezone_set("Asia/Kolkata");

if(isset($_REQUEST["login"])){
			session_start();
			
			$ui = $_REQUEST["userid"];
			$pa = $_REQUEST["password"];
			
     
			$qr = $obj->con1->prepare("select id,password,username from admin where username=? and binary(password) =?");
			$qr->bind_param("ss",$ui,$pa);
			$qr->execute();
			$result = $qr->get_result();
			$qr->close();
			$row=mysqli_fetch_array($result);
			
			if($row["username"]==$ui)
			{
        $_SESSION["userlogin"]="true";
				$_SESSION["id"]=$row["id"];
        // $_SESSION["userid"]=$ui;
        $_SESSION["username"]=$row["name"];
        // $_SESSION["designation"]=$row["designation"];
				header("location:home.php");
			}
			else
			{
        setcookie("login", "wrong_pass",time()+3600,"/");
				header("location:index.php?msg=Incorect UserId/Password");	
			}
			
	
		}
?>
<!DOCTYPE html>

<html
  lang="en"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Login | Rowan Decor</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../img/m_favi.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css" />
    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="assets/js/config.js"></script>
    <script type="text/javascript">
      function createCookie(name, value, days) {
        var expires;
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toGMTString();
        } else {
            expires = "";
        }
        document.cookie = (name) + "=" + String(value) + expires + ";path=/ ";

    }

    function readCookie(name) {
        var nameEQ = (name) + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return (c.substring(nameEQ.length, c.length));
        }
        return null;
    }

    function eraseCookie(name) {
        createCookie(name, "", -1);
    }
</script>
  </head>

  <body>
    <!-- Content -->



    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
           <?php 
            if(isset($_COOKIE["login"]) )
            {

              if($_COOKIE['login']=="wrong_pass")
              {

              ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                Incorect UserId/Password.Please Try Again.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
              </div>
              <script type="text/javascript">eraseCookie("login")</script>
              <?php
              }
            }
              
            ?>
          <!-- Register -->
          <div class="card">
            <div class="card-body">
              <!-- Logo -->
              <div class="app-brand justify-content-center">
                <a href="index.html" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                   <img src="assets/img/kapot_100.png">
                  </span>
                 <!--  <span class="app-brand-text demo text-body fw-bolder">MyKapot</span> -->
                </a>
              </div>
              <!-- /Logo -->
              
             <!--  <p class="mb-4">Please sign-in to your account and start the adventure</p> -->

              <form id="formAuthentication" class="mb-3" action="" method="POST">
                <div class="mb-3">
                  <label for="email" class="form-label">Userid</label>
                  <input required
                    type="text"
                    class="form-control"
                    id="userid"
                    name="userid"
                    
                    autofocus
                  />
                </div>
                <div class="mb-3 form-password-toggle">
                  <div class="d-flex justify-content-between">
                    <label class="form-label" for="password">Password</label>
                  </div>
                  <div class="input-group input-group-merge">
                    <input required
                      type="password"
                      id="password"
                      class="form-control"
                      name="password"
                      
                      aria-describedby="password"
                    />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                  <div class="d-flex justify-content-right">
                    <a href="forgot_pass.php">
                      <small>Forgot Password?</small>
                    </a>
                  </div>
                </div>
                <!-- <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember-me" />
                    <label class="form-check-label" for="remember-me"> Remember Me </label>
                  </div>
                </div> -->
                <div class="mb-3">
                  <button class="btn btn-primary d-grid w-100" type="submit" name="login">Sign in</button>
                </div>
              </form>

             
            </div>
          </div>
          <!-- /Register -->
        </div>
      </div>
    </div>

    <!-- / Content -->

    

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <!-- Page JS -->

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
  </body>
</html>
