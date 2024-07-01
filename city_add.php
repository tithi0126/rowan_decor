<?php
include "header.php";

$stmt_slist = $obj->con1->prepare("select * from state");
$stmt_slist->execute();
$res = $stmt_slist->get_result();
$stmt_slist->close();

if (isset($_COOKIE['edit_id'])) {
	$mode = 'edit';
	$editId = $_COOKIE['edit_id'];
	$stmt = $obj->con1->prepare("select * from city where city_id=?");
	$stmt->bind_param('i', $editId);
	$stmt->execute();
	$data = $stmt->get_result()->fetch_assoc();
	$stmt->close();
}

if (isset($_COOKIE['view_id'])) {
	$mode = 'view';
	$viewId = $_COOKIE['view_id'];
	$stmt = $obj->con1->prepare("select * from city where city_id=?");
	$stmt->bind_param('i', $viewId);
	$stmt->execute();
	$data = $stmt->get_result()->fetch_assoc();
	$stmt->close();
}


// insert data
if(isset($_REQUEST['btnsubmit']))
{
	$state_id = $_REQUEST['state'];
	$ctnm = $_REQUEST['ctnm'];
	$status = $_REQUEST['status'];

	try
	{


		// SELECT c1.*, s1.name as 'state_name' FROM `city` c1 , `state` s1 WHERE c1.state_id=s1.id AND c1.status='Enable'
		$stmt = $obj->con1->prepare("INSERT INTO `city`(`ctnm`,`state`,`status`) VALUES (?,?,?)");
		$stmt->bind_param("sis",$ctnm,$state_id,$status);
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
		header("location:city.php");
	}
	else
	{
		setcookie("msg", "fail",time()+3600,"/");
		header("location:city.php");
	}
}

if(isset($_REQUEST['btnupdate']))
{
	$state_id = $_REQUEST['state'];
	$ctnm = $_REQUEST['ctnm'];
	$status = $_REQUEST['status'];
	$id=$_REQUEST['ttId'];
	$action='updated';
	try
	{
		$stmt = $obj->con1->prepare("update city set ctnm=?, state=?, status=?,action=? where city_id=?");
		$stmt->bind_param("sissi", $ctnm,$state_id,$status,$action,$id);
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
		header("location:city.php");
	}
	else
	{
		setcookie("msg", "fail",time()+3600,"/");
		header("location:city.php");
	}
}
?>
<div class="row" id="p1">
	<div class="col-xl">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<h5 class="mb-0"> <?php echo (isset($mode)) ? (($mode == 'view') ? 'View' : 'Edit') : 'Add' ?> City</h5>

			</div>
			<div class="card-body">
				<form method="post" >

					<div class="row g-2">
						<div class="col mb-3">
							<label class="form-label" for="basic-default-fullname">State</label>
							<select name="state" id="state" class="form-control" required>
								<option value="">Select State</option>
								<?php   
								while($state=mysqli_fetch_array($res)){
									?>
									<option value="<?php echo $state["state_id"] ?>"><?php echo $state["state_name"] ?></option>
									<?php
								}
								?>
							</select>
							<input type="hidden" name="ttId" id="ttId">
						</div>

						<div class="col mb-3">
							<label class="form-label" for="basic-default-fullname">City Name</label>
							<input type="text" class="form-control" name="ctnm" id="ctnm" required />
						</div>
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
					<button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary">Save</button>
					<button type="submit" name="btnupdate" id="btnupdate" class="btn btn-primary " hidden>Update</button>
					<!-- <button type="reset" name="btncancel" id="btncancel" class="btn btn-secondary" onclick="window.location.reload()">Cancel</button> -->
					<button type="button" class="btn btn-secondary" name="btncancel" id="btncancel"
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
		window.location = "city.php";
	}
</script>
<?php
include "footer.php";
?>