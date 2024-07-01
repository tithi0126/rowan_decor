<?php
include ("header.php");

$todays_date = date('Y-m-d');

$date = isset($_COOKIE["dash_date"]) ? $_COOKIE['dash_date'] : date('Y-m-d');
setcookie("selected_date", $date, time() + 3600, "/");

// total customers
// $stmt_list1 = $obj->con1->prepare("select * from customer_reg where `status`='enable'");
// $stmt_list1->execute();
// $total_cust = $stmt_list1->get_result()->num_rows;	
// $stmt_list1->close();

// // today's new customers
// $stmt_list2 = $obj->con1->prepare("select * from customer_reg where `status`='enable' and dt like '%".$date."%'");
// $stmt_list2->execute();
// $todays_cust = $stmt_list2->get_result()->num_rows;	
// $stmt_list2->close();

// // today's dispatch
// $stmt_list3 = $obj->con1->prepare("select * from post where dispatch_date='".$date."'");
// $stmt_list3->execute();
// $todays_dispatch = $stmt_list3->get_result()->num_rows;	
// $stmt_list3->close();

// // total delivery boy
// $stmt_list4 = $obj->con1->prepare("select * from delivery_boy where status='enable'");
// $stmt_list4->execute();
// $total_deli_boy = $stmt_list4->get_result()->num_rows;	
// $stmt_list4->close();

// // today's new delivery boy
// $stmt_list5 = $obj->con1->prepare("select * from delivery_boy where status='enable' and dt like '%".$date."%'");
// $stmt_list5->execute();
// $todays_deli_boy = $stmt_list5->get_result()->num_rows;
// $stmt_list5->close();

// // upcoming post

// $stmt_list6 = $obj->con1->prepare("select * from post where cast(dispatch_date as date)>'".$date."'");
// $stmt_list6->execute();
// $upcoming_post = $stmt_list6->get_result()->num_rows;	
// $stmt_list6->close();

// // today's post
// $stmt_list7 = $obj->con1->prepare("select * from post where order_date like '%".$date."%'");
// $stmt_list7->execute();
// $todays_post = $stmt_list7->get_result()->num_rows;	
// $stmt_list7->close();

// // today's transit
// $stmt_list8 = $obj->con1->prepare("select * from post where post_status='transit' and collection_date='".$date."'");
// $stmt_list8->execute();
// $todays_transit = $stmt_list8->get_result()->num_rows; 
// $stmt_list8->close();
?>
<div class="row">
  <div class="col-lg-12 mb-4 order-0">
    <div class="card">
      <div class="d-flex align-items-end row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary">Welcome Admin</h5>
            <!-- <p class="mb-4">
              You have done <span class="fw-bold">72%</span> more sales today. Check your new badge in
              your profile.
            </p> -->

            <!-- <a href="javascript:;" class="btn btn-sm btn-outline-primary">View Badges</a> -->
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="assets/img/illustrations/man-with-laptop-light.png" height="140" alt="View Badge User"
              data-app-dark-img="illustrations/man-with-laptop-dark.png"
              data-app-light-img="illustrations/man-with-laptop-light.png" />
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



<!-- <div class="row">
  <div class="navbar-nav-right d-flex align-items-center mb-3" id="navbar-collapse">
    <div class="navbar-nav align-items-center">
      <div class="nav-item d-flex align-items-center">
      <!--  <i class="bx bx-calendar fs-4 lh-0"></i>  -->
<!-- <form method="post" id="dashboard_frm">
        <input type="date" class="form-control border-0 shadow-none" name="dash_date" id="dash_date" onchange="get_dashboard_data(this.value)" value="<?php echo isset($_COOKIE['dash_date']) ? $_COOKIE['dash_date'] : date('Y-m-d') ?>">
        <input type="submit" name="dash_submit" class="d-none">
      </form>
      </div>
    </div>
  </div>
    <div class="col-lg-12 col-md-12 order-1">
      <div class="row">
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="post.php">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-credit-card-front"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt1"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu" aria-labelledby="cardOpt1">
                    <a class="dropdown-item" href="customer_report.php?typ=today">View Report</a>
                   
                    
                  </div>
                </div>
              </div>
      <?php if ($todays_date == $date) { ?>
              <span class="fw-semibold d-block mb-1">Today's Post</span>
      <?php } else { ?>
              <span class="fw-semibold d-block mb-1"><?php echo date('d-m-Y', strtotime($date)) ?> 's Post</span>
      <?php } ?>
              <h3 class="card-title mb-2"><?php echo $todays_post ?></h3>
              
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="post.php">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bx-credit-card-front"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt1"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu" aria-labelledby="cardOpt1">
                    <a class="dropdown-item" href="customer_report.php?typ=upcoming">View Report</a>
                    
                    
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Upcoming Post</span>
              <h3 class="card-title mb-2"><?php echo $upcoming_post ?></h3>
              
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="customer_report.php?typ=today_transit">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-bus"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt1"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu" aria-labelledby="cardOpt1">
                    <a class="dropdown-item" href="customer_report.php?typ=today_transit">View Report</a>
                    
                  </div>
                </div>
              </div>
        <?php if ($todays_date == $date) { ?>
              <span class="fw-semibold d-block mb-1">Today's Transit</span>
        <?php } else { ?>
              <span class="fw-semibold d-block mb-1"><?php echo date('d-m-Y', strtotime($date)) ?> 's Transit</span>
        <?php } ?>
              <h3 class="card-title mb-2"><?php echo $todays_transit ?></h3>
              
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="customer_report.php?typ=today_dispatch">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-package"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt1"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu" aria-labelledby="cardOpt1">
                    <a class="dropdown-item" href="customer_report.php?typ=today_dispatch">View Report</a>
                    
                  </div>
                </div>
              </div>
        <?php if ($todays_date == $date) { ?>
              <span class="fw-semibold d-block mb-1">Today's Dispatch</span>
        <?php } else { ?>
              <span class="fw-semibold d-block mb-1"><?php echo date('d-m-Y', strtotime($date)) ?> 's Dispatch</span>
        <?php } ?>
              <h3 class="card-title mb-2"><?php echo $todays_dispatch ?></h3>
              
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="cust_report.php?typ=total">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-user-pin"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt3"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                    <a class="dropdown-item" href="cust_report.php?typ=total">View Report</a>
                    
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Total Customers</span>
              <h3 class="card-title mb-2"><?php echo $total_cust ?></h3>
              
            </div>
          </div>
        </div> -->

<!-- <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="cust_report.php?typ=today">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-user-plus"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button class="btn p-0" type="button" id="cardOpt4"  data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                    <a class="dropdown-item" href="cust_report.php?typ=today">View Report</a>
                  </div>
                </div>
              </div>
        <?php if ($todays_date == $date) { ?>
              <span class="fw-semibold d-block mb-1">Today's New Customers</span>
        <?php } else { ?>
              <span class="fw-semibold d-block mb-1"><?php echo date('d-m-Y', strtotime($date)) ?> 's New Customers</span>
        <?php } ?><h3 class="card-title  mb-2"><?php echo $todays_cust ?></h3>        
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="delivery_boy_report.php?typ=total">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-user-badge"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt1"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu" aria-labelledby="cardOpt1">
                    <a class="dropdown-item" href="delivery_boy_report.php?typ=total">View Report</a>
                    
                  </div>
                </div>
              </div>
              <span class="fw-semibold d-block mb-1">Total Delivery Boy</span>
              <h3 class="card-title mb-2"><?php echo $total_deli_boy ?></h3>
              
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-12 col-6 mb-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title d-flex align-items-start justify-content-between">
                <div class="avatar flex-shrink-0">
                  <a class="dropdown-item" href="delivery_boy_report.php?typ=today">
                    <span class="avatar-initial rounded bg-label-primary"> <i class="bx bxs-user-badge"></i></span>
                  </a>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="cardOpt1"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="bx bx-dots-vertical-rounded"></i>
                  </button>
                  <div class="dropdown-menu" aria-labelledby="cardOpt1">
                    <a class="dropdown-item" href="delivery_boy_report.php?typ=today">View Report</a>
                    
                  </div>
                </div>
              </div>
        <?php if ($todays_date == $date) { ?>
              <span class="fw-semibold d-block mb-1">Today's New Delivery Boy</span>
        <?php } else { ?>
              <span class="fw-semibold d-block mb-1"><?php echo date('d-m-Y', strtotime($date)) ?> 's New Delivery Boy</span>
        <?php } ?>
              <h3 class="card-title mb-2"><?php echo $todays_deli_boy ?></h3>
              
            </div>
          </div>
        </div>
        

      </div>
    </div>
</div>     -->


<script type="text/javascript">
  // Use datepicker on the date inputs
  /*$("#dash_date").datepicker({
    dateFormat: 'dd-mm-yyyy',
    onSelect: function(dateText, inst) {
      $(inst).val(dateText); // Write the value in the input
    }
  });*/



</script>




<?php
include ("footer.php");
?>