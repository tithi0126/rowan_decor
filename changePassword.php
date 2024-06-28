<?php
  include("header.php");

// insert data
if(isset($_REQUEST['btnsubmit']))
{
  $new_pass = $_REQUEST['new_pass'];
  $id=$_SESSION["id"];
  try
  {
    
	$stmt = $obj->con1->prepare("update admin set password=? where id=?");
	$stmt->bind_param("si",$new_pass,$id);
	$Resp=$stmt->execute();
    if(!$Resp)
    {
      throw new Exception("Problem in adding! ". strtok($obj->con1-> error,  '('));
    }
    $stmt->close();
  } 
  catch(\Exception  $e) {
    setcookie("sql_error", urlencode($e->getMessage()),time()+3600,"/");
  }


  if($Resp)
  {
	  setcookie("msg", "update",time()+3600,"/");
     header("location:changePassword.php");
  }
  else
  {
	  setcookie("msg", "fail",time()+3600,"/");
      header("location:changePassword.php");
  }
}
?>

<h4 class="fw-bold py-3 mb-4">Change Password</h4>

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
    Password updated succesfully
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
    An error occured! Try again.
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                      
                      
                    </div>
                    <div class="card-body">
                      <form method="post" >
                       
                        <input type="hidden" name="ttId" id="ttId">
                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Enter Current Password</label>
                          <input type="password" class="form-control" name="old_pass" id="old_pass" onblur="checkOldPass(this.value,<?php echo $_SESSION["id"] ?>)" required />
                          <div id="pass_alert" class="text-danger"></div>
                        </div>

                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Enter New Password</label>
                          <input type="password" class="form-control" name="new_pass" id="new_pass" required onkeyup="checkNewPass(new_pass.value,this.value,<?php echo $_SESSION["id"] ?>)"/>
                        </div>

                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Confirm Password</label>
                          <input type="password" class="form-control" name="conf_pass" id="conf_pass" onkeyup="checkNewPass(new_pass.value,this.value,<?php echo $_SESSION["id"] ?>)" required />
                          <div id="conpass_alert" class="text-danger"></div>
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

  function checkOldPass(oldpass,id){
    $.ajax({
      async: true,
      type: "POST",
      url: "ajaxdata.php?action=checkOldPass",
      data: "oldpass="+oldpass+"&id="+id,
      cache: false,
      success: function(result){
        if(result==0)
        {
          $('#pass_alert').html('Enter Correct Password!');
          document.getElementById('btnsubmit').disabled = true;
        }
        else
        {
          $('#pass_alert').html('');
          document.getElementById('btnsubmit').disabled = false;
        }
      }
    });
  }

  function checkNewPass(newpass,confpass,id){
    var oldpass=$('#old_pass').val();
    if(newpass!="" && confpass!="")
    {
      if(newpass==confpass){
      $('#conpass_alert').html('');
      document.getElementById('btnsubmit').disabled = false;
      } else{
        $('#conpass_alert').html('Password Should be Same!');
        document.getElementById('btnsubmit').disabled = true;
        
      }
    checkOldPass(oldpass,id);

    }
    
  }

  
</script>
<?php 
  include("footer.php");
?>