<?php
  include("header.php");
  
//check permissions
$stmt_desig = $obj->con1->prepare("select * from designation where page='".$page_name."' and designation='".$_SESSION['designation']."'");
$stmt_desig->execute();
$res_design = $stmt_desig->get_result()->fetch_assoc();
$stmt_desig->close();

$stmt_design = $obj->con1->prepare("select * from designation_master  order by id desc");
$stmt_design->execute();
$design_result = $stmt_design->get_result();
$stmt_design->close();

// insert data
if(isset($_REQUEST['btnsubmit']))
{
  
  $state_name = $_REQUEST['state_name'];
  $status = $_REQUEST['status'];

  try
  {
	$stmt = $obj->con1->prepare("INSERT INTO `state`(`state_name`,`status`) VALUES (?,?)");
	$stmt->bind_param("ss",$state_name,$status);
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
	  setcookie("msg", "data",time()+3600,"/");
      header("location:state.php");
  }
  else
  {
	  setcookie("msg", "fail",time()+3600,"/");
      header("location:state.php");
  }
}

if(isset($_REQUEST['btnupdate']))
{
  
  $state_name = $_REQUEST['state_name'];
  $status = $_REQUEST['status'];
  $id=$_REQUEST['ttId'];
  $action='updated';
  try
  {
    $stmt = $obj->con1->prepare("update state set state_name=? ,status=?,action=? where state_id=?");
  	$stmt->bind_param("sssi", $state_name,$status,$action,$id);
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
      header("location:state.php");
  }
  else
  {
	  setcookie("msg", "fail",time()+3600,"/");
      header("location:state.php");
  }
}

// delete data
//if(isset($_REQUEST["flg"]) && $_REQUEST["flg"]=="del")
if(isset($_REQUEST["btndelete"]))
{
  try
  {
    $stmt_del = $obj->con1->prepare("delete from state where state_id='".$_REQUEST["n_id"]."'");
  	$Resp=$stmt_del->execute();
    if(!$Resp)
    {
      if(strtok($obj->con1-> error,  ':')=="Cannot delete or update a parent row")
      {
        throw new Exception("State is already in use!");
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
  header("location:state.php");
}

if(isset($_REQUEST["btnexcelsubmit"])<>"")
{ 
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
    $state_name = trim($allDataInSheet[$i]["A"]);
    $status=trim($allDataInSheet[$i]["B"]);
     
    $action='added';

    if($state_name!="")
    {
      $stmt_state_ck = $obj->con1->prepare("SELECT * FROM state WHERE state_name = '".$state_name."'");
      $stmt_state_ck->execute();
      $state_result = $stmt_state_ck->get_result()->num_rows;
      $stmt_state_ck->close();
        
      if($state_result>0)
      {
        $msg1.= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;"> Record no. '.$i.": ".$state_name."".'  state name already exists in database.</div>';
      }
      else
      {
        $stmt = $obj->con1->prepare("INSERT INTO `state`(`state_name`,`status`,`action`) VALUES (?,?,?)");
        $stmt->bind_param("sss",$state_name,$status,$action);
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
     
    header("location:state.php");      
  } 
}

?>

<h4 class="fw-bold py-3 mb-4">State Master</h4>

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
      <i class='bx bxs-x-circle'></i><?php echo urldecode($_COOKIE['sql_error'])?>
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
                  <div class="col-md-5"><h5 class="card-header">State Records</h5></div>
                  <?php 
  if($res_design["add_p"]=="yes")
  {
    ?>
    <div class="col-md-6" style="margin:1%">
                    <a class="btn btn-primary" href="excel/demo_state.xlsx" 
                           style="margin-right:15px;"><i class="bx bx-download"></i> Download Demo Excel</a>
                    <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#excel_modal"
                           style="margin-right:15px; color: #fff;"><i class="bx bx-upload"></i>  Export Data</a>
                  </div>
                  <?php
  }
  ?>
                </div>

                <div class="table-responsive text-nowrap">
                  <table class="table table-hover" id="table_id">

                    <thead>
                      <tr>
                        <th>Srno</th>
                        <th>State Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <?php 
                        $stmt_list = $obj->con1->prepare("select * from state order by state_id desc");
                        $stmt_list->execute();
                        $result = $stmt_list->get_result();
                        
                        $stmt_list->close();
                        $i=1;
                        while($state=mysqli_fetch_array($result))
                        {
                          ?>

                      <tr>
                        <td><?php echo $i?></td>
                        <td><?php echo $state["state_name"]?></td>
                    <?php if($state["status"]=='enable'){	?>
                        <td style="color:green"><?php echo $state["status"]?></td>
                    <?php } else if($state["status"]=='disable'){	?>
                        <td style="color:red"><?php echo $state["status"]?></td>
                    <?php } ?>
                        <td>
                        <?php 
                            if($res_design["update_p"]=="yes")
                            {
                            ?>
                        	<a href="javascript:editdata('<?php echo $state["state_id"]?>','<?php echo base64_encode($state["state_name"])?>','<?php echo $state["status"]?>');"><i class="bx bx-edit-alt me-1"></i> </a>
                            <?php
                            }
                            if($res_design["delete_p"]=="yes"){
                            ?>
                          <a href="javascript:deletedata('<?php echo $state["state_id"]?>','<?php echo base64_encode($state["state_name"])?>');"><i class="bx bx-trash me-1"></i> </a>
                          <?php 
                            }
                            if($res_design["view_p"]=="yes")
                            {
                            ?>
                        	<a href="javascript:viewdata('<?php echo $state["state_id"]?>','<?php echo base64_encode($state["state_name"])?>','<?php echo $state["status"]?>');">View</a>
                            <?php
                              }
                             ?>
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
              <!--/ Basic Bootstrap Table -->


           <!-- / grid -->

<!-- Basic Layout -->
<?php 
                if($res_design["add_p"]=="yes" || $res_design["update_p"]=="yes" || $res_design["view_p"]=="yes")
                {
                ?>
              <div class="row" id="p1">
                <div class="col-xl">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">Add State</h5>
                      
                    </div>
                    <div class="card-body">
                      <form method="post" >
                       
                        <input type="hidden" name="ttId" id="ttId">
                          
                        <div class="row g-2">
                          <div class="col mb-3">
                            <label class="form-label" for="basic-default-fullname">State Name</label>
                            <input type="text" class="form-control" name="state_name" id="state_name" required />
                          </div>
                          <div class="col mb-3"></div>
                        </div>
                        
                        <div class="mb-3">
                          <label class="form-label d-block" for="basic-default-fullname">Status</label>
                          
                          <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="radio" name="status" id="enable" value="enable" required checked>
                            <label class="form-check-label" for="inlineRadio1">Enable</label>
                          </div>
                          <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="radio" name="status" id="disable" value="disable" required>
                            <label class="form-check-label" for="inlineRadio1">Disable</label>
                          </div>
                         
                        </div>
                        
                    <?php
                    if($res_design["add_p"]=="yes")
					{
						?>
                        <button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary">Save</button>
                    <?php
					}
					if($res_design["update_p"]=="yes")
					{
						?>
                        <button type="submit" name="btnupdate" id="btnupdate" class="btn btn-primary " hidden>Update</button>
                    <?php
					}
					?>
                        <button type="reset" name="btncancel" id="btncancel" class="btn btn-secondary" onclick="window.location.reload()">Cancel</button>

                      </form>
                    </div>
                  </div>
                </div>
                
              </div>
<?php
}
?>

            <!-- / Content -->
<script type="text/javascript">
  function deletedata(id,name) {
    $('#backDropModal').modal('toggle');
    $('#n_id').val(id);
    $('#label_del').html('Are you sure to DELETE state - '+ atob(name)+' ?');
    /*  if(confirm("Are you sure to DELETE data?")) {
          var loc = "state.php?flg=del&n_id=" + id;
          window.location = loc;
      }*/
  }
  function editdata(id,sname,status) {
      $('html, body').animate({
          scrollTop: $("#p1").offset().top
      }, 1);

      $('#state_name').focus();   
     	$('#ttId').val(id);
			$('#state_name').val(atob(sname));
			if(status=="enable")
	   	{
			 $('#enable').attr("checked","checked");	
	   	}
	   	else if(status=="disable")
	   	{
			 $('#disable').attr("checked","checked");	
	   	}
			
			$('#btnsubmit').attr('hidden',true);
      $('#btnupdate').removeAttr('hidden');
			$('#btnsubmit').attr('disabled',true);
      $('#btnupdate').removeAttr('disabled',false);
  }
  function viewdata(id,sname,status) {
      $('html, body').animate({
          scrollTop: $("#p1").offset().top
      }, 1);
      
      $('#state_name').focus();
		  $('#ttId').val(id);
      $('#state_name').val(atob(sname));
			if(status=="enable")
		  {
				$('#enable').attr("checked","checked");	
		  }
		  else if(status=="disable")
		  {
				$('#disable').attr("checked","checked");	
		  }
			
			$('#btnsubmit').attr('hidden',true);
      $('#btnupdate').attr('hidden',true);
			$('#btnsubmit').attr('disabled',true);
			$('#btnupdate').attr('disabled',true);

  }
</script>
<?php 
  include("footer.php");
?>