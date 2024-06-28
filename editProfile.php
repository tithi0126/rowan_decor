<?php
  include("header.php");

$id = $_SESSION['id'];

$stmt_list = $obj->con1->prepare("select * from admin where id=?");
$stmt_list->bind_param("i",$id);
$stmt_list->execute();
$result = $stmt_list->get_result();
$stmt_list->close();
$admin_res = mysqli_fetch_array($result);


// insert data
if(isset($_REQUEST['btnsubmit']))
{
  
  echo $name = $_REQUEST['name'];
  echo $email = $_REQUEST['email'];
  echo $id;

  try
  {
	$stmt = $obj->con1->prepare("update admin set name=?, email=? where id=?");
	$stmt->bind_param("ssi",$name,$email,$id);
	$Resp=$stmt->execute();
    if(!$Resp)
    {
      throw new Exception("Problem in updating! ". strtok($obj->con1-> error,  '('));
    }
    $stmt->close();
  } 
  catch(\Exception  $e) {
    setcookie("sql_error", urlencode($e->getMessage()),time()+3600,"/");
  }


  if($Resp)
  {
	  setcookie("msg", "update",time()+3600,"/");
      header("location:editProfile.php");
  }
  else
  {
	  setcookie("msg", "fail",time()+3600,"/");
      header("location:editProfile.php");
  }
}

?>

<!-- <h4 class="fw-bold py-3 mb-4">State Master</h4>  -->

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
    Profile updated succesfully
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
                      <h5 class="mb-0">Edit Profile</h5>
                      
                    </div>
                    <div class="card-body">
                      <form method="post" >
                       
                        <input type="hidden" name="ttId" id="ttId">

                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Userid</label>
                          <input type="text" class="form-control" name="userid" id="userid" value="<?php echo $admin_res['userid'] ?>" readonly />
                        </div>

                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Name</label>
                          <input type="text" class="form-control" name="name" id="name" value="<?php echo $admin_res['name'] ?>" required />
                        </div>

                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">Email</label>
                          <input type="text" class="form-control" name="email" id="email" value="<?php echo $admin_res['email'] ?>" required />
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