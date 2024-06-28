<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../include/DbOperation.php';
require '../libs/Slim/Slim.php';
date_default_timezone_set("Asia/Kolkata");
\Slim\Slim::registerAutoloader();

require_once('../../../PHPMailer_v5.1/class.phpmailer.php');

$app = new \Slim\Slim();

/**
 * dboy registration
 * parmas:
 * method:post
 * */

$app->post('/delivery_reg', function () use ($app) {
    //paymentIntent id,status,payment response
    verifyRequiredParams(array('data'));
    $response = array();
    $data = json_decode($app->request->post('data'));
    $name = $data->name;
    $email = $data->email;
    $password = $data->password;
    $city = $data->city;
    $zone_id = 11;
    $address = $data->address;
    $contact = $data->contact;
    $pincode = $data->pincode;
    $id_proof_type = $data->id_proof_type;    
    $tokenid = $data->tokenid;
    $device_type = $data->device_type;
    $MainFileName = "";
    $status="disable";
    $action="added";
    $response["data"]=array();
    $db = new DbOperation();
    if (isset($_FILES["profile_pic"]["name"]) && $_FILES["profile_pic"]["name"] != "") {
        if (file_exists("../../../deliveryboy_id/" . $_FILES["profile_pic"]["name"])) {
            $i = 1;
            $MainFileName = $_FILES["profile_pic"]["name"];
            $Arr = explode('.', $MainFileName);
            $MainFileName = $Arr[0] . $i . "." . $Arr[1];
            while (file_exists("../../../deliveryboy_id/" . $MainFileName)) {
                $i++;
                $MainFileName = $Arr[0] . $i . "." . $Arr[1];
            }

        } else {
            $MainFileName = $_FILES["profile_pic"]["name"];;
        }


    }
    if (isset($_FILES["id_proof"]["name"]) && $_FILES["id_proof"]["name"] != "") {
        if (file_exists("../../../deliveryboy_id/" . $_FILES["id_proof"]["name"])) {
            $i = 1;
            $ProofFileName = $_FILES["id_proof"]["name"];
            $Arr = explode('.', $ProofFileName);
            $ProofFileName = $Arr[0] . $i . "." . $Arr[1];
            while (file_exists("../../../deliveryboy_id/" . $ProofFileName)) {
                $i++;
                $ProofFileName = $Arr[0] . $i . "." . $Arr[1];
            }

        } else {
            $ProofFileName = $_FILES["id_proof"]["name"];;
        }


    }
    $res = $db->delivery_reg($name, $email, $password, $contact, $address,  $city, $pincode, $id_proof_type, $MainFileName, $zone_id, $status, $action,$ProofFileName);

    if ($res > 0) {
        //send notification to admin

        $adminstatus=1;
        $adminplaystatus=1;
        $noti_type="delivery_reg";
        $msg="New delivery boy registered";
        $notification = $db->new_notification($noti_type,  $res,$msg,$adminstatus,$adminplaystatus);

        if ($MainFileName != "") {
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], "../../../deliveryboy_id/" . $MainFileName)) {
                $response["profile_pic_upload"] = "success";
            } else {
                $response["profile_pic_upload"] = "fail";
            }
            if (move_uploaded_file($_FILES["id_proof"]["tmp_name"], "../../../deliveryboy_id/" .  $ProofFileName)) {
                $response["id_proof_upload"] = "success";
            } else {
                $response["id_proof_upload"] = "fail";
            }

        }


        
        
        $dboy = $db->getDelivery_boy($email);
        if ($dboy > 0) {

            $data=array();
            if (strtolower($dboy["email"]) == strtolower($email) && $dboy["password"] == $password && $dboy["status"] == 'enable') {
                $response['value'] = "valid";
                $response['result'] = true;
                $data['db_id'] = $dboy['db_id'];
                $data['name'] = $dboy['name'];
                $response["message"] = "Registered Successfully";
                

                //insert dboy devices
                $insert_device = $db->insert_delivery_boy_device($dboy['db_id'], $tokenid, $device_type);
                array_push($response["data"],$data);
            } else if ($dboy["status"] == 'disable') {
                $response['value'] = "disable";
                $response['result'] = true;
                $response['message'] = "Thanks for your registration, Admin will verify your uploaded documents and enable your ID";

            } 

        }
        
        echoResponse(201, $response);
    }

    else if ($res==-2)
    {
        $response["result"] = false;
        $response["message"] = "Contact number already exist!";
        echoResponse(200, $response);
    }
     else {
        $response["result"] = false;
        $response["message"] = "Email-id already exist!";
        echoResponse(200, $response);
    }
});

/* *
 * dboy login
 * Parameters: username, password,tokenid,type
 * Method: POST
 * */

$app->post('/login', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $email = $data->email;
    $password = $data->password;
    $tokenid = $data->tokenid;
    $type = $data->device_type;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
     
    if ($db->Delivery_boyLogin($email, $password)) {
        $delivery_boy = $db->getDelivery_boy($email);
        if ($delivery_boy > 0) {
            $response = array();
            if (strtolower($delivery_boy["email"]) == strtolower($email) && $delivery_boy["password"] == $password && $delivery_boy["status"] == 'enable') {
                $data['message'] = "";
                $data['result'] = true;
                $response['id'] = $delivery_boy['db_id'];
                $response['name'] = $delivery_boy['name'];
                
                

                //check for delivery boy status
                $del_status=$db->check_delivery_boy($delivery_boy['db_id']);

                if($del_status->num_rows==0)
                {
                    $del_boy_status="on";
                    //insert into delivery boy availability
                    $del_available=$db->delivery_boy_availability($delivery_boy['db_id'],$del_boy_status);
                }
                else
                {
                    $del_boy_data=$del_status->fetch_assoc();
                    $del_boy_status=$del_boy_data["status"];
                    $del_available=$db->delivery_boy_availability($delivery_boy['db_id'],$del_boy_status);
                }

                

                //delete delivery boy devices
               // $del_device = $db->delete_delivery_boy_device($delivery_boy['id']);

                //insert delivery boy devices
                $insert_device = $db->insert_delivery_boy_device($delivery_boy['db_id'], $tokenid, $type);
               
                $data["data"]=$response;

            } else if ($delivery_boy["status"] == 'disable') {
                $data["data"]=null;  // added by jay 16-04-2023
                $data['message'] = "Sorry your ID has been disabled, contact the Admin"; // message updated by Rachna 24/04/2023
                $data['result'] = false;

            } else {
                $data["data"]=null; // added by jay 16-04-2023
                $data['result'] = false;
                $data['message'] = "Invalid email id or password";
            }

        }
    } else {
        $data["data"]=null; // added by jay 16-04-2023
        $data['result'] = false;

        $data['message'] = "Invalid email or password";
    }
    
    echoResponse(200, $data);
});








/* *
 * dboy login OTP
 * Parameters: phno,tokenid,type
 * Method: POST
 * */

$app->post('/login_otp', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
   
    $phno = $data->phno;
    $tokenid = $data->tokenid;
    $type = $data->device_type;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
     
    $delivery_boy = $db->getDelivery_boy_phno($phno);
        
        if ($delivery_boy > 0) {
            $response = array();
            if (strtolower($delivery_boy["status"] == 'enable')) {
                $data['message'] = "";
                $data['result'] = true;
                $response['id'] = $delivery_boy['db_id'];
                $response['name'] = $delivery_boy['name'];
                
                

                //check for delivery boy status
                $del_status=$db->check_delivery_boy($delivery_boy['db_id']);

                if($del_status->num_rows==0)
                {
                    $del_boy_status="on";
                    //insert into delivery boy availability
                    $del_available=$db->delivery_boy_availability($delivery_boy['db_id'],$del_boy_status);
                }
                else
                {
                    $del_boy_data=$del_status->fetch_assoc();
                    $del_boy_status=$del_boy_data["status"];
                    $del_available=$db->delivery_boy_availability($delivery_boy['db_id'],$del_boy_status);
                }

                

                //delete delivery boy devices
               // $del_device = $db->delete_delivery_boy_device($delivery_boy['id']);

                //insert delivery boy devices
                $insert_device = $db->insert_delivery_boy_device($delivery_boy['db_id'], $tokenid, $type);
               
                $data["data"]=$response;

            } else if ($delivery_boy["status"] == 'disable') {
                $data["data"]=null;  // added by jay 16-04-2023
                $data['message'] = "You are disabled, contact admin";
                $data['result'] = false;

            } else {
                $data["data"]=null; // added by jay 16-04-2023
                $data['result'] = false;
                $data['message'] = "Invalid phone number";
            }
            

        
     
    }
    else {
                $data["data"]=null; // added by jay 16-04-2023
                $data['result'] = false;
                $data['message'] = "Invalid phone number";
            }
    
    echoResponse(200, $data);
});




/* *
 * dboy Check pnoneno
 * Parameters: phno
 * Method: POST
 * */

$app->post('/check_phoneno', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
   
    $phno = $data->phno;
  
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
     
    $delivery_boy = $db->getDelivery_boy_phno($phno);
        
        if ($delivery_boy > 0) {
            $response = array();
            if (strtolower($delivery_boy["status"] == 'enable'))
            {
                $data['message'] = "Valid phone number";
                $data['result'] = true;
               

            }
            else
            {
              $data['message'] = "Delivery boy is disabled";
                $data['result'] = false;
            }
     
          }
            else 
            {
                $data["data"]=null; // added by jay 16-04-2023
                $data['result'] = false;
                $data['message'] = "Invalid phone number";
            }
    
    echoResponse(200, $data);
        
});







/*

 * view profile
 * Parameters:userid
 * Method: post
 
*/


$app->post('/view_profile', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $userid = $data->userid;
    $db = new DbOperation();
    $result = $db->view_profile($userid);
    $response = array();
    $response['result'] = true;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = new stdClass();
        
        foreach ($row as $key => $value) {
            $temp->$key = $value;
        }
        $temp->img_url="https://mykapot.com/mykapotroot555/deliveryboy_id/";
        
       
    }
    $response["data"]=$temp;
    echoResponse(200, $response);
});


/*

 * check profile
 * Parameters:userid
 * Method: post
 by jay 22/04
 
*/


$app->post('/check_profile', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $userid = $data->userid;
    $db = new DbOperation();
    $result = $db->check_profile($userid);
    $response = array();
    $response['result'] = true;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = new stdClass();
        
        foreach ($row as $key => $value) {
            $temp->$key = $value;
        }
        $temp->img_url="https://mykapot.com/mykapotroot555/deliveryboy_id/";
        
       
    }
    $response["data"]=$temp;
    echoResponse(200, $response);
});





/*
*name:get job list
*param:deliveryboy_id
method:post
*/
$app->post('/get_job_list', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $deliveryboy_id = $data->deliveryboy_id;


    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $joblist = $db->get_job_list($deliveryboy_id);
    
    if (!empty($joblist)) {
        $data['result'] = true;
        $data['message'] = "";
        
        while ($row = $joblist->fetch_assoc()) {

            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
            $temp = array_map('utf8_encode', $temp);
            array_push($data["data"], $temp);

        }
    } else {
        $data['data']=null;  // added by jay 16-04-2023
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});

/*
*name:get active job list
*param:deliveryboy_id
method:post
*/
$app->post('/get_active_job_list', function () use ($app) {

   $data = json_decode($app->request->post('data'));
    $deliveryboy_id = $data->deliveryboy_id;


    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $joblist = $db->get_active_job_list($deliveryboy_id);

    if (!empty($joblist)) {

        $data['result'] = true;
        $data['message'] = "";
        while ($row = $joblist->fetch_assoc()) {
            
           
            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
            
            $temp = array_map('utf8_encode', $temp);
            array_push($data["data"], $temp);

        }


    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});


/* *
 * Change password (by Jay 23-4-23)
 * Parameters: cur_password,new_password,uid
 * Method: POST
 * 
 */

$app->post('/change_password', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $cur_password = $data->cur_password;
    $new_password = $data->new_password;
    $uid = $data->uid;
    $action='updated';
	
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $affected_rows=$db->change_password($cur_password,$new_password,$uid,$action);
    if($affected_rows==1)
    {
		$data['message'] = "Your Password updated successfully";
        $data['result'] = true;
    }
    else 
    {
        $data['message'] = "Your current password doesnt match, please check!";
        $data['result'] = false;
    }
    echoResponse(200, $data);
});









/*
*name:get post job list
*param:deliveryboy_id
method:post
*/
$app->post('/get_post_job_list', function () use ($app) {

   $data = json_decode($app->request->post('data'));
    $deliveryboy_id = $data->deliveryboy_id;


    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $joblist = $db->get_post_job_list($deliveryboy_id);

    if (!empty($joblist)) {

        $data['result'] = true;
        $data['message'] = "";
        while ($row = $joblist->fetch_assoc()) {
            
           
            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
            
            $temp = array_map('utf8_encode', $temp);
            array_push($data["data"], $temp);

        }


    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});



/*
*name:get job history
*param:deliveryboy_id
method:post
*/
$app->post('/get_job_history', function () use ($app) {

   $data = json_decode($app->request->post('data'));
    $deliveryboy_id = $data->deliveryboy_id;


    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $joblist = $db->get_job_history($deliveryboy_id);

    if (!empty($joblist)) {
        $data['result'] = true;
        $data['message'] = "";
        //print_r($joblist);
        while ($row = $joblist->fetch_assoc()) {

            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
            $temp = array_map('utf8_encode', $temp);
            array_push($data["data"], $temp);

        }
    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});

/*
*name:get city list
*param:
method:post
*/
$app->post('/city_list', function () use ($app) {

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $citylist = $db->get_city_list();
    
    if (!empty($citylist)) {
        $data['result'] = true;
        $data['message'] = "";
        
        while ($row = $citylist->fetch_assoc()) {

            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
            $temp = array_map('utf8_encode', $temp);
            array_push($data["data"], $temp);

        }
    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});


/*
* update delivery boy availability (on/off)
*param:id
method:post
*/
$app->post('/update_delivery_boy_availability', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $reason = $data->reason;
    $status = $data->status;
    $id=$data->db_id;


    $db = new DbOperation();
    $data = array();


    if ($db->update_delivery_boy_availability($id,$reason,$status)) {


        $data['result'] = true;
        $data['message'] = "Status updated successfully.";


    } else {
        $data['result'] = false;
        $data['message'] = "Problem in saving data";
    }

    echoResponse(200, $data);
});


/*
* accept /reject /dispatch/dilivered job
*param:job_id,status
method:post
*/
$app->post('/job_action', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $job_id = $data->job_id;    
    $status = $data->status;

    $payment_status=isset($data->payment_status)?$data->payment_status:"";
    $barcode=isset($data->barcode)?$data->barcode:"";
    $discount=isset($data->discount)?$data->discount:"";
    $coupon_id=isset($data->coupon_id)?$data->coupon_id:"";
    $total_charges=isset($data->total_charges)?$data->total_charges:"";
    $total_payable=isset($data->total_payable)?$data->total_payable:"";
    $weight=isset($data->weight)?$data->weight:"";
    $basic_charges=isset($data->basic_charges)?$data->basic_charges:"";
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $MainFileName="";
    if ($db->job_action($job_id, $status)) {
        
        $order = $db->order_info($job_id);
        // reject other delivery boy's job
        if($status=="accept")
        {   
            $job_status="auto_reject";
            $db->job_update($order["post_id"],$job_status,$job_id);
            //update post status
            $order_status="accept";
            $db->update_order_status($order["post_id"],$order_status,$payment_status);

            //send notification to user
            $not = new stdClass();
            $reg_ids_android = array();
            $reg_ids_ios = array();
            $inc=0;
            $inc2=0;
            $send_to="user";
            $not->o_id = $order["post_id"];
            $not->id = $order["post_id"];
            $not->message = "Post ID : ".$order["post_id"]." has been accepted on ".date("d-m-Y h:i A", strtotime($order["date_time"]));
            $not->title =  "#".$order["post_id"];
            $not->order_id =  $order["post_id"];
            $not->body =  "Post ID : ".$order["post_id"]." has been accepted on ".date("d-m-Y h:i A", strtotime($order["date_time"]));

            $title ="#".$order["post_id"];
            $body =  "Post ID : ".$order["post_id"]." has been accepted on ".date("d-m-Y h:i A", strtotime($order["date_time"]));

        
            // notification to android devices
            $res_token = $db->fetch_user_android($order["post_id"]);
            while ($token = mysqli_fetch_array($res_token)) {
                $reg_ids_android[$inc++] = $token["device_token"];
            }
            $resp=send_notification_android($not, $reg_ids_android, $title, $body,$send_to);

            // notification to ios devices
            $res_token = $db->fetch_user_ios($order["post_id"]);
            while ($token_ios = mysqli_fetch_array($res_token)) {
                $reg_ids_ios[$inc2++] = $token_ios["token"];
            }
           // $resp=send_notification_ios($not, $reg_ids_ios, $title, $body,$send_to);

        }
        else if (strtolower($status) == "transit") 
        {

            //$order = $db->order_info($job_id);
            $order_status="transit";
            $payment_status='paid';
            if ($update_order = $db->update_order_status($order["post_id"],$order_status,$payment_status)) {
                

                //notification to admin/vendor panel
                $noti_type='post_in_transit';
                $msg='Your post is in transit';
                $sender_type='delivery';
                $vendorstatus=1;
                $playstatus=1;
                $adminstatus=1;
                $adminplaystatus=1;

                $data["order_status"] = "updated";

                // update coupon counter if discount is 0
                if($discount==0 && $order["coupon_id"]!=0)
                {
                    $res_coupon_counter=$db->update_counter($order["sender_id"],$order["coupon_id"]);
                }
                
                // update post grams & charges
                $jobdata = $db->update_weight($order["post_id"],$weight,$basic_charges,$total_charges,$total_payable,$discount,$coupon_id);              
                
                
                // add barcode in delivery images
                $del_image=$db->add_delivery_image($job_id,$barcode,"barcode");

                if (isset($_FILES["image"]["name"]) && $_FILES["image"]["name"] != "")
                {
                    if (file_exists("../../../post_images/" . $_FILES["image"]["name"] )) {
                        $i = 1;
                        $MainFileName = $_FILES["image"]["name"];
                        $Arr = explode('.', $MainFileName);
                        $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        while (file_exists("../../../post_images/" . $MainFileName)) {
                            $i++;
                            $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        }

                    } else {
                        $MainFileName = $_FILES["image"]["name"];;
                    }
                           
                        $del_image=$db->add_delivery_image($job_id,$MainFileName,$status);
                }
                
                if($MainFileName!=""){
                    if(move_uploaded_file($_FILES["image"]["tmp_name"], "../../../post_images/" . $MainFileName))
                    {
                              $response["image_upload"] = "success";
                    }
                    else
                    {
                              $response["image_upload"] = "fail";
                    }
                    
                }

                
            } 
            else 
            {
                $data["order_status"] = "fail";
            }


            // send notification to user
            $not = new stdClass();
            $reg_ids_android = array();
            $reg_ids_ios = array();
            $inc=0;
            $inc2=0;
            $send_to="user";
            $not->o_id = $order["post_id"];
            $not->id = $order["post_id"];
            $not->message = "Post ID : ".$order["post_id"]." has been picked up on ".date("d-m-Y h:i A", strtotime($order["date_time"]));
            $not->title =  "#".$order["post_id"];
            $not->order_id =  $order["post_id"];
            $not->body =  "Post ID : ".$order["post_id"]." has been picked up on ".date("d-m-Y h:i A", strtotime($order["date_time"]));

            $title ="#".$order["post_id"];
            $body =  "Post ID : ".$order["post_id"]." has been picked up on ".date("d-m-Y h:i A", strtotime($order["date_time"]));

        
            // notification to android devices
            $res_token = $db->fetch_user_android($order["post_id"]);
            while ($token = mysqli_fetch_array($res_token)) {
                $reg_ids_android[$inc++] = $token["device_token"];
            }
            $resp=send_notification_android($not, $reg_ids_android, $title, $body,$send_to);

            // notification to ios devices
            $res_token = $db->fetch_user_ios($order["post_id"]);
            while ($token_ios = mysqli_fetch_array($res_token)) {
                $reg_ids_ios[$inc2++] = $token_ios["token"];
            }
           // $resp=send_notification_ios($not, $reg_ids_ios, $title, $body,$send_to);
            /*if($resp)
            {
                $response["message"] = "Notification has been sent";
            }*/
        }
        else if (strtolower($status) == "dispatched") 
        {

            //$order = $db->order_info($job_id);
            $order_status="dispatched";
            $img_status="dispatch";
            if ($update_order = $db->update_order_status($order["post_id"],$order_status,$payment_status)) {
                $data["order_status"] = "updated";

                //notification to admin/vendor panel
                $noti_type='post_dispatched';
                $msg='Your post has been dispatched';
                $sender_type='delivery';
                $vendorstatus=1;
                $playstatus=1;
                $adminstatus=1;
                $adminplaystatus=1;
              

                if (isset($_FILES["image"]["name"]) && $_FILES["image"]["name"] != "")
                {
                    if (file_exists("../../../post_images/" . $_FILES["image"]["name"] )) {
                        $i = 1;
                        $MainFileName = $_FILES["image"]["name"];
                        $Arr = explode('.', $MainFileName);
                        $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        while (file_exists("../../../post_images/" . $MainFileName)) {
                            $i++;
                            $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        }

                    } else {
                        $MainFileName = $_FILES["image"]["name"];;
                    }
                           
                        $del_image=$db->add_delivery_image($job_id,$MainFileName,$img_status);
                }
                
                if($MainFileName!=""){
                    if(move_uploaded_file($_FILES["image"]["tmp_name"], "../../../post_images/" . $MainFileName))
                    {
                              $response["image_upload"] = "success";
                    }
                    else
                    {
                              $response["image_upload"] = "fail";
                    }
                    
                }
            } else {
                $data["order_status"] = "fail";
            }


            // send notification to user
            $not = new stdClass();
            $reg_ids_android = array();
            $reg_ids_ios = array();
            $inc=0;
            $inc2=0;
            $send_to="user";
            $not->o_id = $order["post_id"];
            $not->id = $order["post_id"];
            $not->message = "Post ID : ".$order["post_id"]." has been dispatched on ".date("d-m-Y h:i A", strtotime($order["date_time"]));
            $not->title =  "#".$order["post_id"];
            $not->order_id =  $order["post_id"];
            $not->body =  "Post ID : ".$order["post_id"]." has been dispatched on ".date("d-m-Y h:i A", strtotime($order["date_time"]));
           
            $title ="#".$order["post_id"];
            $body =  "Post ID : ".$order["post_id"]." has been dispatched on ".date("d-m-Y h:i A", strtotime($order["date_time"]));

        
            // notification to android devices
            $res_token = $db->fetch_user_android($order["post_id"]);
            while ($token = mysqli_fetch_array($res_token)) {
                $reg_ids_android[$inc++] = $token["device_token"];
            }
            $resp=send_notification_android($not, $reg_ids_android, $title, $body,$send_to);

            // notification to ios devices
            $res_token = $db->fetch_user_ios($order["post_id"]);
            while ($token_ios = mysqli_fetch_array($res_token)) {
                $reg_ids_ios[$inc2++] = $token_ios["token"];
            }
           // $resp=send_notification_ios($not, $reg_ids_ios, $title, $body,$send_to);
            /*if($resp)
            {
                $response["message"] = "Notification has been sent";
            }*/
        }

        $data['result'] = true;
        $data['message'] = "Job " . $status;          
    
            if(strtolower($status) == "accept" )
            {
                //notification to admin/vendor panel
                $noti_type='post_accepted';
                $msg='Delivery job has been accepted ';
                          
            }
            if(strtolower($status) == "reject" )
            {
                //notification to admin/vendor panel
                $noti_type='post_rejected';
                $msg='Delivery job has been rejected ';    
            }
            if(strtolower($status) == "dispatched" )
            {
                //notification to admin/vendor panel
                $noti_type='post_dispatched';
                $msg='Delivery job has been dispatched ';    
            }
            
            if(strtolower($status)=="transit")
            {
                $noti_type='post_in_transit';
                $msg='Your post is in transit';
            }
          
            
            $adminstatus=1;
            $adminplaystatus=1;
            
             //if else conditin  added by Jay 21-04-23 
            if(strtolower($status) == "reject")
            {
                $notification = $db->new_notification($noti_type,$job_id,$msg,$adminstatus,$adminplaystatus);
            }
            else
            {
                   $notification = $db->new_notification($noti_type,$order["post_id"],$msg,$adminstatus,$adminplaystatus);
            }
            
           // $notification = $db->new_notification($noti_type,  $order["post_id"],$msg,$adminstatus,$adminplaystatus);

            if($notification)
            {
                $response["message"] = "Notification has been sent";
            }
        


    } else {
        $data['result'] = false;
        $data['message'] = "This job has been already delegated!";
    }

    echoResponse(200, $data);
});


/*
*name:get total jobs
*param:deliveryboy_id,start_date,end_date
method:post
*/
$app->post('/get_total_job', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $deliveryboy_id = $data->deliveryboy_id;
    $start_date=$data->start_date;
    $end_date=$data->end_date;

    $db = new DbOperation();
    $data = array();
    //$data["data"] = array();
   // $data["data"]["list"] = array(); //added
    $response = array();
    $data_main=new stdClass();
    $list=array();
    $list["list"]= array();
    $joblist = $db->get_total_job($deliveryboy_id,$start_date,$end_date);
    $total_job=$joblist->num_rows;
    if (!empty($joblist)) {
        $data['result'] = true;
        $data['message'] = "";
        $total_payment=0;
        
    //    $data["total_job"]=$total_job;
        while ($row = $joblist->fetch_assoc()) {
            $temp=array();
            $total_payment+=$row["total_payment"];
            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
           $temp = array_map('utf8_encode', $temp);
        array_push($list["list"], $temp);

        }
         
         //    $data["total_payment"]=$total_payment;
         

         $list["total_payment"]=$total_payment;
         $list["total_job"]=$total_job;
         // $temp2 = array_map('utf8_encode', $temp2);
         
       
        //   array_push($data["data"]["list"], $temp2);
             
         $data["data"]=$list;
       
    } else {
        $data['data']=null;  // added by jay 16-04-2023
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});



/*
* accept /reject /dispatch/dilivered job
*param:job_id,status
method:post
*/
$app->post('/job_action_jay', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $job_id = $data->job_id;    
    $status = $data->status;
    //$weight=isset($data->weight)?$data->weight:"";
    $payment_status=isset($data->payment_status)?$data->payment_status:"";
    $barcode=isset($data->barcode)?$data->barcode:"";
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $MainFileName="";
    if ($db->job_action($job_id, $status)) {
        
        $order = $db->order_info($job_id);
        
        
       if($status=="reject") // updated by jay 16-04
       {
     
            $db->job_assign_update($job_id,$status);
           
       }
       else if($status=="accept")
        {   
            
            $job_status="auto_reject";
            $db->job_update($order["post_id"],$job_status,$job_id);
            //update post status
            $db->update_order_status($order["post_id"],$status,$payment_status);

        }
       

        else if (strtolower($status) == "dispatched") 
        {
           

             $db->job_assign_update($job_id,$status);
          
           
            if ($update_order =  $db->update_order_status($order["post_id"],$status,$payment_status)) 
            {   // modified by jay 16-04
                $data["order_status"] = "updated";

                //notification to admin/vendor panel
                $noti_type='post_dispatched';
                $msg='Your post has been dispatched';
                $sender_type='delivery';
                $vendorstatus=1;
                $playstatus=1;
                $adminstatus=1;
                $adminplaystatus=1;
               // $notification = $db->new_notification($order["v_id"], $order["customer_id"], $order["post_id"],$noti_type,$msg,$sender_type,$vendorstatus,$playstatus,$adminstatus,$adminplaystatus);

                if (isset($_FILES["image"]["name"]) && $_FILES["image"]["name"] != "")
                {
                    if (file_exists("../../../post_images/" . $_FILES["image"]["name"] )) { // removed //assests by jay 16/04
                        $i = 1;
                        $MainFileName = $_FILES["image"]["name"];
                        $Arr = explode('.', $MainFileName);
                        $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        while (file_exists("../../../post_images/" . $MainFileName)) { // removed //assests by jay 16/04
                            $i++;
                            $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        }

                    } else {
                        $MainFileName = $_FILES["image"]["name"];;
                    }
                           echo $MainFileName.$status.$job_id;
                         $del_image=$db->add_delivery_image($job_id,$MainFileName,$status);
                }
                
                if($MainFileName!=""){
                    if(move_uploaded_file($_FILES["image"]["tmp_name"], "../../../post_images/" . $MainFileName)) // removed //assests by jay 16/04
                    {
                              $response["image_upload"] = "success";
                    }
                    else
                    {
                              $response["image_upload"] = "fail";
                    }
                    
                }
            } else {
                $data["order_status"] = "fail";
            }


            // send notification to user
            $not = new stdClass();
            $reg_ids_android = array();
            $reg_ids_ios = array();
            $inc=0;
            $inc2=0;
            $send_to="user";
            $not->o_id = $order["post_id"];
            $not->id = $order["post_id"];
            $not->message = "Post has been ".$order_status;
            $not->title =  "#".$order["post_id"];
            $not->order_id =  $order["post_id"];
            $not->body =  "Post has been ".$order_status;
           
            $title ="#".$order["post_id"];
            $body =  "Post has been ".$order_status;

            
            // notification to android devices
            $res_token = $db->fetch_user_android($order["post_id"]);
            while ($token = mysqli_fetch_array($res_token)) {
                $reg_ids_android[$inc++] = $token["device_token"];
            }
          //  $resp=send_notification_android($not, $reg_ids_android, $title, $body,$send_to);

            // notification to ios devices
            $res_token = $db->fetch_user_ios($order["post_id"]);
            while ($token_ios = mysqli_fetch_array($res_token)) {
                $reg_ids_ios[$inc2++] = $token_ios["token"];
            }
           // $resp=send_notification_ios($not, $reg_ids_ios, $title, $body,$send_to);
            /*if($resp)
            {
                $response["message"] = "Notification has been sent";
            }*/
        }
        if (strtolower($status) == "transit") 
        {

             $db->job_assign_update($job_id,$status); // changed by Jay 16-04-23
            $payment_status="Paid"; // added by jay 16-04-23
            
            if ($update_order = $db->update_order_status($order["post_id"],$status,$payment_status)) {
                $data["order_status"] = "updated";

                //notification to admin/vendor panel
                $noti_type='post_in_transit';
                $msg='Your post is in transit';
                $sender_type='delivery';
                $vendorstatus=1;
                $playstatus=1;
                $adminstatus=1;
                $adminplaystatus=1;
               
                //update post weight & total payment
                // add barcode in delivery images
                $barcode='static123'; // added to test and its working , need to change later
                $del_image=$db->add_delivery_image($job_id,$barcode,"barcode");

                if (isset($_FILES["image"]["name"]) && $_FILES["image"]["name"] != "")
                {
                    if (file_exists("../../../post_images/" . $_FILES["image"]["name"] )) {  // removed //assests by jay 16/04
                        $i = 1;
                        $MainFileName = $_FILES["image"]["name"];
                        $Arr = explode('.', $MainFileName);
                        $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        while (file_exists("../../../post_images/" . $MainFileName)) { // removed //assests by jay 16/04
                            $i++;
                            $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                        }

                    } else {
                        $MainFileName = $_FILES["image"]["name"];;
                    }
                           
                        $del_image=$db->add_delivery_image($job_id,$MainFileName,$status);
                }
                
                if($MainFileName!=""){
                    if(move_uploaded_file($_FILES["image"]["tmp_name"], "../../../post_images/" . $MainFileName)) // removed //assests by jay 16/04
                    {
                              $response["image_upload"] = "success";
                    }
                    else
                    {
                              $response["image_upload"] = "fail";
                    }
                    
                }   // added by jay 16-04 - kept this call as it is
            } else {
                $data["order_status"] = "fail";
            }


            // send notification to user
            $not = new stdClass();
            $reg_ids_android = array();
            $reg_ids_ios = array();
            $inc=0;
            $inc2=0;
            $send_to="user";
            $not->o_id = $order["post_id"];
            $not->id = $order["post_id"];
            $not->message = "Post has been ".$order_status;
            $not->title =  "#".$order["post_id"];
            $not->order_id =  $order["post_id"];
            $not->body =  "Post has been ".$order_status;
           
            $title ="#".$order["post_id"];
            $body =  "Post has been ".$order_status;

            
            // notification to android devices
            $res_token = $db->fetch_user_android($order["post_id"]);
            while ($token = mysqli_fetch_array($res_token)) {
                $reg_ids_android[$inc++] = $token["device_token"];
            }
          //  $resp=send_notification_android($not, $reg_ids_android, $title, $body,$send_to);

            // notification to ios devices
            $res_token = $db->fetch_user_ios($order["post_id"]);
            while ($token_ios = mysqli_fetch_array($res_token)) {
                $reg_ids_ios[$inc2++] = $token_ios["token"];
            }
           // $resp=send_notification_ios($not, $reg_ids_ios, $title, $body,$send_to);
            /*if($resp)
            {
                $response["message"] = "Notification has been sent";
            }*/

        }



        $data['result'] = true;
        $data['message'] = "Job " . $status;


    
            if(strtolower($status) == "accept" )
            {
                //notification to admin/vendor panel
                $noti_type='post_accepted';
                $msg='Delivery job has been accepted ';
                          
            }
            if(strtolower($status) == "reject" )
            {
                //notification to admin/vendor panel
                $noti_type='post_rejected';
                $msg='Delivery job has been rejected ';    
            }
            if(strtolower($status) == "dispatched" )
            {
                //notification to admin/vendor panel
                $noti_type='post_dispatched';
                $msg='Delivery job has been dispatched ';    
            }
            if(strtolower($status) == "transit" )
            {
                //notification to admin/vendor panel
                $noti_type='post_in_transit';
                $msg='Delivery job is in transit ';    
            }
            
          
            
            $adminstatus=1;
            $adminplaystatus=1;
            //if else conditin  added by Jay 21-04-23 
            if(!strtolower($status) == "reject")
            {
                $notification = $db->new_notification($noti_type,  $order["post_id"],$msg,$adminstatus,$adminplaystatus);
            }
            else
            {
                   $notification = $db->new_notification($noti_type,$job_id,$msg,$adminstatus,$adminplaystatus);
            }
            
            if($notification)
            {
                $response["message"] = "Notification has been sent";
            }
        


    } else {
        $data['result'] = false;
        $data['message'] = "This job has been already delegated!";
    }

    echoResponse(200, $data);
});



/*
*name:get job detail
*param:job_id
method:post
*/
$app->post('/get_job_detail', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $job_id = $data->job_id;
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $jobdata = $db->get_job_detail($job_id);
    
    if (!empty($jobdata)) {
        $data['result'] = true;
        $data['message'] = "";
        $temp= new stdClass();
        while ($row = $jobdata->fetch_assoc()) {

            foreach ($row as $key => $value) {
                $temp->$key= strval($value);
            }
            
        }
        $data["data"]=$temp;
    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});




/*
*name:get post job detail
*param:job_id
method:post
*/
$app->post('/get_post_job_detail', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $job_id = $data->job_id;
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $jobdata = $db->get_post_job_detail($job_id);
    
    if (!empty($jobdata)) {
        $data['result'] = true;
        $data['message'] = "";
        $temp=new stdClass();
        while ($row = $jobdata->fetch_assoc()) {

            foreach ($row as $key => $value) {
                $temp->$key = strval($value);
            }
            

        }
        $data["data"]=$temp;
    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});


/*
*name:update weight
*param:deliveryboy_id
method:post
*/
$app->post('/update_weight_old', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $post_id = $data->post_id;
    $weight=isset($data->weight)?$data->weight:"";

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();
    $temp=array();
    
    // get details from post
    $post_data=$db->post_data($post_id);
    $ack_charges=$post_data["ack_charges"];
    $delivery_charge=$post_data["delivery_charge"];
    //$discount=($post_data["discount"]!="")?$post_data["discount"]:0;
    $coupon_id=$post_data["coupon_id"];
    $mail_type=$post_data["mail_type"];
    $cust_id=$post_data["sender_id"];
     // calculate charges
    $get_amount=$db->get_amount($weight,$mail_type);

   // check if discount is available or not 
    $get_discount=$db->get_discount($coupon_id,$get_amount,$delivery_charge);
    if($get_discount==1)
    {
        
         $discount=0;
    }
    else if ($get_discount==2)
    {
        
         $discount=0;
    }
    else
    {

     $discount=$get_discount;
    }

    // update coupon counter if discount is 0
    if($discount==0)
    {
        $res_coupon_counter=$db->update_counter($cust_id,$coupon_id);
        $coupon_id=0;
    }
    

   if($get_amount>0)
   {
        $basic_charges=$get_amount;
        $total_charges=$delivery_charge+$ack_charges+$basic_charges;
        $total_payment=($delivery_charge+$ack_charges+$basic_charges)-$discount;
        // update post grams & charges
        $jobdata = $db->update_weight($post_id,$weight,$basic_charges,$total_charges,$total_payment,$discount,$coupon_id);
        
        if ($jobdata>0) {
            $data['result'] = true;
            $data['message'] = "";
            $temp['total_charges']=$total_payment;
            $data['data']=$temp;
            
        } else {
            
            $data['result'] = true;
            $temp['total_charges']=$total_payment;
            $data['data']=$temp;
            $data['message'] = "Weight already updated";
        }
   }
   else
   {
        $data["data"]=null; 
        $data['result'] = false;
        $data['message'] = "Weight not available";
   }
    

    echoResponse(200, $data);
});

/*
*name:update weight
*param:deliveryboy_id
method:post
*/
$app->post('/update_weight', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $post_id = $data->post_id;
    $weight=isset($data->weight)?$data->weight:"";
    $save=isset($data->save)?$data->save:"";
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();
    $temp=array();
    


    // get details from post
    $post_data=$db->post_data($post_id);
    $ack_charges=$post_data["ack_charges"];
    $delivery_charge=$post_data["delivery_charge"];
    //$discount=($post_data["discount"]!="")?$post_data["discount"]:0;
    $coupon_id=$post_data["coupon_id"];
    $mail_type=$post_data["mail_type"];
    $cust_id=$post_data["sender_id"];
    $jobdata="";
     // calculate charges
    $get_amount=$db->get_amount($weight,$mail_type);
    $basic_charges=$get_amount;
    $total_charges=$delivery_charge+$ack_charges+$basic_charges;

   // check if discount is available or not 
    $get_discount=$db->get_discount($coupon_id,$total_charges,$delivery_charge);
    if($get_discount==1)
    {
        
         $discount=0;
    }
    else if ($get_discount==2)
    {
        
         $discount=0;
    }
    else
    {

     $discount=$get_discount;
    }

    // update coupon counter if discount is 0
    if($discount==0)
    {
        //$res_coupon_counter=$db->update_counter($cust_id,$coupon_id);
        $coupon_id=0;
    }
    

   if($get_amount>0)
   {
        
        $total_payment=($delivery_charge+$ack_charges+$basic_charges)-$discount;
       
        
        if ($jobdata>0) {
            $data['result'] = true;
            $data['message'] = "";
            $temp['total_charges']=$total_charges;
            $temp['discount']=$discount;
            $temp['coupon_id']=$coupon_id;
            $temp['total_payable']=$total_payment;
            $temp['weight']=$weight;
            $temp['basic_charges']=$basic_charges;
            $data['data']=$temp;

            
        } else {
            
            $data['result'] = true;
            $temp['total_charges']=$total_charges;
            $temp['discount']=$discount;
            $temp['coupon_id']=$coupon_id;
            $temp['total_payable']=$total_payment;
            $temp['weight']=$weight;
            $temp['basic_charges']=$basic_charges;
            $data['data']=$temp;
            $data['message'] = "Weight already updated";
        }
   }
   else
   {
        $data["data"]=null; 
        $data['result'] = false;
        $data['message'] = "Weight not available";
   }
    

    echoResponse(200, $data);
});



/*

 * logout
 * Parameters:id,tokenid
 * Method: post
*/
$app->post('/logout', function () use ($app) {
    verifyRequiredParams(array('data'));

    $response = array();
    $data = json_decode($app->request->post('data'));
    $id = $data->db_id;
    $tokenid = $data->tokenid;

    $db = new DbOperation();
    $res = $db->logout($id, $tokenid);

    if ($res == 1) {


        $response['result'] = false;
        $response['value'] = "valid";
        $response['message'] = "Logged out";
        echoResponse(201, $response);
    } else {

        $response['result'] = true;
        $response['error_code'] = 1;
        $response['value'] = "invalid";
        $response['message'] = "Please try again";
        echoResponse(201, $response);
    }

});


/* get privacy policy
method:get
parmas:
  changed by jay 22-04-23 , converted from array to object
*/
$app->get('/get_privacy', function () use ($app) {
   
    $db = new DbOperation();
    $result = $db->get_privacy();
   // $response = array();
    
    $response['result'] = false;
   $data = json_decode($app->request->post('data'));
    while ($row = $result->fetch_assoc()) {
       // $temp = array();
        $temp= new stdClass();
        foreach ($row as $key => $value) {
            $temp->$key = $value;
        }
       // $temp = array_map('utf8_encode', $temp);
        $data["data"]= $temp;
          $data["result"] = true;
        
        
       
    }
    echoResponse(200, $data);
});



/*
 *  terms and conditions
 * Parameters:
 * Method: get
 changed by jay 22/04/23
*/


$app->get('/get_terms', function () use ($app) {
    // get_faq
    $db = new DbOperation();
    $result = $db->get_terms();
    $response = array();
 //   $response['result'] = false;
 //   $response['data'] = array();
 $data = json_decode($app->request->post('data'));
 
    while ($row = $result->fetch_assoc()) {
        $temp= new stdClass();
        foreach ($row as $key => $value) {
            $temp->$key = $value;
        }
    //    $temp = array_map('utf8_encode', $temp);
     //   array_push($response['data'], $temp);
       $data["data"]= $temp;
       $data["result"] = true;
    }
    echoResponse(200, $data);
});


/*
*name:add_location
*param:db_id,lat,lng
method:post
*/
$app->post('/add_location', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $db_id = $data->db_id;
    $lat = $data->lat;
    $long = $data->lng;
    


    $db = new DbOperation();
    $data = array();
    
    // check if delivery location is in databse
    $check_location=$db->check_delivery_location($db_id);
    if($check_location->num_rows>0)
    {
        $db_location=$check_location->fetch_assoc();
        //update location if already exsits
         

        $add_location = $db->update_location($db_location["id"],$lat,$long);
    }
    else
    {
         // add new location

        $add_location = $db->add_location($db_id,$lat,$long);
    }
  
    
   if ($add_location == 0) {
            $data['result'] = true;
            
            $data['message'] = "Added successfully";
            
        } else {
            $data['result'] = true;
            $data["message"] = "Added successfully";
           
        }

    echoResponse(200, $data);
});

/*
*name:forget_password
param:email
method:post


*/
$app->post('/forget_password', function () use ($app) {
    // Forget Password
     verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $email = $data->email;
    $response = array();
    $db = new DbOperation();
    $user = $db->get_dboy($email);
    if ($user > 0) {

        $uname = $user['name'] ;
        $password = $user['password'];
        $phonenumber = $user['contact'];


        $forgot_pass_msg = "<html><p>Dear " . str_replace("*", "'", $uname) . ",</p></br>
<div>This e-mail is in response to your recent request to recover your forgotten password.<br>
Your  password is:" . $password . "</div><br><div>Regards,<br>MyKapot Delivery</div></html>";
        
        smtpmailer($email, "MyKapot Delivery", "Forgot Password", $forgot_pass_msg);
        $response["result"] = true;
        $response["message"] = "Mail sent.";
        echoResponse(201, $response);

    } else {

        $response["result"] = false;
        $response["message"] = "Please enter valid email";
        echoResponse(201, $response);
    }
});

function echoResponse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response);
}


function verifyRequiredParams($required_fields)
{
    $error = false;
    $error_fields = "";
    $request_params = $_REQUEST;

    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["result"] = true;
        $response["error_code"] = 99;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

function smtpmailer($to, $from_name, $subject, $body)
{
  $from = 'test@pragmanxt.com';
  global $error;
  $mail = new PHPMailer();
  $mail->IsSMTP();
  $mail->SMTPAuth = true; 
 

       // $mail->SMTPSecure = 'ssl'; 
  $mail->SMTPKeepAlive = true;
  $mail->Mailer = "smtp";
  $mail->Host = 'mail.pragmanxt.com';
  $mail->Port = 465;  
  $mail->SMTPSecure = 'ssl';   
  $mail->Username = 'test@pragmanxt.com';  
  $mail->Password = 'L{H=JC9iIRW6';   
    

  $mail->IsHTML(true);    
  $mail->SMTPDebug = 1; 
  $mail->From=$from;
  $mail->FromName=$from_name;
  $mail->Sender=$from; // indicates ReturnPath header
  $mail->AddReplyTo($from, $from_name); // indicates ReplyTo headers

  $mail->Subject = $subject;
  $mail->Body = $body;
  $mail->AddAddress($to);
  if(!$mail->Send())
  {     
      $error = 'Mail error: '.$mail->ErrorInfo;
  
      return $error;

  }
  else
  {
      $error = 'Message sent!';

      return 1;
     
  }

}

// fcm notificaton for android
function send_notification_android($data, $reg_ids_android, $title, $body,$send_to)
{

    $url = 'https://fcm.googleapis.com/fcm/send';
    if($send_to=="user")
    {
        $api_key = 'AAAA2n2PB4A:APA91bEb_4LGpFCH3xTmzG763VWpuV02DGrMmunv1e-bza06vBLdIZgcHaqYu_f7P8a-druZ7buh6b1-OzcLGCP1Yc0bywdVb93dlKQ-BmOgZCVSD135Itw9UKSuNy6rWGqyWr7Q9eLX';
    }
    else
    {
        $api_key = 'AAAAdlMKxlc:APA91bGTsTmJMO1EAicBqWTRdZEQOpxJmn_4VRtd7GrVEaJMrZCO-XGKTfzQdk5DGFfmE6ZAbyvRNLbN7Iao13qaSRgv6ia6KdLziszNSj4-oiuc9p-K1IXPJ9Unxdj0FEpVFkpJ0g2n';
    }
    
    $msg = array(
        'title' => $title,
        'body' => $body,
        'icon' => 'myicon',
        'sound' => 'custom_notification.mp3',
        'data' => $data
    );

    $fields = array(
        'registration_ids' => $reg_ids_android,
        'data' => $data,

    );
//print_r($fields);
    $headers = array(
        'Content-Type:application/json',
        'Authorization:key=' . $api_key
    );

    // echo json_encode($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    if ($result === FALSE) {
        //die('FCM Send Error: ' . curl_error($ch));
        $resp=0;
    }
    else{
        $resp=$result;
    }
    curl_close($ch);

    //  echo $result;
    return $resp;
}

// fcm notification code
function send_notification_ios($data, $reg_ids, $title, $body,$send_to)
{
    //$reg_ids[0]="esR5GsVCeEBljF0hszij-k:APA91bEq7A2QCl6Rrt8-__t7OlUemcQOIy_KRe0Zm6h50b8ffZcciHDdnT8f9poGAiW6gcqywi438TWt_aOLN0yk7YKgbOakkvrmTlvVUEtr98aiz69BsgoACxfHXztRmFx-0HprNxLy";
    $url = 'https://fcm.googleapis.com/fcm/send';
    if($send_to=="user")
    {
        $api_key = 'AAAAdlMKxlc:APA91bGTsTmJMO1EAicBqWTRdZEQOpxJmn_4VRtd7GrVEaJMrZCO-XGKTfzQdk5DGFfmE6ZAbyvRNLbN7Iao13qaSRgv6ia6KdLziszNSj4-oiuc9p-K1IXPJ9Unxdj0FEpVFkpJ0g2n';
    }
    else
    {
        $api_key = 'AAAAdlMKxlc:APA91bGTsTmJMO1EAicBqWTRdZEQOpxJmn_4VRtd7GrVEaJMrZCO-XGKTfzQdk5DGFfmE6ZAbyvRNLbN7Iao13qaSRgv6ia6KdLziszNSj4-oiuc9p-K1IXPJ9Unxdj0FEpVFkpJ0g2n';
    }
    $msg = array(
        'title' => $title,
        'body' => $body,
        'icon' => 'myicon',
        'sound' => 'custom_notification.mp3',
        'data' => $data
    );
    $fields = array(
        'registration_ids' => $reg_ids,
        'notification' => $msg
    );
//print_r($fields);
    $headers = array(
        'Content-Type:application/json',
        'Authorization:key=' . $api_key
    );

    // echo json_encode($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    if ($result === FALSE) {
    //    die('FCM Send Error: ' . curl_error($ch));
    }
    curl_close($ch);

    //  echo $result;
    return $result;
}

$app->run();
