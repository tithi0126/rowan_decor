<?php
include "header.php";
// delete data
if (isset($_REQUEST["btndelete"])) {
  try {
    $stmt_del = $obj->con1->prepare("delete from city where srno='" . $_REQUEST["n_id"] . "'");
    $Resp = $stmt_del->execute();
    if (!$Resp) {
      if (strtok($obj->con1->error, ':') == "Cannot delete or update a parent row") {
        throw new Exception("City is already in use!");
        }
      }
    $stmt_del->close();
    } catch (\Exception $e) {
    setcookie("sql_error", urlencode($e->getMessage()), time() + 3600, "/");
    }

  if ($Resp) {
    setcookie("msg", "data_del", time() + 3600, "/");
    }
  header("location:city.php");
  }
?>

<h4 class="fw-bold py-3 mb-4">City Master</h4>

<?php
if (isset($_COOKIE["msg"])) {

  if ($_COOKIE['msg'] == "data") {

    ?>
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class='bx bxs-check-circle'></i>Data added succesfully
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      </button>
    </div>
    <script type="text/javascript">eraseCookie("msg")</script>
    <?php
    }
  if ($_COOKIE['msg'] == "update") {

    ?>
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class='bx bxs-check-circle'></i>Data updated succesfully
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      </button>
    </div>
    <script type="text/javascript">eraseCookie("msg")</script>
    <?php
    }
  if ($_COOKIE['msg'] == "data_del") {

    ?>
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class='bx bxs-cross-circle'></i>Data deleted succesfully
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
      </button>
    </div>
    <script type="text/javascript">eraseCookie("msg")</script>
    <?php
    }
  if ($_COOKIE['msg'] == "fail") {
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
if (isset($_COOKIE["sql_error"])) {
  ?>
  <div class="alert alert-danger alert-dismissible" role="alert">
    <?php echo urldecode($_COOKIE['sql_error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>

  <script type="text/javascript">eraseCookie("sql_error")</script>
  <?php
  }
if (isset($_COOKIE["excelmsg"])) {
  ?>
  <div class="alert alert-primary alert-dismissible" role="alert">
    <?php echo $_COOKIE['excelmsg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </button>
  </div>

  <script type="text/javascript">eraseCookie("excelmsg")</script>

  <?php
  }
?>

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
      <a class="btn btn-primary" href="city_add.php" style="margin-right:15px;">Add City</a>

    </div>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover" id="table_id">

        <thead>
          <tr>
            <th>Srno</th>
            <th>City Name</th>
            <th>State Name</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <?php
          $stmt_list = $obj->con1->prepare("SELECT c1.*, s1.name as 'state_name' FROM `city` c1 , `state` s1 WHERE c1.state_id=s1.id");
          $stmt_list->execute();
          $result = $stmt_list->get_result();

          $stmt_list->close();
          $i = 1;
          while ($city = mysqli_fetch_array($result)) {
            ?>

            <tr>
              <td><?php echo $i ?></td>
              <td><?php echo $city["ctnm"] ?></td>
              <td><?php echo $city["state_name"] ?></td>
              <?php if ($city["status"] == 'Enable') { ?>
                <td style="color:green"><?php echo $city["status"] ?></td>
              <?php } else if ($city["status"] == 'Disable') { ?>
                  <td style="color:red"><?php echo $city["status"] ?></td>
              <?php } ?>

              <td>
                <a
                  href="javascript:editdata('<?php echo $city["srno"] ?>','<?php echo $city["state_id"] ?>','<?php echo base64_encode($city["ctnm"]) ?>','<?php echo $city["status"] ?>');"><i
                    class="bx bx-edit-alt me-1"></i> </a>
                <a
                  href="javascript:deletedata('<?php echo $city["srno"] ?>','<?php echo base64_encode($city["ctnm"]) ?>');"><i
                    class="bx bx-trash me-1"></i> </a>
                <a
                  href="javascript:viewdata('<?php echo $city["srno"] ?>','<?php echo $city["state_id"] ?>','<?php echo base64_encode($city["ctnm"]) ?>','<?php echo $city["status"] ?>');">View</a>
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
  function deletedata(id, name) {
    $('#backDropModal').modal('toggle');
    $('#n_id').val(id);
    $('#label_del').html('Are you sure you want to DELETE city - ' + atob(name) + ' ?');
  }
</script>
<?php
include "footer.php";
?>
<!-- pushed again -->