<?php
include "header.php";
// delete data
if(isset($_REQUEST["btndelete"]))
{
  try
  {
    $stmt_del = $obj->con1->prepare("delete from city where city_id='".$_REQUEST["n_id"]."'");
  	$Resp=$stmt_del->execute();
    if(!$Resp)
    {
      if(strtok($obj->con1-> error,  ':')=="Cannot delete or update a parent row")
      {
        throw new Exception("City is already in use!");
      }
    }
    $stmt_del->close();
  } 
  catch(\Exception  $e) {
    setcookie("sql_error", urlencode($e->getMessage()),time()+3600,"/");
  }

  if($Resp)
  {
	 setcookie("msg", "data_del",time()+3600,"/");
  }
  header("location:city.php");
}

if(isset($_REQUEST["btnexcelsubmit"])<>"")
{ 
  $state=$_REQUEST['exl_state'];
  $x_file=$_FILES["excel_file"]["tmp_name"];
  $msg1=$msg2=$msg3=$msg4="";
  
  set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
  include 'Classes/PHPExcel/IOFactory.php';
  $inputFileName = $x_file; 
  
  try 
  {
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
  } 
  catch(Exception $e) 
  {
    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage()); 
  }
  $allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

  $arrayCount = count($allDataInSheet);  // Here get total count of row in that Excel sheet

  for($i=2;$i<=$arrayCount;$i++)
  {
    $city_name = trim($allDataInSheet[$i]["A"]);
    $status=trim($allDataInSheet[$i]["B"]);
     
    $action='added';

    if($city_name!="")
    {
      $stmt_city_ck = $obj->con1->prepare("SELECT * FROM city WHERE city_name = '".$city_name."'");
      $stmt_city_ck->execute();
      $city_result = $stmt_city_ck->get_result()->num_rows;
      $stmt_city_ck->close();
        
      if($city_result>0)
      {
        $msg1.= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;"> Record no. '.$i.": ".$city_name."".'  city name already exists in database.</div>';
      }
      else
      {
        $stmt = $obj->con1->prepare("INSERT INTO `city`(`city_name`,`state`,`status`,`action`) VALUES (?,?,?,?)");
        $stmt->bind_param("siss",$city_name,$state,$status,$action);
        $Resp=$stmt->execute();
        if($Resp>0)
        {
          $msg2.= '<div style="font-family:serif;font-size:18px;Padding:0px 0 0 0;margin:10px 0px 0px 0px;">Record no. '.$i.": ".'  Added Successfully in database.</div>';           
        }
        else
        {
          $msg3.= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;">Record no. '.$i.": ".'  Record not added in database.</div>';
        }
      }
    }
    else{
      $msg4.= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;"> Record no. '.$i.":".'  is null.</div>';   
    }
    
    $msges=$msg1.$msg2.$msg3.$msg4;
    
    setcookie("excelmsg", $msges,time()+3600,"/");
     
    header("location:city.php");      
  } 
}

?>

<h4 class="fw-bold py-3 mb-4">City Master</h4>

<?php 
if(isset($_COOKIE["msg"]) )
{

  if($_COOKIE['msg']=="data")
  {

  ?>
  <div class="alert alert-success alert-dismissible" role="alert">
    <i class='bx bxs-check-circle'></i>Data added succesfully
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
  if($_COOKIE['msg']=="update")
  {

  ?>
  <div class="alert alert-success alert-dismissible" role="alert">
    <i class='bx bxs-check-circle'></i>Data updated succesfully
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>
  <script type="text/javascript">eraseCookie("msg")</script>
  <?php
  }
  if($_COOKIE['msg']=="data_del")
  {

  ?>
  <div class="alert alert-success alert-dismissible" role="alert">
    <i class='bx bxs-cross-circle'></i>Data deleted succesfully
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
    <i class='bx bx-x-circle'></i>An error occured! Try again.
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
  if(isset($_COOKIE["excelmsg"]))
  {
  ?>
  <div class="alert alert-primary alert-dismissible" role="alert">
      <?php echo $_COOKIE['excelmsg']?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      </button>
    </div>

    <script type="text/javascript">eraseCookie("excelmsg")</script>

<?php
  }
?>


              
           
<!-- Modal -->
  <div class="modal fade" id="excel_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel1">Upload Via Excel</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="row">
                    <form method="post" enctype="multipart/form-data">
                    <div class="col mb-3">
                      <label for="nameBasic" class="form-label">State</label>
                      
                      <select name="exl_state" id="exl_state" class="form-control" required>
                        <option value="">Select State</option>
                        <?php    
                            while($state=mysqli_fetch_array($res2)){
                        ?>
                            <option value="<?php echo $state["state_id"] ?>"><?php echo $state["state_name"] ?></option>
                        <?php
                          }
                        ?>
                        </select>
                    </div>
                  </div>
                  <div class="row g-2">
                    <div class="col mb-0">
                      <label for="emailBasic" class="form-label">Excel File</label>
                      <input type="file" id="excel_file" name="excel_file" class="form-control" >
                    </div>
                    
                  </div>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" name="btnexcelsubmit" class="btn btn-primary">Save changes</button>
                </div>
                </form>
              </div>
            </div>
          </div>
<!--- modal -->

<!-- Delete Modal -->
<div class="modal fade" id="backDropModal" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="backDropModalTitle">Delete Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col mb-3">
            <label for="nameBackdrop" class="form-label" id="label_del"></label>
            <input type="hidden" name="n_id" id="n_id">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="btndelete" class="btn btn-primary">Delete</button>
      </div>
    </form>
  </div>
</div>



           <!-- grid -->

           <!-- Basic Bootstrap Table -->
              <div class="card mb-4">
                
                <div class="row ms-2 me-3">
    <div class="col-md-6" style="margin:1%">
      <a class="btn btn-primary" href="city_add.php" 
                           style="margin-right:15px;">Add City</a>
                    <a class="btn btn-primary" href="excel/demo_city.xlsx" 
                           style="margin-right:15px;"><i class="bx bx-download"></i> Download Demo Excel</a>
                    <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#excel_modal"
                           style="margin-right:15px; color: #fff;"><i class="bx bx-upload"></i>  Export Data</a>
                  </div>
                    <div class="table-responsive text-nowrap">
                  <table class="table table-hover" id="table_id">

                    <thead>
                      <tr>
                        <th>Srno</th>
                        <th>City Name</th>
                        <th>State</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <?php 
                        $stmt_list = $obj->con1->prepare("select  c1.*,s1.state_name,s1.state_id from city c1, state s1 where c1.state=s1.state_id order by c1.city_id desc");
                        $stmt_list->execute();
                        $result = $stmt_list->get_result();
                        
                        $stmt_list->close();
                        $i=1;
                        while($city=mysqli_fetch_array($result))
                        {
                          ?>

                      <tr>
                        <td><?php echo $i?></td>
                        <td><?php echo $city["city_name"]?></td>
                        <td><?php echo $city["state_name"]?></td>
                    <?php if($city["status"]=='enable'){	?>
                        <td style="color:green"><?php echo $city["status"]?></td>
                    <?php } else if($city["status"]=='disable'){	?>
                        <td style="color:red"><?php echo $city["status"]?></td>
                    <?php } ?>
                    
                        <td>
                        	<a href="javascript:editdata('<?php echo $city["city_id"]?>','<?php echo $city["state_id"]?>','<?php echo base64_encode($city["city_name"])?>','<?php echo $city["status"]?>');"><i class="bx bx-edit-alt me-1"></i> </a>
							<a  href="javascript:deletedata('<?php echo $city["city_id"]?>','<?php echo base64_encode($city["city_name"])?>');"><i class="bx bx-trash me-1"></i> </a>
                        	<a href="javascript:viewdata('<?php echo $city["city_id"]?>','<?php echo $city["state_id"]?>','<?php echo base64_encode($city["city_name"])?>','<?php echo $city["status"]?>');">View</a>
                        </td>
                      </tr>
                      <?php
                          $i++;
                        }
                      ?>
                      
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
              <!--/ Basic Bootstrap Table -->


           <!-- / grid -->
            <!-- / Content -->
<script type="text/javascript">
     function adddata() {
    eraseCookie("edit_id");
    eraseCookie("view_id");
    window.location = "city_add.php";
}

function editdata(id) {
    eraseCookie("view_id");
    createCookie("edit_id", id, 1);
    window.location = "city_add.php";
}

function viewdata(id) {
    eraseCookie("edit_id");
    createCookie("view_id", id, 1);
    window.location = "city_add.php";
}
  function deletedata(id,name) {
    $('#backDropModal').modal('toggle');
    $('#n_id').val(id);
    $('#label_del').html('Are you sure you want to DELETE city - '+ atob(name)+' ?');
  }
</script>
<?php 
include "footer.php";
?>