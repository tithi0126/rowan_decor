<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../include/DbOperation.php';
require '../libs/Slim/Slim.php';
date_default_timezone_set("Asia/Kolkata");
\Slim\Slim::registerAutoloader();

//require_once('../../../PHPMailer_v5.1/class.phpmailer.php');

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
            } else if ($dboy["stats"] == 'disable') {
                $response['value'] = "disable";
                $response['result'] = false;
                $response['message'] = "Sorry your ID has been disabled, contact the Admin";

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
                $data['message'] = "You are disabled, contact admin";
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
    $response['result'] = false;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp["img_url"]="https://mykapot.com/mykapotroot555/deliveryboy_id/";
        
        $temp = array_map('utf8_encode', $temp);
        array_push($response['data'], $temp);
    }
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
* check delivery boy status (enable/disable)
*param:id
method:post
*/
$app->post('/check_delivery_boy_status', function () use ($app) {

    verifyRequiredParams(array('id'));
    $id = $app->request->post('id');


    $db = new DbOperation();
    $data = array();


    if ($db->check_delivery_boy_status($id)) {


        $data['result'] = true;
        $data['message'] = "You are enabled";


    } else {
        $data['result'] = false;
        $data['message'] = "You are disabled";
    }
    $resp=$db->check_delivery_boy($id);
    if($resp->num_rows>0)
    {
        $del_boy_data=$resp->fetch_assoc();
        $data['staus']=$del_boy_data["status"];
    }
    else
    {
        $data['staus']="Off";
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
    //$weight=isset($data->weight)?$data->weight:"";
    $payment_status=isset($data->payment_status)?$data->payment_status:"";
    $barcode=isset($data->barcode)?$data->barcode:"";
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $MainFileName="";
    if ($db->job_action($job_id, $status)) {
        
        $order = $db->order_info($job_id);
        // reject other delivery boy's job
        
       if($status=="reject") // updated by jay 16-04
       {
            $db->job_assign_update($job_id,$status);
           
       }
       else if($status=="accept")  
        {
             $db->job_assign_update($job_id,$status);
            $db->update_order_status($order["post_id"],$status,'unpaid'); // modified by jay 16-04
            $job_status="auto_reject";
            $db->job_update($order["post_id"],$job_status,$job_id);
                       
        }
        else if (strtolower($status) == "dispatched") 
        {

             $db->job_assign_update($job_id,$status);
          
           
            if ($update_order =  $db->post_status_update($order["post_id"],$status)) 
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
            $notification = $db->new_notification($noti_type,  $order["post_id"],$msg,$adminstatus,$adminplaystatus);

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
                $temp->$key= $value;
            }
            //$temp = array_map('utf8_encode', $temp);
            //array_push($data["data"], $temp);

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
        
        while ($row = $jobdata->fetch_assoc()) {

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
*name:update weight
*param:deliveryboy_id
method:post
*/
$app->post('/update_weight', function () use ($app) {

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
    $discount=($post_data["discount"]!="")?$post_data["discount"]:0;
    $mail_type=$post_data["mail_type"];
     // calculate charges
   $get_amount=$db->get_amount($weight,$mail_type);
   if($get_amount>0)
   {
        $basic_charges=$get_amount;
        $total_charges=($delivery_charge+$ack_charges+$basic_charges)-$discount;
        // update post grams & charges
        $jobdata = $db->update_weight($post_id,$weight,$basic_charges,$total_charges);
        
        if ($jobdata>0) {
            $data['result'] = true;
            $data['message'] = "";
            $temp['total_charges']=$total_charges;
            $data['data']=$temp;
            
        } else {
            $data['result'] = false;
            $data['message'] = "Weight already updated";
        }
   }
   else
   {
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

*/
$app->get('/get_privacy', function () use ($app) {
    // get_faq
    $db = new DbOperation();
    $result = $db->get_privacy();
    $response = array();
    $response['result'] = false;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($response['data'], $temp);
    }
    echoResponse(200, $response);
});



/*
 *  terms and conditions
 * Parameters:
 * Method: get
 
*/


$app->get('/get_terms', function () use ($app) {
    // get_faq
    $db = new DbOperation();
    $result = $db->get_terms();
    $response = array();
    $response['result'] = false;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($response['data'], $temp);
    }
    echoResponse(200, $response);
});
/*
* order list
*param:job_id
method:post
*/
$app->post('/order_list', function () use ($app) {

    verifyRequiredParams(array('job_id'));
    $job_id = $app->request->post('job_id');


    $db = new DbOperation();
    $data = array();
    $result = $db->order_list($job_id);
    $fields_num = mysqli_num_rows($result);
    if ($fields_num > 0) {

        $data['result'] = true;
        $data['message'] = "";
        $response_detail = array();
        while ($order_list = mysqli_fetch_array($result)) {


            $details = json_decode(str_replace("\'", "'", $order_list["order_list"]));


            if (count($details) > 0) {
                $order = new stdClass();
                for ($k = 0; $k < count($details); $k++) {

                    $order->name = str_replace("&quot;", "''", $details[$k]->name);
                    $order->quantity = str_replace("&quot;", "''", $details[$k]->quantity);
                    array_push($response_detail, $order);
                }

            }

        }
        $data["data"] = $response_detail;
    } else {
        $data['result'] = false;
        $data['message'] = "No data found";
    }

    echoResponse(200, $data);
});




/*
*name:add_location
*param:db_id,from_date,to_date
method:post
*/
$app->post('/add_location', function () use ($app) {

    verifyRequiredParams(array('db_id','lat','lng'));
    $db_id = $app->request->post('db_id');
    $lat = $app->request->post('lat');
    $long = $app->request->post('lng');
    


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
            $data['result'] = false;
            
            $data['message'] = "Please try again";
            
        } else {
            $data['result'] = true;
            $data["message"] = "Added successfully";
           
        }

    echoResponse(200, $data);
});



/*
*name:get account data
*param:db_id,from_date,to_date
method:post
*/
$app->post('/get_account', function () use ($app) {

    verifyRequiredParams(array('db_id','from_date','to_date'));
    $db_id = $app->request->post('db_id');
    $from_date = $app->request->post('from_date');
    $to_date = $app->request->post('to_date');


    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();

    $account_data = $db->get_account($db_id,$from_date,$to_date);
    
    if (!empty($account_data)) {
        $data['result'] = true;
        $data['message'] = "";
        
        while ($row = $account_data->fetch_assoc()) {

            foreach ($row as $key => $value) {
                $temp[$key] = !is_null($value)?strval($value):0;
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
 * call notification
 * Parameters:
 * Method: post
 * dev : jay
*/


$app->post('/call_notification', function () use ($app) {
    verifyRequiredParams(array('o_id'));
    $o_id = $app->request->post('o_id');
    $db = new DbOperation();
    $delivery_boy=$db->delivery_boy_info($o_id);

    $inc = 0;
    $response = array();
    $response['result'] = false;
    $response['data'] = array();
    $response["message"] = "";
    $data = new stdClass();
    $reg_ids_android = array();
    $data->o_id = $o_id;
    $data->message = "Unable to Locate. Please Contact delivery boy";
    $data->title = "Unable to Locate. Please Contact delivery boy";
    $data->body = "Unable to Locate. Please Contact delivery boy";
    $data->delivery_contact=$delivery_boy["contact"];
    $title = "Unable to Locate. Please Contact delivery boy";
    $body = "Unable to Locate. Please Contact delivery boy";
    $res_token = $db->call_notification_to_user_android($o_id);
    while ($token = mysqli_fetch_array($res_token)) {
        $reg_ids_android[$inc++] = $token["device_token"];
    }
    $resp=send_notification_android($data, $reg_ids_android, $title, $body);
    if($resp)
    {
        $response["message"] = "Notification has been sent";
    }

    echoResponse(200, $response);
});

/*
URL: https://pragmanxt.com/pragma_demo_multivendor/Mobile_Services/delivery/v1/index.php/forget_password
 * forget password
 * Parameters:email
 * Method: get
 * dev : jay
*/

$app->get('/forget_password/:userid', function ($userid) use ($app) {
    $response = array();
    $db = new DbOperation();
    $user = $db->getUser($userid);
    if ($user > 0) {

        $uname = $user['name'];
        $password = $user['password'];
        $email=$user['email'];
        $forgot_pass_msg = "<html><p>Dear " . str_replace("*", "'", $uname) . ",</p></br>
<div>This e-mail is in response to your recent request to recover your forgotten password.<br>
Your  password is:" . $password . "</div><br><div>Regards,<br> My City Store</div></html>";
        $message = "Dear " . str_replace("*", "'", $uname) . ",
This sms is in response to your recent request to recover your password.
Your  password is:" . $password;

        smtpmailer($email, "sales@myct.store", "My City Store", "Forgot Password", $forgot_pass_msg);


        $response["result"] = false;
        $response["message"] = "Mail sent.";

        echoResponse(201, $response);

    } else {

        $response["result"] = true;
        $response["message"] = "Please enter valid userid";
        echoResponse(201, $response);
    }
});

/*
* check phone number
*param:phone number

*/

$app->post('/check_phonenumber', function () use ($app) {
    verifyRequiredParams(array('phone_number'));
    $response = array();
    $phone_number = $app->request->post('phone_number');
    
    $db = new DbOperation();
    $res = $db->fetch_contact($phone_number);
    $num_rows = $res->num_rows;
   if ($num_rows > 0) {
        $response['result'] = false;      
        $response['message'] = "exist";
        echoResponse(200, $response);


    } else {

        $response['message'] = "Sorry this number is not registered";
        $response['result'] = true;       
        echoResponse(200, $response);
    }
});


/*
* login_with_mobile
* param: contact,device_type,token,version_code,version_name
* method: post
*/


$app->post('/login_with_mobile', function () use ($app) {
    verifyRequiredParams(array('contact', 'device_type', 'token'));

   $data = array();
    $data["data"] = array();
    $response = array();

    $phonenumber = $app->request->post('contact');
    $device_type = $app->request->post('device_type');
    $token = $app->request->post('token');

    $db = new DbOperation();    
    
    $user = $db->get_deliveryboy($phonenumber);

        //insert into delivery boy availability
        $del_boy_status="on";
        $del_available=$db->delivery_boy_availability($user['id'],$del_boy_status);

        $res1 = $db->insert_delivery_boy_device($user['id'], $token,$device_type);
        if ($res1 == 0) {
            $data['result'] = false;
            
            $data['message'] = "Please try again";
            echoResponse(201, $data);
        } else {
            $data['result'] = true;
            $data["message"] = "Login successfully";
            $response['id'] = $user['id'];
            $response['email'] = $user['email'];
            $response['name'] = $user['name'];
            $response['status'] = $user['status'];
            $response['contact'] = $user['contact'];
            $response['address'] = $user['address'];
            $response['zipcode'] = $user['zipcode'];
            array_push($data["data"], $response);
            echoResponse(201, $data);
        }
    

});



/*
* check delivery boy availability (on/off)
*param:id
method:post
*/
$app->post('/check_delivery_boy_availability', function () use ($app) {

    verifyRequiredParams(array('id'));
    $id = $app->request->post('id');


    $db = new DbOperation();
    $data = array();


    if ($db->check_delivery_boy_availability($id)) {


        $data['result'] = true;
        $data['message'] = "You are available";


    } else {
        $data['result'] = false;
        $data['message'] = "You are not available";
    }

    echoResponse(200, $data);
});




/*
 * update password
 * Parameters:old_pass,new_pass,uid
 * Method: post
*/

$app->post('/update_password', function () use ($app) {
    verifyRequiredParams(array('old_pass', 'new_pass', 'uid'));
    $response = array();
    $old_pass = $app->request->post('old_pass');
    $new_pass = $app->request->post('new_pass');
    $id = $app->request->post('uid');

    $db = new DbOperation();
    $res = $db->update_Password($old_pass, $new_pass, $id);

    if ($res == 0) {
        $response['result'] = false;
        $response['status']="valid";
        $response["error_code"] = 0;
        $response["message"] = "Password updated successfully";
        echoResponse(201, $response);
    } else if ($res == 1) {
        $response["message"] = "Oops! An error occurred while Updating profile";
        $response['result'] = true;
         $response['status']="invalid";
        $response["error_code"] = 1;
        echoResponse(200, $response);
    } else if ($res == 2) {
        $response["message"] = "Current Password is incorrect.";
        $response['result'] = true;
         $response['status']="invalid";
        $response["error_code"] = 1;
        echoResponse(200, $response);
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

// fcm notificaton for android
function send_notification_android($data, $reg_ids_android, $title, $body,$send_to)
{

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
