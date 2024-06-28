<?php
include("header.php");

//check ;

$stmt_desig = $obj->con1->prepare("select * from designation where page='" . $page_name . "' and designation='" . $_SESSION['designation'] . "'");
$stmt_desig->execute();
$res_design = $stmt_desig->get_result()->fetch_assoc();
$stmt_desig->close();

$stmt_page_list = $obj->con1->prepare("select * from pages where page_name not in (select page from designation where designation='" . $_SESSION["designation"] . "')order by id desc");
$stmt_page_list->execute();
$page_result = $stmt_page_list->get_result();
$stmt_page_list->close();

$stmt_design = $obj->con1->prepare("select * from designation_master  order by id desc");
$stmt_design->execute();
$design_result = $stmt_design->get_result();
$stmt_design->close();

/*set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
include 'Classes/PHPExcel/IOFactory.php';*/
/*if(isset($_REQUEST["btnexcelsubmit"])<>"")
{ 
  //$state=$_REQUEST['exl_state'];
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
    $holiday = trim($allDataInSheet[$i]["A"]);
    $date = trim($allDataInSheet[$i]["B"]);
    
     
    $action='added';

    if($holiday!="" && $date!="")
    {
      $stmt_city_ck = $obj->con1->prepare("SELECT * FROM holiday WHERE holiday = '".$holiday."'");
      $stmt_city_ck->execute();
      $city_result = $stmt_city_ck->get_result()->num_rows;
      $stmt_city_ck->close();
        
      if($city_result>0)
      {
        $msg1.= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;"> Record no. '.$i.": ".$holiday."".'  page name already exists in database.</div>';
      }
      else
      {
        $stmt = $obj->con1->prepare("INSERT INTO `holiday`(`holiday`, `date`, `action`) values (?,?,?)");
        $stmt->bind_param("sss",$holiday,$date,$action);
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
     
    header("location:holiday.php");   
  }
}*/



// insert data
if (isset($_REQUEST['btnsubmit'])) {
    $info_t = $_REQUEST['info_type'];
    $info_v = $_REQUEST['info_value'];
    try {
        $stmt = $obj->con1->prepare("INSERT INTO `info`(`info_type`, `info_value`) values (?,?)");
        $stmt->bind_param("ss", $info_t, $info_v);
        $Resp = $stmt->execute();


        if (!$Resp) {
            throw new Exception("Problem in adding! " . strtok($obj->con1->error,  '('));
        }
        $stmt->close();
    } catch (\Exception  $e) {
        setcookie("sql_error", urlencode($e->getMessage()), time() + 3600, "/");
    }


    if ($Resp) {
        setcookie("msg", "data", time() + 3600, "/");
        header("location:info.php");
    } else {
        setcookie("msg", "fail", time() + 3600, "/");
        header("location:info.php");
    }
}

if (isset($_REQUEST['btnupdate'])) {
    $info_t = $_REQUEST['info_type'];
    $info_v = $_REQUEST['info_value'];
    $id = $_REQUEST['ttId'];
    try {
        $stmt = $obj->con1->prepare("UPDATE `info` SET `info_type`=?,`info_value`=? WHERE `id`=?");
        $stmt->bind_param("ssi", $info_t, $info_v, $id);
        $Resp = $stmt->execute();
        if (!$Resp) {
            throw new Exception("Problem in updating! " . strtok($obj->con1->error,  '('));
        }
        $stmt->close();
    } catch (\Exception  $e) {
        setcookie("sql_error", urlencode($e->getMessage()), time() + 3600, "/");
    }


    if ($Resp) {
        setcookie("msg", "update", time() + 3600, "/");
        header("location:info.php");
    } else {
        setcookie("msg", "fail", time() + 3600, "/");
        header("location:info.php");
    }
}

// delete data
if (isset($_REQUEST["btndelete"])) {
    try {
        $stmt_del = $obj->con1->prepare("delete from info where id='" . $_REQUEST["n_id"] . "'");
        $Resp = $stmt_del->execute();
        if (!$Resp) {
            if (strtok($obj->con1->error,  ':') == "Cannot delete or update a parent row") {
                throw new Exception("designation is already in use!");
            }
        }
        $stmt_del->close();
    } catch (\Exception  $e) {
        setcookie("sql_error", urlencode($e->getMessage()), time() + 3600, "/");
    }

    if ($Resp) {
        setcookie("msg", "data_del", time() + 3600, "/");
    }
    header("location:info.php");
}

if (isset($_REQUEST["btnexcelsubmit"]) <> "") {
    //$state=$_REQUEST['exl_state'];
    $x_file = $_FILES["excel_file"]["tmp_name"];
    $msg1 = $msg2 = $msg3 = $msg4 = "";

    set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
    include 'Classes/PHPExcel/IOFactory.php';
    $inputFileName = $x_file;

    try {
        $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
    } catch (Exception $e) {
        die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) . '": ' . $e->getMessage());
    }
    $allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

    $arrayCount = count($allDataInSheet);  // Here get total count of row in that Excel sheet

    for ($i = 2; $i <= $arrayCount; $i++) {
        $info_type = trim($allDataInSheet[$i]["A"]);
        $info_value = trim($allDataInSheet[$i]["B"]);

        if ($info_type != "" && $info_value != "") {
            $stmt_info_ck = $obj->con1->prepare("SELECT * FROM info WHERE info_type = '" . $info_type . "'");
            $stmt_info_ck->execute();
            $info_result = $stmt_info_ck->get_result()->num_rows;
            $stmt_info_ck->close();

            if ($info_result > 0) {
                $msg1 .= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;"> Record no. ' . $i . ": " . $info_type . "" . '  city name already exists in database.</div>';
            } else {
                $stmt = $obj->con1->prepare("INSERT INTO `info`(`info_type`, `info_value`) VALUES (?,?)");
                $stmt->bind_param("ss", $info_type, $info_value);
                $Resp = $stmt->execute();
                if ($Resp > 0) {
                    $msg2 .= '<div style="font-family:serif;font-size:18px;Padding:0px 0 0 0;margin:10px 0px 0px 0px;">Record no. ' . $i . ": " . '  Added Successfully in database.</div>';
                } else {
                    $msg3 .= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;">Record no. ' . $i . ": " . '  Record not added in database.</div>';
                }
            }
        } else {
            $msg4 .= '<div style="font-family:serif;font-size:18px;color:rgb(214, 13, 42);Padding:0px 0 0 0;margin:10px 0px 0px 0px;"> Record no. ' . $i . ":" . '  is null.</div>';
        }

        $msges = $msg1 . $msg2 . $msg3 . $msg4;

        setcookie("excelmsg", $msges, time() + 3600, "/");

        header("location:info.php");
    }
}

?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h4 class="fw-bold py-3 mb-4">Info Master</h4>

<?php
if (isset($_COOKIE["msg"])) {

    if ($_COOKIE['msg'] == "data") {

?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <i class='bx bxs-check-circle'></i>Data added succesfully
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>
        <script type="text/javascript">
            eraseCookie("msg")
        </script>
    <?php
    }
    if ($_COOKIE['msg'] == "update") {

    ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <i class='bx bxs-check-circle'></i>Data updated succesfully
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>
        <script type="text/javascript">
            eraseCookie("msg")
        </script>
    <?php
    }
    if ($_COOKIE['msg'] == "data_del") {

    ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <i class='bx bxs-cross-circle'></i>Data deleted succesfully
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>
        <script type="text/javascript">
            eraseCookie("msg")
        </script>
    <?php
    }
    if ($_COOKIE['msg'] == "fail") {
    ?>

        <div class="alert alert-danger alert-dismissible" role="alert">
            <i class='bx bx-x-circle'></i>An error occured! Try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>
        <script type="text/javascript">
            eraseCookie("msg")
        </script>
    <?php
    }
}
if (isset($_COOKIE["sql_error"])) {
    ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <?php echo urldecode($_COOKIE['sql_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
    </div>

    <script type="text/javascript">
        eraseCookie("sql_error")
    </script>
<?php
}
if (isset($_COOKIE["excelmsg"])) {
?>
    <div class="alert alert-primary alert-dismissible" role="alert">
        <?php echo $_COOKIE['excelmsg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
    </div>

    <script type="text/javascript">
        eraseCookie("excelmsg")
    </script>

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

                <form method="post" enctype="multipart/form-data">


                    <div class="row g-2">
                        <div class="col mb-0">
                            <label for="emailBasic" class="form-label">Excel File</label>
                            <input type="file" id="excel_file" name="excel_file" class="form-control">
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


<!-- Modal -->
<!--  <div class="modal fade" id="excel_modal" tabindex="-1" aria-hidden="true">
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
// modal 
-->

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
        <div class="col-md-5">
            <h5 class="card-header">Info Records</h5>
        </div>
        <?php
        if ($res_design["add_p"] == "yes") {
        ?>
            <div class="col-md-6" style="margin:1%">
                <a class="btn btn-primary" href="excel/demo_info.xlsx" style="margin-right:15px;"><i class="bx bx-download"></i> Download Demo Excel</a>
                <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#excel_modal" style="margin-right:15px; color: #fff;"><i class="bx bx-upload"></i> Export Data</a>
            </div>
        <?php
        }
        ?>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover" id="table_id">

                <thead>
                    <tr>
                        <th>Srno</th>
                        <th>Info Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php $stmt_list = $obj->con1->prepare("select  * from info order by id desc");

                    $stmt_list->execute();
                    $result = $stmt_list->get_result();

                    $stmt_list->close();
                    $i = 1;
                    while ($design = mysqli_fetch_array($result)) {
                    ?>

                        <tr>
                            <td><?php echo $i ?></td>
                            <td><?php echo $design["info_type"] ?></td>
                            <td><?php echo $design["info_value"] ?></td>
                            <td>
                                <?php
                                if ($res_design["update_p"] == "yes") {
                                ?>
                                    <a href="javascript:editdata('<?php echo $design["id"] ?>','<?php echo $design["info_type"] ?>','<?php echo $design["info_value"] ?>');"><i class="bx bx-edit-alt me-1"></i> </a>
                                <?php
                                }

                                if ($res_design["delete_p"] == "yes") {
                                ?>
                                    <a href="javascript:deletedata('<?php echo $design["id"] ?>');"><i class="bx bx-trash me-1"></i> </a>
                                <?php
                                }
                                
                                if ($res_design["view_p"] == "yes") {
                                ?>
                                    <a href="javascript:viewdata('<?php echo $design["id"] ?>','<?php echo $design["info_type"] ?>','<?php echo $design["info_value"] ?>');">View</a>
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
</div>
<!--/ Basic Bootstrap Table -->


<!-- / grid -->


<!-- Basic Layout -->
<?php
if ($res_design["add_p"] == "yes" || $res_design["update_p"] == "yes" || $res_design["view_p"] == "yes") {
?>
    <div class="row" id="p1">
        <div class="col-xl">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Add Info</h5>

                </div>
                <div class="card-body">
                    <form method="post">

                        <div class="row g-2">
                            <div class="col mb-3">
                                <label class="form-label" for="basic-default-fullname">Info Type</label>

                                <input type="text" name="info_type" id="info_type" class="form-control " required>

                                <input type="hidden" name="ttId" id="ttId">
                            </div>

                            <div class="col mb-3">
                                <label class="form-label" for="basic-default-fullname">Description</label>

                                <input type="text" name="info_value" id="info_value" class="form-control" required>
                            </div>
                        </div>
                        <?php
                        if ($res_design["add_p"] == "yes") {
                        ?>
                            <button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary">Save</button>
                        <?php
                        }
                        if ($res_design["update_p"] == "yes") {
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
    function deletedata(id) {
        $('#backDropModal').modal('toggle');
        $('#n_id').val(id);
        $('#label_del').html('Are you sure you want to DELETE record ?');
    }

    function editdata(id, info_type, info_value) {
        $('html, body').animate({
            scrollTop: $("#p1").offset().top
        }, 1);

        $('#info_type').focus();

        $('#ttId').val(id);
        $('#info_type').val(info_type);
        $('#info_value').val(info_value);


        $('#btnsubmit').attr('hidden', true);
        $('#btnupdate').removeAttr('hidden');
        $('#btnsubmit').attr('disabled', true);
        $('#btnupdate').removeAttr('disabled', false);
    }

    function viewdata(id, info_type, info_value) {
        $('html, body').animate({
            scrollTop: $("#p1").offset().top
        }, 1);

        $('#info_type').focus();

        $('#ttId').val(id);
        $('#info_type').val(info_type);
        $('#info_value').val(info_value);

        $('#btnsubmit').attr('hidden', true);
        $('#btnupdate').attr('hidden', true);
        $('#btnsubmit').attr('disabled', true);
        $('#btnupdate').attr('disabled', true);

    }
</script>
<?php
include("footer.php");
?>