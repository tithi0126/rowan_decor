<?php
include "header.php";


if (isset($_COOKIE['edit_id'])) {
	$mode = 'edit';
	$editId = $_COOKIE['edit_id'];
	$stmt = $obj->con1->prepare("select * from architect where id=?");
	$stmt->bind_param('i', $editId);
	$stmt->execute();
	$data = $stmt->get_result()->fetch_assoc();
	$stmt->close();
}

if (isset($_COOKIE['view_id'])) {
	$mode = 'view';
	$viewId = $_COOKIE['view_id'];
	$stmt = $obj->con1->prepare("select * from architect where id=?");
	$stmt->bind_param('i', $viewId);
	$stmt->execute();
	$data = $stmt->get_result()->fetch_assoc();
	$stmt->close();
}


// insert data
if(isset($_REQUEST['btnsubmit']))
{
	$name = $_REQUEST['name'];
    $contact = $_REQUEST['contact'];
    $whatsapp_no = $_REQUEST['whatsapp_no'];
	$status = $_REQUEST['status'];

	try
	{
		$stmt = $obj->con1->prepare("INSERT INTO `architect`(`name`,`contact`,`whatsapp_no`,`status`) VALUES (?,?,?,?)");
		$stmt->bind_param("ssss",$name,$contact,$whatsapp_no,$status);
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
		header("location:architect.php");
	}
	else
	{
		setcookie("msg", "fail",time()+3600,"/");
		header("location:architect.php");
	}
}

if(isset($_REQUEST['btnupdate']))
{
	$name = $_REQUEST['name'];
    $contact = $_REQUEST['contact'];
    $whatsapp_no = $_REQUEST['whatsapp_no'];
	$status = $_REQUEST['status'];
	$e_id=$_COOKIE['edit_id'];
	
	try
	{
        // echo"UPDATE architect SET `name`=$name, `contact`=$contact, `status`=$status where id=$e_id";
		$stmt = $obj->con1->prepare("UPDATE architect SET name =?, contact =?, whatsapp_no =?, status = ? WHERE id =?");
		$stmt->bind_param("ssssi",$name,$contact,$whatsapp_no,$status,$e_id);
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
		header("location:architect.php");
	}
	else
	{
		setcookie("msg", "fail",time()+3600,"/");
		 header("location:architect.php");
	}
}
?>
<div class="row" id="p1">
	<div class="col-xl">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<h5 class="mb-0"> <?php echo (isset($mode)) ? (($mode == 'view') ? 'View' : 'Edit') : 'Add' ?> architect</h5>

			</div>
			<div class="card-body">
				<form method="post" >

					<div class="row g-2">
						<div class="col mb-3">
							<label class="form-label" for="basic-default-fullname">Architect Name</label>
							<input type="text" class="form-control" name="name" id="name" value="<?php echo (isset($mode)) ? $data['name'] : '' ?>"
                            <?php echo isset($mode) && $mode == 'view' ? 'readonly' : '' ?> required />
						</div>
                        <div class="col mb-3">
							<label class="form-label" for="basic-default-fullname">Contact</label>
							<input type="text" class="form-control" name="contact" id="contact"  maxlength="10" pattern="[6789][0-9]{9}"value="<?php echo (isset($mode)) ? $data['contact'] : '' ?>"
                            <?php echo isset($mode) && $mode == 'view' ? 'readonly' : '' ?> required />
						</div>
					</div>
                    <div class="row g-2">
						<div class="col mb-3">
							<label class="form-label" for="basic-default-fullname">Whatsapp No.</label>
							<input type="text" class="form-control" name="whatsapp_no" id="whatsapp_no" maxlength="10" pattern="[6789][0-9]{9}"value="<?php echo (isset($mode)) ? $data['whatsapp_no'] : '' ?>"
                            <?php echo isset($mode) && $mode == 'view' ? 'readonly' : '' ?> required />
						</div>
                    </div>

					<div class="mb-3">
						<label class="form-label d-block" for="basic-default-fullname">Status</label>
						<div class="form-check form-check-inline mt-3">
							<input class="form-check-input" type="radio" name="status" id="Enable" value="Enable" <?php echo isset($mode) && $data['status'] == 'Enable' ? 'checked' : '' ?> <?php echo isset($mode) && $mode == 'view' ? 'disabled' : '' ?> required checked>
							<label class="form-check-label" for="inlineRadio1">Enable</label>
						</div>
						<div class="form-check form-check-inline mt-3">
							<input class="form-check-input" type="radio" name="status" id="Disable" value="Disable" <?php echo isset($mode) && $data['status'] == 'Disable' ? 'checked' : '' ?> <?php echo isset($mode) && $mode == 'view' ? 'disabled' : '' ?> required>
							<label class="form-check-label" for="inlineRadio1">Disable</label>
						</div>
					</div>
					<button type="submit"  name="<?php echo isset($mode) && $mode == 'edit' ? 'btnupdate' : 'btnsubmit' ?>" id="save"
                        class="btn btn-success <?php echo isset($mode) && $mode == 'view' ? 'd-none' : '' ?>">
                        <?php echo isset($mode) && $mode == 'edit' ? 'Update' : 'Save' ?>
                    </button>
                    <button type="button" class="btn btn-danger"
                        onclick="<?php echo (isset($mode)) ? 'javascript:go_back()' : 'window.location.reload()' ?>">
                     Close</button>

			</form>
		</div>
	</div>
</div>

</div>
<script>
	function go_back() {
    eraseCookie("edit_id");
    eraseCookie("view_id");
		window.location = "architect.php";
	}
</script>
<?php
include "footer.php";
?>