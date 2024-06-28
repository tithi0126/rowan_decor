<?php
  include("header.php");

// insert data
if(isset($_REQUEST['btnsubmit']))
{
  $email = $_REQUEST['email'];

    $stmt = $obj->con1->prepare("select * from admin where email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    $admin_res = mysqli_fetch_array($res);

    if(mysqli_num_rows($res)==1){
   
      ob_start();

          require_once('PHPMailer_v5.1/class.phpmailer.php'); //library added in download source.

          $subj = urldecode("Password Recovery");
          $to   = $email;
         
          $from_name = urldecode("Test");
          //$from=urldecode($_REQUEST['from']);
          $body=urldecode("Your Password is = ".$admin_res['password']);
        
        

          smtpmailer($to,$from_name,$subj, $body);
          // header("location:../index.php");
          
//  header("location:../contact-us.php?msg=".$error); 
    }
    else
    {
      setcookie("msg", "fail",time()+3600,"/");
      header("location:forgetPass.php");
    }
 
}

function smtpmailer($to, $from_name, $subject, $body, $is_gmail = true)
          {
            $from = 'pragmatestmail@gmail.com';
            global $error;
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = true; 
            if($is_gmail)
            {
        
                 // $mail->SMTPSecure = 'ssl'; 
              $mail->SMTPKeepAlive = true;
              $mail->Mailer = "smtp";
              $mail->Host = 'smtp.gmail.com';
              $mail->Port = 465;  
              $mail->SMTPSecure = 'ssl';   
                  $mail->Username = 'pragmatestmail@gmail.com';  
                  $mail->Password = 'Pragma@jay';   
              }
              else
              {
        
                 $mail->Host = 'smtpout.secureserver.net';
                   $mail->Username = 'pragmatestmail@gmail.com';  
                  $mail->Password = 'Pragma@jay';   
              }

              $mail->IsHTML(true);    
          $mail->SMTPDebug = 1; 
              $mail->From="pragmatestmail@gmail.com";
              $mail->FromName=$from_name;
              $mail->Sender=$from; // indicates ReturnPath header
              $mail->AddReplyTo($from, $from_name); // indicates ReplyTo headers
      //        $mail->AddCC('cc@site.com.com', 'CC: to site.com');
              $mail->Subject = $subject;
              $mail->Body = $body;
              $mail->AddAddress($to);
              if(!$mail->Send())
              {     
                  $error = 'Mail error: '.$mail->ErrorInfo;
              echo $error;
                  return $error;
            
              }
              else
              {
                  $error = 'Message sent!';
          //  echo $error;
                  return "1";
                  setcookie("msg", "data",time()+3600,"/");
      header("location:forgetPass.php");
              }

    }
  

?>

<h4 class="fw-bold py-3 mb-4">Forget Password</h4>

<?php 
if(isset($_COOKIE["msg"]) )
{

  if($_COOKIE['msg']=="data")
  {

  ?>
  <div class="alert alert-primary alert-dismissible" role="alert">
    Data added succesfully
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
  if($_COOKIE['msg']=="update")
  {

  ?>
  <div class="alert alert-primary alert-dismissible" role="alert">
    Data updated succesfully
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
  if($_COOKIE['msg']=="data_del")
  {

  ?>
  <div class="alert alert-primary alert-dismissible" role="alert">
    Data deleted succesfully
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
    Incorrrect Email! Try again.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
}
  if(isset($_COOKIE["sql_error"]))
  {
    ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
      <?php echo urldecode($_COOKIE['sql_error'])?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      </button>
    </div>

    <script type="text/javascript">eraseCookie("sql_error")</script>
    <?php
  }
?>


              <!-- Basic Layout -->
              <div class="row">
                <div class="col-xl">
                  <div class="card mb-4">
                  <!--  <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">Add State</h5>
                      
                    </div>  -->
                    <div class="card-body">
                      <form method="post" >
                       
                        <input type="hidden" name="ttId" id="ttId">
                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Enter Your Email</label>
                          <input type="text" class="form-control" name="email" id="email"  required />
                        </div>
                        
                    
                        <button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary">Save</button>
                    
                        <button type="reset" name="btncancel" id="btncancel" class="btn btn-secondary" onclick="window.location.reload()">Cancel</button>

                      </form>
                    </div>
                  </div>
                </div>
                
              </div>
           
            <!-- / Content -->
<script type="text/javascript">

</script>
<?php 
  include("footer.php");
?>