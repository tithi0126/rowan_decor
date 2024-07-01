<?php
//ob_start();
include ("db_connect.php");
$obj = new DB_connect();
date_default_timezone_set("Asia/Kolkata");
error_reporting(E_ALL);

session_start();


if (!isset($_SESSION["userlogin"])) {
  header("location:index.php");
  }


$adminmenu = array("customer_reg.php", "post.php", "send_notification.php", "privacy_policy.php", "terms.php", "customer_address.php", "assign_module.php", "page.php", "designation.php", "users.php", "holiday.php", "info.php", "config.php", "mail_settings.php", "priority.php");
$location = array("state.php", "city.php", "zone.php", "area.php");
$delivery = array("deliveryboy_reg.php", "delivery_settings.php", "collection_time.php");
$coupon = array("coupon.php", "coupon_counter.php");
$mail = array("mail_type.php", "mail_type_tariff.php");
$review_feedback = array("post_review.php", "customer_feedback.php");
$reportmenu = array("customer_report.php", "cust_report.php", "delivery_boy_report.php");
$page_name = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/"
  data-template="vertical-menu-template-free">

  <head>
    <meta charset="utf-8" />
    <meta name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Dashboard | Rowan Decor</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../img/m_favi.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />


    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="assets/vendor/css/pages/card-analytics.css" />

    <!-- data tables -->
    <link rel="stylesheet" type="text/css" href="assets/vendor/DataTables/datatables.css">

    <link rel="stylesheet" href="assets/vendor/libs/quill/typography.css" />
    <link rel="stylesheet" href="assets/vendor/libs/quill/katex.css" />
    <link rel="stylesheet" href="assets/vendor/libs/quill/editor.css" />

    <!-- Row Group CSS -->
    <!-- <link rel="stylesheet" href="assets/vendor/datatables-rowgroup-bs5/rowgroup.bootstrap5.css"> -->
    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="assets/js/config.js"></script>
    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script type="text/javascript">
      function createCookie(name, value, days) {
        var expires;
        if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
          expires = "; expires=" + date.toGMTString();
        } else {
          expires = "";
        }
        document.cookie = (name) + "=" + String(value) + expires + ";path=/ ";

      }

      function readCookie(name) {
        var nameEQ = (name) + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) === ' ') c = c.substring(1, c.length);
          if (c.indexOf(nameEQ) === 0) return (c.substring(nameEQ.length, c.length));
        }
        return null;
      }

      function eraseCookie(name) {
        createCookie(name, "", -1);
      }
      function get_dashboard_data(date) {
        createCookie("dash_date", date, 1);

        document.getElementById("dashboard_frm").submit();
      }

      $(function () {
        setInterval("get_notification()", 10000);

      });


      function get_notification() {

        $.ajax({
          async: true,
          url: 'ajaxdata.php?action=get_notification',
          type: 'POST',
          data: "",

          success: function (data) {
            // console.log(data);

            var resp = data.split("@@@@");
            $('#notification_list').html('');
            $('#notification_list').append(resp[0]);

            $('#noti_count').html('');

            //if(resp[1]>0) {

            $("#noti_count").addClass("badge-notifications");
            $('#noti_count').append(resp[1]);
            $('#notif_header').show();
            if (resp[2] == 1) {
              playSound();
            }


            /*}
            else
            {     
                $('#noti_count').removeClass('badge-notifications');

                 $('#noti_count').append('');
                 $('#notification_list').hide();
                 $('#notif_header').hide();
                 
            }*/
          }

        });
      }
      function removeNotification(id, typ) {

        $.ajax({
          async: true,
          type: "GET",
          url: "ajaxdata.php?action=removenotification",
          data: "id=" + id + "&type=" + typ,
          async: true,
          cache: false,
          timeout: 50000,

          success: function (data) {

            if (typ == "customer_reg") {
              createCookie("cust_id", data, 1);
              window.open('cust_report_detail.php', '_blank');
            }
            else if (typ == "delivery_reg") {
              createCookie("deli_boy_id", data, 1);
              window.open('deliveryboy_report_detail.php', '_blank');
            }
            else if (typ == "post_accepted") {
              //window.location = "post.php";
              createCookie("post_id", data, 1);
              window.open('customer_report_detail.php', '_blank');
            }
            else if (typ == "post_dispatched") {
              //window.location = "post.php";
              createCookie("post_id", data, 1);
              window.open('customer_report_detail.php', '_blank');
            }
            else if (typ == "post_rejected") {
              //window.location = "post.php";
              createCookie("post_id", data, 1);
              window.open('customer_report_detail.php', '_blank');
            }
            else {
              //window.location = "post.php";
              createCookie("post_id", data, 1);
              window.open('customer_report_detail.php', '_blank');
            }


          }
        });
      }
      function playSound() {

        $.ajax({
          async: true,
          url: 'ajaxdata.php?action=get_Playnotification',
          type: 'POST',
          data: "",

          success: function (data) {
            // console.log(data);

            var resp = data.split("@@@@");

            if (resp[0] > 0) {

              var mp3Source = '<source src="notif_sound.wav" type="audio/mpeg">';
              document.getElementById("sound").innerHTML = '<audio autoplay="autoplay">' + mp3Source + '</audio>';
              removeplaysound(resp[1]);
            }
          }

        });

      }

      function removeplaysound(ids) {

        $.ajax({
          async: true,
          type: "GET",
          url: "ajaxdata.php?action=removeplaysound",
          data: "id=" + ids,
          async: true,
          cache: false,
          timeout: 50000,

        });

      }
      function mark_read_all() {
        $.ajax({
          async: true,
          type: "GET",
          url: "ajaxdata.php?action=mark_read_all",
          data: "",
          async: true,
          cache: false,
          timeout: 50000,
          success: function (data) {
            $('#notif_header').hide();
            $('#notification_list').html('');
            $('#noti_count').html('');
          }

        });
      }
    </script>

  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="home.php" class="app-brand-link">

              <span class="app-brand-text demo menu-text fw-bolder ms-2">Rowan Decor</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <!-- Dashboard -->
            <li class="menu-item active">
              <a href="home.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>


            <!-- Forms & Tables -->
            <!-- <li class="menu-header small text-uppercase"><span class="menu-header-text">Masters</span></li> -->
            <!-- Forms -->


            <li
              class="menu-item <?php echo in_array(basename($_SERVER["PHP_SELF"]), $adminmenu) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-cog"></i>
                <div data-i18n="Form Elements">Admin Controls</div>
              </a>

              <ul class="menu-sub">

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "branch.php" ? "active" : "" ?>">
                  <a href="branch.php" class="menu-link">
                    <div data-i18n="course">Branch</div>
                  </a>
                </li>

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "architect.php" ? "active" : "" ?>">
                  <a href="architect.php" class="menu-link">
                    <div data-i18n="course">Architect</div>
                  </a>
                </li>

                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "category.php" ? "active" : "" ?>">
                  <a href="category.php" class="menu-link">
                    <div data-i18n="course">Category</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "architect.php" ? "active" : "" ?>">
                  <a href="architect.php" class="menu-link">
                    <div data-i18n="course">Architect</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "page.php" ? "active" : "" ?>">
                  <a href="page.php" class="menu-link">
                    <div data-i18n="course">Pages</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "designation.php" ? "active" : "" ?>">
                  <a href="designation.php" class="menu-link">
                    <div data-i18n="course">Designation</div>
                  </a>
                </li>
                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "assign_module.php" ? "active" : "" ?>">
                  <a href="assign_module.php" class="menu-link">
                    <div data-i18n="course">Assign Module</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "holiday.php" ? "active" : "" ?>">
                  <a href="holiday.php" class="menu-link">
                    <div data-i18n="course">Holiday</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "info.php" ? "active" : "" ?>">
                  <a href="info.php" class="menu-link">
                    <div data-i18n="course">Info</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "config.php" ? "active" : "" ?>">
                  <a href="config.php" class="menu-link">
                    <div data-i18n="course">Configuration</div>
                  </a>
                </li>
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "priority.php" ? "active" : "" ?>">
                  <a href="priority.php" class="menu-link">
                    <div data-i18n="course">Priority</div>
                  </a>
                </li>
                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "mail_settings.php" ? "active" : "" ?>">
                  <a href="mail_settings.php" class="menu-link">
                    <div data-i18n="course">Mail settings</div>
                  </a>
                </li>

                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "privacy_policy.php" ? "active" : "" ?>">
                  <a href="privacy_policy.php" class="menu-link">
                    <div data-i18n="course">Privacy Policy</div>
                  </a>
                </li>

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "terms.php" ? "active" : "" ?>">
                  <a href="terms.php" class="menu-link">
                    <div data-i18n="course">Terms & Conditions</div>
                  </a>
                </li>

              </ul>
            </li>

            <li
              class="menu-item  <?php echo in_array(basename($_SERVER["PHP_SELF"]), $location) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-current-location"></i>
                <div data-i18n="Form Elements">Location Settings</div>
              </a>
              <ul class="menu-sub">


                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "state.php" ? "active" : "" ?>">
                  <a href="state.php" class="menu-link">
                    <div data-i18n="course">State Master</div>
                  </a>
                </li>

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "city.php" ? "active" : "" ?>">
                  <a href="city.php" class="menu-link">
                    <div data-i18n="course">City Master</div>
                  </a>
                </li>

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "zone.php" ? "active" : "" ?>">
                  <a href="zone.php" class="menu-link">
                    <div data-i18n="course">Zone Master</div>
                  </a>
                </li>

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "area.php" ? "active" : "" ?>">
                  <a href="area.php" class="menu-link">
                    <div data-i18n="course">Area Master</div>
                  </a>
                </li>
              </ul>
            </li>

            <li
              class="menu-item  <?php echo in_array(basename($_SERVER["PHP_SELF"]), $delivery) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-package"></i>
                <div data-i18n="Form Elements">Delivery Settings</div>
              </a>
              <ul class="menu-sub">

                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "deliveryboy_reg.php" ? "active" : "" ?>">
                  <a href="deliveryboy_reg.php" class="menu-link">
                    <div data-i18n="course">Delivery Boy Registration</div>
                  </a>
                </li>

                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "delivery_settings.php" ? "active" : "" ?>">
                  <a href="delivery_settings.php" class="menu-link">
                    <div data-i18n="course">Delivery Master</div>
                  </a>
                </li>

                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "collection_time.php" ? "active" : "" ?>">
                  <a href="collection_time.php" class="menu-link">
                    <div data-i18n="course">Collection Time</div>
                  </a>
                </li>
              </ul>
            </li>

            <li
              class="menu-item  <?php echo in_array(basename($_SERVER["PHP_SELF"]), $coupon) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-coupon"></i>
                <div data-i18n="Form Elements">Coupon Settings</div>
              </a>
              <ul class="menu-sub">

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "coupon.php" ? "active" : "" ?>">
                  <a href="coupon.php" class="menu-link">
                    <div data-i18n="course">Coupon Master</div>
                  </a>
                </li>
                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "coupon_counter.php" ? "active" : "" ?>">
                  <a href="coupon_counter.php" class="menu-link">
                    <div data-i18n="course">Coupon Counter</div>
                  </a>
                </li>
              </ul>
            </li>

            <li class="menu-item  <?php echo in_array(basename($_SERVER["PHP_SELF"]), $mail) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-envelope"></i>
                <div data-i18n="Form Elements">Mail Settings</div>
              </a>
              <ul class="menu-sub">

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "mail_type.php" ? "active" : "" ?>">
                  <a href="mail_type.php" class="menu-link">
                    <div data-i18n="course">Mail Type</div>
                  </a>
                </li>
                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "mail_type_tariff.php" ? "active" : "" ?>">
                  <a href="mail_type_tariff.php" class="menu-link">
                    <div data-i18n="course">Mail Type Tariff</div>
                  </a>
                </li>
              </ul>
            </li>


            <li
              class="menu-item  <?php echo in_array(basename($_SERVER["PHP_SELF"]), $review_feedback) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-star"></i>
                <div data-i18n="Form Elements">Review/Feedback</div>
              </a>
              <ul class="menu-sub">
                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "post_review.php" ? "active" : "" ?>">
                  <a href="post_review.php" class="menu-link">
                    <div data-i18n="course">Post Review</div>
                  </a>
                </li>
                <!-- <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "customer_feedback.php" ? "active" : "" ?>">
                      <a href="customer_feedback.php" class="menu-link">
                      <div data-i18n="course">Customer Feedback</div>
                      </a>
                    </li> -->


              </ul>
            </li>


            <li
              class="menu-item  <?php echo in_array(basename($_SERVER["PHP_SELF"]), $reportmenu) ? "active open" : "" ?> ">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bxs-report"></i>
                <div data-i18n="Form Elements">Reports</div>
              </a>
              <ul class="menu-sub">


                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "customer_report.php" ? "active" : "" ?>">
                  <a href="customer_report.php" class="menu-link">
                    <div data-i18n="course">Post Job Report</div>
                  </a>
                </li>

                <li class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "cust_report.php" ? "active" : "" ?>">
                  <a href="cust_report.php" class="menu-link">
                    <div data-i18n="course">Customer Report</div>
                  </a>
                </li>

                <li
                  class="menu-item <?php echo basename($_SERVER["PHP_SELF"]) == "delivery_boy_report.php" ? "active" : "" ?>">
                  <a href="delivery_boy_report.php" class="menu-link">
                    <div data-i18n="course">Delivery Boy Report</div>
                  </a>
                </li>




              </ul>
            </li>


          </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">


              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Place this tag where you want the button to render. -->



                <!-- <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar">
                     <i class="bx bx-bell"></i>
                     <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20" id="noti_count"></span>
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" id="notification_list">
                  </ul>
                </li> -->
                <!-- Notification -->
                <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside">
                    <i class="bx bx-bell bx-sm"></i>
                    <span class="badge bg-danger rounded-pill badge-notifications" id="noti_count"></span>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom" id="notif_header" style="display:none">
                      <div class="dropdown-header d-flex align-items-center py-3">
                        <h5 class="text-body mb-0 me-auto">Notification</h5>
                        <a href="javascript:mark_read_all()" class="dropdown-notifications-all text-body"
                          data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read">Read All</a>
                      </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                      <ul class="list-group list-group-flush" id="notification_list">

                      </ul>
                    </li>

                  </ul>
                </li>
                <!--/ Notification -->

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="assets/img/kapot_100.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                      <a class="dropdown-item" href="editProfile.php">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle"><?php echo ucfirst($_SESSION["username"]) ?></span>
                      </a>
                    </li>


                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="changePassword.php">
                        <i class="bx bx-lock me-2"></i>
                        <span class="align-middle">Change Password</span>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>

                    <li>
                      <a class="dropdown-item" href="logout.php">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!-- / User -->
              </ul>
            </div>
          </nav>
          <div id="sound"></div>
          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">