<?php
ob_start();
include ("db_connect.php");
require_once('PHPMailer_v5.1/class.phpmailer.php'); 
$obj=new DB_connect();
date_default_timezone_set("Asia/Kolkata");
error_reporting(E_ALL);
if(isset($_REQUEST['btn_submit']))
{
  	$email = $_REQUEST['email'];

    $stmt = $obj->con1->prepare("select * from admin where email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $admin_res = mysqli_fetch_array($res);

    if(mysqli_num_rows($res)==1){
           

    $subj = urldecode("Password Recovery");
    $to   = $email;
   
    $from_name = urldecode("MyKapot");
    
    $body=urldecode("<html><p>Dear user,</p></br>
<div>This e-mail is in response to your recent request to recover your forgotten password.<br>
Your  password is: " .$admin_res['password'] . "</div><br><div></html>");
  
    $resp_mail=smtpmailer($to,$from_name,$subj, $body);
    
    if($resp_mail==1)
    {
    	setcookie("msg", "sent",time()+3600,"/");
    }
    else
    {
    	setcookie("msg", "fail",time()+3600,"/");
    }
    
          
    header("location:forgot_pass.php");
    }
    else
    {
      setcookie("msg", "not found",time()+3600,"/");
      header("location:forgot_pass.php");
    }
 
}

function smtpmailer($to, $from_name, $subject, $body)
{
  $from = 'test@pragmanxt.com';
  global $error;
  $mail = new PHPMailer();
  $mail->IsSMTP();
  $mail->SMTPAuth = true; 
 

       // $mail->SMTPSecure = 'ssl'; 
  $mail->SMTPKeepAlive = true;
  $mail->Mailer = "smtp";
  $mail->Host = 'mail.pragmanxt.com';
  $mail->Port = 465;  
  $mail->SMTPSecure = 'ssl';   
  $mail->Username = 'test@pragmanxt.com';  
  $mail->Password = 'Udwo?7zNuMU}';   
    

  $mail->IsHTML(true);    
  $mail->SMTPDebug = 1; 
  $mail->From=$from;
  $mail->FromName=$from_name;
  $mail->Sender=$from; // indicates ReturnPath header
  $mail->AddReplyTo($from, $from_name); // indicates ReplyTo headers

  $mail->Subject = $subject;
  $mail->Body = $body;
  $mail->AddAddress($to);
  if(!$mail->Send())
  {     
      $error = 'Mail error: '.$mail->ErrorInfo;
  
      return $error;

  }
  else
  {
      $error = 'Message sent!';

      return 1;
     
  }

}


?>

<!DOCTYPE html>

<html lang="en" class="light-style  customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template">


<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Forgot Password | MyKapot</title>
    
    <meta name="description" content="" />
    
   
    <link rel="icon" type="image/x-icon" href="assets/img/kapot_favi.png" />
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

 

<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
 <?php 
if(isset($_COOKIE["msg"]) )
{

  if($_COOKIE['msg']=="sent")
  {

  ?>
  <div class="alert alert-primary alert-dismissible" role="alert">
    Email has been sent successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
  
  
  if($_COOKIE['msg']=="fail")
  {
  ?>

  <div class="alert alert-danger alert-dismissible" role="alert">
    An error occured! Try again.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
  if($_COOKIE['msg']=="not found")
  {
  ?>

  <div class="alert alert-danger alert-dismissible" role="alert">
    This Email does not exist! Please enter valid email-id.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
}
  
?>
      <!-- Forgot Password -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center">
            <a href="#" class="app-brand-link gap-2">
              <img src="assets/img/kapot_logo.jpg" class="mb-3">
              
            </a>
          </div>
          <!-- /Logo -->
         
          <h4 class="mb-2">Forgot Password? 🔒</h4>
          <p class="mb-4">Enter your email-id and we'll send you an email regarding your password.</p>
          <form  class="mb-3" action="" method="POST">
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus required>
            </div>
            <button class="btn btn-primary d-grid w-100" type="submit" name="btn_submit">Send </button>
          </form>
          <div class="text-center">
            <a href="index.php" class="d-flex align-items-center justify-content-center">
              <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
              Back to login
            </a>
          </div>
        </div>
      </div>
      <!-- /Forgot Password -->
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



