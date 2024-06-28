<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
//including the required files
require_once '../include/DbOperation.php';
require '../libs/Slim/Slim.php';

date_default_timezone_set("Asia/Kolkata");
\Slim\Slim::registerAutoloader();

require_once('../../PHPMailer_v5.1/class.phpmailer.php');

$app = new \Slim\Slim();


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


/* *
 *  get coupon list customerwise (by Jay 14-4-23)
 * Parameters: sender_id
 * Method: POST
 * 
 */

$app->post('/coupon_list1', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $sender_id = $data->sender_id;
   
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

   
    $result=$db->coupon_list1($sender_id);
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
   
    echoResponse(200, $data);
});




/* *
 *  Add customer Address (by Jay 4-4-23)
 * Parameters: cust_id,address_label,house_no,street,area_id,city_id,pincode
 * Method: POST
 * 
 */

$app->post('/add_customer_address', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $cust_id = $data->cust_id;
    $address_label = $data->address_label;
    $house_no = $data->house_no;
    $street = $data->street;
    $area_id = $data->area_id;
	$city_id = $data->city_id;
	$pincode = $data->pincode;
    $map_location=isset($data->map_location)?$data->map_location:"";
    $action='added';
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_customer_address=$db->add_customer_address($cust_id,$address_label,$house_no,$street,$area_id,$city_id,$pincode,$action,$map_location);
    if($res_customer_address>0)
    {
		$data['message'] = "Your new address added successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = false;
    }
    echoResponse(200, $data);
});


/* *
 * get collection_time and delivery_date  -> added by nidhi
 * Parameters: collection_date
 * Method: POST
 *
 */

$app->post('/get_collection_time', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $collection_date = $data->collection_date;
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    
    $collection_date = date('Y-m-d',strtotime($collection_date));
    
    $result=$db->get_collection_time($collection_date);

    

    $collection_day=date('l',strtotime($collection_date));
    $month=date('F',strtotime($collection_date));
    $year=date('Y',strtotime($collection_date));
    
    if($collection_day!="Sunday"){
        if($collection_day=="Monday" || $collection_day=="Tuesday" || $collection_day=="Wednesday" || $collection_day=="Thursday" ){
            $d_date = new DateTime($collection_date);
            $d_date->modify('+1 day');
            $delivery_date = $d_date->format('d-m-Y');
        }  
        else if($collection_day=="Friday" ){
            $dod_date = new DateTime($collection_date);
            $dod_date->modify('+1 day');
            $d_date = $dod_date->format('d-m-Y');
            if(date('d-m-Y', strtotime("Second Saturday Of ".$month." {$year}"))==$d_date || date('d-m-Y', strtotime("Fourth Saturday Of ".$month." {$year}"))==$d_date)
            {
                $d_date = new DateTime($collection_date);
                $d_date->modify('+3 day');
                $delivery_date = $d_date->format('d-m-Y');      
            }
            else{
                $delivery_date = $d_date;
            }
        }
        else if($collection_day=="Saturday"){
            $d_date = new DateTime($collection_date);
            $d_date->modify('+2 day');
            $delivery_date = $d_date->format('d-m-Y');
        }

        //check holiday

        $new_delivery_date=$db->check_holiday($delivery_date); 
    
        while ($row = $result->fetch_assoc()) {
            $temp = array();
            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
    
            $temp = array_map('utf8_encode', $temp);
            array_push($data['data'], $temp);
        }
        $data['delivery_date'] = $new_delivery_date;
        $data['message'] = "";
        $data['success'] = true;
    }
    else{
        $data['message'] = "Collection is closed on Sunday";
        $data['success'] = false;
    }
    
   
    echoResponse(200, $data);
});






/* *
 * Update customer Profile (by Jay 4-4-23)
 * Parameters: name,password,id
 * Method: POST
 * 
 */

$app->post('/update_customer_profile', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $name = $data->name;
    $password = $data->password;
    $id = $data->id;
    $action='updated';
	
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_customer_profile=$db->update_customer_profile($name,$password,$action,$id);
    if($res_customer_profile>0)
    {
		$data['message'] = "Your profile updated successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = false;
    }
    echoResponse(200, $data);
});


// cancel post order (by Jay 8-4-23)
$app->post('/cancel_post_order', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $pid = $data->pid;
    
    $action='updated';
	
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_cancel_post_order=$db->cancel_post_order($pid,$action);
    if($res_cancel_post_order==1)
    {
        // send notification to admin
        $adminstatus=1;
        $adminplaystatus=1;
        $noti_type="post_cancel";
        $msg="Post has been cancelled by user";
        $notification = $db->new_notification($noti_type, $res_customer,$msg,$adminstatus,$adminplaystatus);


		$data['message'] = "Your post order cancelled";
        $data['success'] = true;
    }
    else if($res_cancel_post_order==0)
    {
		$data['message'] = "Your post order cannot be cancelled as order is already porcessed";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = false;
    }
    echoResponse(200, $data);
});



//get_all_order_customerwise (by Jay 8-4-2023)

$app->post('/get_all_order_customerwise', function () use ($app) {
   
    
	 verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $cid = $data->cid;
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->get_all_order_customerwise($cid);
    
     $num=$result->num_rows;
     if($num>0)
     {
          while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

        $data['message'] = "";
        $data['success'] = true;
     }
     else
     {
        $data['message'] = "No order history found!";
        $data['success'] = true;
         
     }
    
   
    
    echoResponse(200, $data);
});


//get_all_order_customerwise (by Jay 8-4-2023)

$app->post('/get_post_order_details', function () use ($app) {
   
    
	 verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $pid = $data->pid;
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->get_post_order_details($pid);
    $result_image=$db->post_image($pid);
    $num=$result->num_rows;
    
   
   
     if($num>0)
     {
         $temp = array();
          while ($row = $result->fetch_assoc()) {
        
        foreach ($row as $key => $value) {
            
            if($key=="profile_pic" && $value!="")
            {
                 $temp[$key] = "https://mykapot.com/mykapotroot555/deliveryboy_id/".$value;
                
            }
            else
            {
                $temp[$key] = $value;
            }    
        }
        $temp = array_map('utf8_encode', $temp);
        
    }
    if($result_image["image"]!="")
    {
       
         $temp["post_image"]="https://mykapot.com/mykapotroot555/post_images/".$result_image["image"];
    }
    else
    {
        $temp["post_image"]="";
    }
       
        array_push($data['data'], $temp);
        $data['message'] = "";
        $data['success'] = true;
     }
     else
     {
        $data['message'] = "No Post order found!";
        $data['success'] = true;
         
     }
    
   
    
    echoResponse(200, $data);
});


/*
*get customer address list
params:cust_id
method:post
*/
$app->post('/get_customer_address', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $cid = $data->cid;
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->get_customer_address($cid);
    
     $num=$result->num_rows;
     if($num>0)
     {
        while ($row = $result->fetch_assoc()) {
        $temp = array();
            foreach ($row as $key => $value) {
                $temp[$key] = $value;
            }
            $temp = array_map('utf8_encode', $temp);
            array_push($data['data'], $temp);
        }

        $data['message'] = "";
        $data['success'] = true;
     }
     else
     {
        $data['message'] = "No order history found!";
        $data['success'] = true;
         
     }
    
   
    
    echoResponse(200, $data);
});

/*
*delete customer address
params:address_id
method:post
*/
$app->post('/delete_customer_address', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $address_id = $data->address_id;
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->delete_customer_address($address_id);
    
    
     if($result==1)
     {
        

        $data['message'] = "Address deleted successfully";
        $data['success'] = true;
     }
     else if ($result==2)
     {
        $data['message'] = "Customer Address is already in use!";
        $data['success'] = true;
     }
     else
     {
        $data['message'] = "An error occurred!";
        $data['success'] = true;
         
     }
    
   
    
    echoResponse(200, $data);
});



/* *
 * Update customer address (by Rachna 18-4-23)
 * Parameters: cust_id,address_id,address_label,house_no,street,area_id,city_id,pincode
 * Method: POST
 * 
 */

$app->post('/update_customer_address', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $cust_id = $data->cust_id;
    $address_id = $data->address_id;
    $address_label = $data->address_label;
    $house_no = $data->house_no;
    $street = $data->street;
    $area_id = $data->area_id;
    $city_id = $data->city_id;
    $pincode = $data->pincode;
    $map_location=isset($data->map_location)?$data->map_location:"";
    $action='updated';
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_customer_profile=$db->update_customer_address($address_id,$cust_id,$address_label,$house_no,$street,$area_id,$city_id,$pincode,$action,$map_location);
    if($res_customer_profile>0)
    {
        $data['message'] = "Your address updated successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = false;
    }
    echoResponse(200, $data);
});



/* *
 * customer registration 
 * Parameters: name,email,contact,password
 * Method: POST
 * 
 */

$app->post('/customer_reg', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $name = $data->name;
    $email = trim($data->email);
    $password = trim($data->password);
    $contact = $data->contact;
    $status='enable';
    $action='added';
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_customer=$db->customer_reg($name,$email,$password,$contact,$status,$action);
    if($res_customer>0)
    {

        $data['message'] = "Customer added successfully";
        $data['success'] = true;
        //send notification to admin

        $adminstatus=1;
        $adminplaystatus=1;
        $noti_type="customer_reg";
        $msg="New user registered";
        $notification = $db->new_notification($noti_type, $res_customer,$msg,$adminstatus,$adminplaystatus);
    }
    else if ($res_customer==-2)
    {
        $data['message'] = "Contact number already exist!";
        $data['success'] = false;

    }
    else if ($res_customer==-3)
    {
        $data['message'] = "Email-id already exist!";
        $data['success'] = false;

    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = false;
    }
       
    echoResponse(200, $data);
});





/* *
 * add post 
 * Parameters: name,address,education,stu_type(level),enrollment_dt,skill_id,course_id,
 * Method: POST
 * 
 */

$app->post('/add_post', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $receiver_name = $data->receiver_name;
    $sender = $data->sender;
    $house = $data->house;
    $street = $data->street;
    $area = $data->area;
    $city = $data->city;
    $pincode = $data->pincode;
    $mail_type = $data->mail_type;
    $weight = $data->weight;
    $ack = $data->ack;
    $priority = $data->priority;
    $coll_address = $data->coll_address;
    $coll_time = $data->coll_time;
    $dispatch_date = $data->dispatch_date;
    $basic_charge = $data->basic_charge;
    $delivery_charge = $data->delivery_charge;
    $ack_charges = $data->ack_charge;
    $coupon_id = $data->coupon;
    $discount = $data->discount;
    
     
   // added by Rachna
    $total_charges=isset($data->total_charges)?$data->total_charges:($data->total_amt);
    $total_payment = $data->total_amt;
  
    $collection_date = isset($data->collection_date)?$data->collection_date:""; 

    $dispatch_date=date("Y-m-d", strtotime($dispatch_date));
    $collection_date=date("Y-m-d", strtotime($collection_date));
  //  $status='enable';  -> removed by jay 16-04-23
    $action='added';
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    
     //Added by jay
    
    if($ack==2 || $ack=="yes")
    {
        $ack="yes";
    }
    else
    {
        $ack="no";
    }
    
    
    
    // 
    
    if($db->add_post($receiver_name,$sender,$house,$street,$area,$city,$pincode,$mail_type,$weight,$ack,$priority,$coll_address,$coll_time,$dispatch_date,$basic_charge,$delivery_charge,$ack_charges,$total_charges,$coupon_id,$discount,$total_payment,$collection_date))
    {

        $data['message'] = "Post added successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = true;
    }
       
    echoResponse(200, $data);
});





/* *
 * city list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/city_list', function () use ($app) {
    //verifyRequiredParams(array('data'));
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->city_list();
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});



/* *
 * area and pincode list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/area_list', function () use ($app) {
      verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $ct_id = $data->ct_id;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->area_list($ct_id);
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});






/* *
 * collection address
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/collection_address_list', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $sender_id = $data->sender_id;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->collection_address_list($sender_id);
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * collection time
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/collection_time', function () use ($app) {
    //verifyRequiredParams(array('data'));
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->collection_time();
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});

/* *
 * coupon list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/coupon_list', function () use ($app) {
    //verifyRequiredParams(array('data'));
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->coupon_list();
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * mail_type
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/mail_type', function () use ($app) {
    //verifyRequiredParams(array('data'));
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->mail_type();
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * user login
 * Parameters: email, password,device_token,type
 * Method: POST
 * 
 */

$app->post('/login', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $email = trim($data->email);
    $password = trim($data->password);
    $device_token = $data->device_token;
    $device_type = $data->device_type;
    
    $db = new DbOperation();
    $data = array();
    
  $response = array();
   
    if ($db->customerLogin($email, $password)) 
    {
        $cust = $db->getUser($email);
        $response = array();
        if($cust["status"]=="enable")
        {

           

            foreach ($cust as $key => $value) {
                $response[$key]= $value;
            }
            

            $insert_device = $db->insert_customer_device($cust['id'], $device_token, $device_type);
            $data["data"]=$response;
            $data['success'] = true;
        }
        else{
            $data["data"]=null;  // added by jay 16-04-23
            $data["success"] = false;
            $data["message"]="User is disabled!";
        }
        
        
        
        

    }
    else
    {
         $data["data"]=null;  // added by jay 16-04-23
        $data["success"] = false;
        $data["message"] = "Invalid username or password";

    }
    
   

    echoResponse(200, $data);
});



/* *
 *  login using OTP
 * Parameters: phno,tokenid,type
 * Method: POST
 * */

$app->post('/login_otp', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
   
    $phno = $data->phno;
    $device_token = $data->device_token;
    $type = $data->device_type;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
     
    $user = $db->getUser_phno($phno);
        
        if ($user > 0) {
            $response = array();
            if (strtolower($user["status"] == 'enable')) {
                $data['message'] = "";
                $data['result'] = true;
                $response['id'] = $user['id'];
                $response['name'] = $user['name'];
                
                
                //insert delivery boy devices
                $insert_device = $db->insert_customer_device($user['id'], $device_token, $type);
               
                $data["data"]=$response;

            } else if ($delivery_boy["status"] == 'disable') {
                $data["data"]=null;  
                $data['message'] = "You are disabled, contact admin";
                $data['result'] = false;

            } else {
                $data["data"]=null; 
                $data['result'] = false;
                $data['message'] = "Invalid phone number";
            }
            

        
     
    }
    else {
                $data["data"]=null; 
                $data['result'] = false;
                $data['message'] = "Invalid phone number";
            }
    
    echoResponse(200, $data);
});

/* *
 * user profile
 * Parameters: uid
 * Method: POST
 * by jay 21-04-23
 */



$app->post('/get_profile', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $uid = $data->uid;
    $db = new DbOperation();
    $result = $db->get_profile($uid);
    $response = array();
    $flag=false;
    $response['data'] = array();
     $temp = null;
    while ($row = $result->fetch_assoc()) {
        $temp = new stdClass();
        $flag=true;
        foreach ($row as $key => $value) {
            $temp->$key = $value;
        }
      
       
    }
    
    
    $response['result'] = $flag;
    $response["data"]=$temp;
    echoResponse(200, $response);
});





/*
*name:update weight
*param:deliveryboy_id
method:post
*/
$app->post('/get_charges', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $mail_type = $data->mail_type;
    $weight=$data->weight;
    $ack_charges=$data->ack_charges;
    $coupon_id=isset($data->coupon_id)?$data->coupon_id:"0";
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();
    $temp=array();
    
    //Patch code by jay
    
   /* if($ack_charges==1)
    {
        $ack_charges=2;
    }
    else
    {
        $ack_charges=0;
    }*/
    
    
    // pactch code over
    

    // get delivery charges
    $del_charges=$db->get_delivery_charges();
    
    // calculate charges
   $get_amount=$db->get_amount($weight,$mail_type);


   $tot_charges=$get_amount+$ack_charges+$del_charges["minimum_delivery_charge"];
   
  // $total_del_charge=$ack_charges+$del_charges["minimum_delivery_charge"]; by jay
   $total_del_charge=$del_charges["minimum_delivery_charge"];
   // get discount
   if($coupon_id!="")  // doubt
   {
     $get_discount=$db->get_discount($coupon_id,$tot_charges,$total_del_charge);
    if($get_discount===1)
    {
        $data['coupon_message'] = "Invalid Coupon Code";
         $discount=0;
    }
    else if ($get_discount===2)
    {
        $data['coupon_message']="Amount is too small";
        $discount=0;
    }
    else
    {

     $discount=$get_discount;
    }
   }
   else
   {
    $discount=0;
   }
   if($get_amount>0)
   {
        $basic_charges=$get_amount;
        $total_payable=($del_charges["minimum_delivery_charge"]+$ack_charges+$basic_charges)-$discount;
        $total_charges=($del_charges["minimum_delivery_charge"]+$ack_charges+$basic_charges);
       
            $data['result'] = true;
            $data['message'] = "";
            $temp['total_charges']=number_format($total_charges,2,'.',',');
            $temp['basic_charges']=number_format($basic_charges,2,'.',',');
            $temp['discount']=number_format($discount,2,'.',',');
            $temp['delivery_charge']=number_format($del_charges["minimum_delivery_charge"],2,'.',',');
            $temp['handling_charges']=number_format($ack_charges,2,'.',','); // added by jay
            $temp['total_payable']=number_format($total_payable,2,'.',','); // added by jay
            $data['data']=$temp;
            
       
   }
   else
   {
        $data['result'] = false;
        $data['message'] = "Weight not available";
   }
    

    echoResponse(200, $data);
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


/* *
 *  Add post review (by Rachna 19-4-23)
 * Parameters: post_id,rating,review
 * Method: POST
 * 
 */

$app->post('/add_post_review', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $post_id = $data->post_id;
    $rating = $data->rating;
    $review = $data->review;
   
    $action='added';
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_customer_review=$db->add_post_review($post_id,$rating,$review,$action);
    if($res_customer_review>0)
    {
        $data['message'] = "Your new review added successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = false;
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
    $user = $db->getUser($email);
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




//-----------------------------------//

/* *
 * faculty list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/faculty_list', function () use ($app) {
    //verifyRequiredParams(array('data'));
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->faculty_list();
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * faculty list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/batch_list', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $faculty_id = $data->faculty_id;
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->batch_list($faculty_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});

/* *
 * student list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/student_list', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $batch_id = $data->batch_id;
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->student_list($batch_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * attendence from faculty side
 * Parameters: stu_id,batch_id,attendence
 * Method: POST
 * 
 */

$app->post('/faculty_attendence', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $batch_id = $data->batch_id;
    $stu_id = $data->stu_id;
    $attendence = $data->attendence;
    $remark = $data->remark;
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    if($db->faculty_attendence($batch_id,$stu_id,$attendence,$remark))
    {

        $data['message'] = "Attendence added successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = true;
    }
       
    echoResponse(200, $data);
});


/* *
 * attendence from student side
 * Parameters: stu_id,batch_id,attendence
 * Method: POST
 * 
 */

$app->post('/stu_attendence', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $batch_id = $data->batch_id;
    $stu_id = $data->stu_id;
    $attendence = $data->attendence;
   
    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    if($db->stu_attendence($batch_id,$stu_id,$attendence))
    {

        $data['message'] = "Attendence added successfully";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "An error occurred";
        $data['success'] = true;
    }
       
    echoResponse(200, $data);
});


/* *
 * skill list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/skill_list', function () use ($app) {
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->skill_list();
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * course list
 * Parameters: 
 * Method: POST
 * 
 */

$app->post('/course_list', function () use ($app) {
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->course_list();
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});



/* *
 * book list
 * Parameters: course_id
 * Method: POST
 * 
 */

$app->post('/book_list', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $course_id = $data->course_id;
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->book_list($course_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * chapter list
 * Parameters: book_id
 * Method: POST
 * 
 */

$app->post('/chapter_list', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $book_id = $data->book_id;
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->chapter_list($book_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * exercise list
 * Parameters: book_id,chapter_id
 * Method: POST
 * 
 */

$app->post('/exercise_list', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $book_id = $data->book_id;
    $chapter_id = $data->chapter_id;
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->exercise_list($book_id,$chapter_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * skills progress
 * Parameters: stu_id
 * Method: POST
 * 
 */

$app->post('/skill_status', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $stu_id = $data->stu_id;
    
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->skill_status($stu_id);
    
     
    
    while ($row = $result->fetch_assoc()) {
        $temp = new stdClass();
        foreach ($row as $key => $value) {
            $temp->$key = $value;
        }
        
        
    }
    $data['data']=$temp;
    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * roadmap
 * Parameters: stu_id
 * Method: POST
 * 
 */

$app->post('/roadmap', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $stu_id = $data->stu_id;
    
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->roadmap($stu_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        // get exercise count
        $result_exercise=$db->roadmap_exercise_count($stu_id,$row["bid"]);
        $temp = array();
        foreach ($row as $key => $value) {
            $temp["total_skill"]=$result_exercise["total_skill"];
            $temp["completed_skill"]=$result_exercise["completed_skill"];
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * chapter_list
 * Parameters: stu_id,book_id
 * Method: POST
 * 
 */

$app->post('/stu_chapter_list', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $stu_id = $data->stu_id;
    $book_id = $data->book_id;
    
        
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->stu_chapter_list($stu_id,$book_id);
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * banner
 * Parameters:
 * Method: POST
 * 
 */

$app->post('/banner', function () use ($app) {

    
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $result=$db->banner();
    
     
    $response = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
           
            $temp["image_path"]="http://englishexpress.co.in/roots555/banner/";
            
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($data['data'], $temp);
    }

    $data['message'] = "";
    $data['success'] = true;
    
    echoResponse(200, $data);
});


/* *
 * edit_profile
 * Parameters:uid,type
 * Method: POST
 * 
 */

$app->post('/edit_profile', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $uid = $data->uid;
    $type=$data->type;
    $email=$data->email;
    $pic=(isset($_FILES["pic"]["name"]))?$_FILES["pic"]["name"]:"";
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    if($type=="student")
    {
        $result=$db->edit_stu_profile($uid,$email,$pic);
    }
    else
    {
        $result=$db->edit_faculty_profile($uid,$email,$pic);
    }
    
    if($result==1)
    {
        // upload pic
        if (isset($_FILES["pic"]["name"]) && $_FILES["pic"]["name"] != "" && $type=="student")
        {
                            
            $i = 1;
            $MainFileName = $_FILES["pic"]["name"];
           
            if(move_uploaded_file($_FILES["pic"]["tmp_name"], "../../../roots555/studentProfilePic/" . $MainFileName))
            {
                $data["image_upload"] = true;
            }
            else
            {
                $data["image_upload"] =false;
            }
                
            
        }
        // upload faculty pic
        if (isset($_FILES["pic"]["name"]) && $_FILES["pic"]["name"] != "" && $type=="faculty")
        {
                            
            $i = 1;
            $MainFileName = $_FILES["pic"]["name"];
           
            if(move_uploaded_file($_FILES["pic"]["tmp_name"], "../../../roots555/faculty_pic/" . $MainFileName))
            {
                $data["image_upload"] = true;
            }
            else
            {
                $data["image_upload"] =false;
            }
                
            
        }
       
        $data['message'] = "Profile updated successfully";
        $data['success'] = true;
    }
    else
    {

        $data['message'] = "An error occurred! Try again";
        $data['success'] = false;
    }


    
    
    echoResponse(200, $data);
});


// change password


$app->post('/change_password', function () use ($app) {
    verifyRequiredParams(array('data'));

    $data = json_decode($app->request->post('data'));
    
    $user_type=$data->user_type;
    $password=$data->password;
    $uid=$data->uid;
    $data = array();
    $data["data"] = array();  

   //device_token

    $db = new DbOperation();
    if($user_type=="student")
    {
        $res = $db->stu_password_update($uid,$password);
    }
    else
    {
        $res = $db->faculty_password_update($uid,$password);
    }
    

    if ($res == 1) {


        $data['success'] = true;
        
        $data['message'] = "Password updated successfully.";
        
    } else {

        $data['success'] = false;
       
        $data['message'] = "Please try again";
        
    }
    echoResponse(201, $data);

});

// logout
$app->post('/logout', function () use ($app) {
    verifyRequiredParams(array('data'));

    $data = json_decode($app->request->post('data'));
    $device_token = $data->device_token;
    $user_type=$data->user_type;
    $device_type=$data->device_type;
    $uid=$data->uid;
    $data = array();
    $data["data"] = array();  

   //device_token

    $db = new DbOperation();
    if($user_type=="student")
    {
        $res = $db->stu_logout($uid, $device_token,$device_type);
    }
    else
    {
        $res = $db->faculty_logout($uid, $device_token,$device_type);
    }
    

    if ($res == 1) {


        $data['success'] = true;
        
        $data['message'] = "Logged out";
        
    } else {

        $data['success'] = false;
       
        $data['message'] = "Please try again";
        
    }
    echoResponse(201, $data);

});


//---------------------------------------//


/*

* register new user
* method:post
* param:email,name,contact,password
*/

$app->post('/customer_registeration', function () use ($app) {
    verifyRequiredParams(array('email', 'name', 'contact', 'password'));
    $response = array();
    // $username = $app->request->post('username');
    $email = $app->request->post('email');
    $name = $app->request->post('name');
    $gender = $app->request->post('gender');
    $contact = $app->request->post('contact');
    $profile_for = $app->request->post('profile_for');
    $password = $app->request->post('password');

    $db = new DbOperation();
    $res = $db->do_reg_customer($email, $name, $contact, $password,$gender,$profile_for);


    if ($res == 0) {
        $user = $db->getUser($email);
        $data= new stdClass();
        $response['success'] = true;
        
        $data->id=$user['id'];
        $response["data"]=$data;
        $response["message"] = "You are successfully registered";
      
        echoResponse(201, $response);
    } else if ($res == 1) {
        $response["message"] = "Oops! An error occurred while registereing";
        $response['success'] = false;
        echoResponse(200, $response);
    } else if ($res == 2) {
        //$res_user = $db->check_user($email, $contact);
        //$user = $res_user->fetch_assoc();
        $response["message"] = "Sorry, this user email already exist";
        $response['success'] = false;
        
        
        echoResponse(200, $response);
    } else if ($res == 3) {
        //$res_user = $db->check_user($email, $contact);
        //$user = $res_user->fetch_assoc();
        $response["message"] = "Sorry, this user contact already exist";
        $response['success'] = false;
        
        echoResponse(200, $response);
    }


});







/*
*add address
*param: ssid,address
*method:post
*/

$app->post('/add_address', function () use ($app) {
    verifyRequiredParams(array('address','pincode','name','phone'));
    $data = array();
    
    $address = $app->request->post('address');
    $pincode = $app->request->post('pincode');
    $name = $app->request->post('name');
    $phone = $app->request->post('phone');
    $email = $app->request->post('email');
    $country = $app->request->post('country');
    $qty = $app->request->post('qty');
    $feedback = "";
    $product="vibhuti";
    $order_from="android";
    $reg_from="android";
    $date=Date("Y-m-d");
    $phone_code = ($app->request->post('phone_code')!="")?$app->request->post('phone_code'):"+91";


    $db = new DbOperation();

    $res = $db->check_phone_number($phone,$phone_code);
    $num_rows = $res->num_rows;
    $phone_data=mysqli_fetch_array($res);
    
    if ($num_rows > 0) {
       // update address
        $check_user=$db->get_user_order($phone_data["ssno"]);
        $user_data=mysqli_fetch_array($check_user);
        $res_add=$db->update_address($name,$phone,$address,$pincode,$qty,$email,$country,$phone_code,$reg_from);
        $user_id=$phone_data["ssno"];

        $delivery_date=explode(" ", $user_data["delivery_date"]);
        $now = strtotime(Date('Y-m-d')); // or your date as well
        $your_date = strtotime($delivery_date[0]);
        $datediff = $now - $your_date;

        $diff= round($datediff / (60 * 60 * 24));
            /*if($diff>=10)
            {*/
                $date=Date('Y-m-d', strtotime($delivery_date[0]. ' + 10 days'));
                $current_date=Date("Y-m-d");
                  if($date<$current_date)
                  {
                     $date=Date("Y-m-d",strtotime("+3 days"));
                  }
                $user_order=$db->add_order($phone_data["ssno"],$product,$feedback,$date,$order_from);
                if ($user_order > 0) {
                $data['success'] = true;        
               $data['message'] = "Sai Ram, we have received your
request for Divya Udi Prasadam.
It will be dispatched by ".Date("d-m-Y",strtotime($date)).".
Thanks.";        
            }
           
       /* }
        else
        {
            $data['success'] = false;        
            $data['message'] = "You can not repeat order within 10 days";
        }*/
        /*$data['success'] = true;        
        $data['message'] = "Address added successfully";*/

        
    }
    else
    {
        
         // add address
        $res_add=$db->add_address($name,$phone,$address,$pincode,$qty,$email,$country,$phone_code,$reg_from);
        $user_id=$res_add;

        $date=date('Y-m-d', strtotime( ' + 3 days'));
        $user_order=$db->add_order($user_id,$product,$feedback,$date,$order_from);
        if ($user_order > 0) {
            $data['success'] = true;        
            $data['message'] = "Address added successfully ";
        
        }
        else
        {
            $data['success'] = false;        
            $data['message'] = "An error occurred!";
        }
    }
    
        
        /*$data['success'] = true;        
        $data['message'] = "Address added successfully";*/
        
        
    
    echoResponse(200, $data);

});



/*
*add/update user
*param: ssid,address
*method:post
*/

$app->post('/add_user', function () use ($app) {
    verifyRequiredParams(array('address','pincode','name','phone'));
    $data = array();
    $address = $app->request->post('address');
    $pincode = $app->request->post('pincode');
    $name = $app->request->post('name');
    $phone = $app->request->post('phone');
    $email = $app->request->post('email');
    $country = $app->request->post('country');
    $qty = $app->request->post('qty');
    $phone_code = ($app->request->post('phone_code')!="")?$app->request->post('phone_code'):"+91";
    $reg_from = ($app->request->post('reg_from')!="")?$app->request->post('reg_from'):"android";
    $fcm_token = ($app->request->post('fcm_token')!="")?$app->request->post('fcm_token'):"";
    $user_type="external";


    $db = new DbOperation();

    $res = $db->check_phone_number($phone,$phone_code);
    $num_rows = $res->num_rows;

   
    if ($num_rows > 0) {
       // update address
         $user_data=mysqli_fetch_array($res);
        $res_add=$db->update_address($name,$phone,$address,$pincode,$qty,$email,$country,$phone_code,$reg_from);
        $user_id=$user_data["ssno"];
    }
    else
    {
        
         // add address
        $res_add=$db->add_address($name,$phone,$address,$pincode,$qty,$email,$country,$phone_code,$reg_from);
        $user_id=$res_add;
    }
    
    $res = $db->insert_device_type($reg_from, $fcm_token, $user_id, $user_type);
        $obj= new stdClass();
        $obj->user_id=$user_id;
        $data['success'] = true;        
        $data['message'] = "Address added successfully";
        $data['data']=$obj;

        echoResponse(200, $data);

});



/*
*add order
*param: cid
*method:post
*/

$app->post('/add_order', function () use ($app) {
    verifyRequiredParams(array('cid'));
    $data = array();
    $cid = $app->request->post('cid');
    //$user_type = $app->request->post('user_type');
    $feedback = $app->request->post('feedback');
    $product="vibhuti";
    $date=Date("Y-m-d");
    $order_from = ($app->request->post('order_from')!="")?$app->request->post('order_from'):"android";
    $flag=1;
    $db = new DbOperation();    

    $check_user=$db->get_user_order($cid);
    $user_data=mysqli_fetch_array($check_user);
    if($check_user->num_rows==0)
    {
        // add order
        $date=date('Y-m-d', strtotime( ' + 3 days'));

        $user_order=$db->add_order($cid,$product,$feedback,$date,$order_from);
        if ($user_order > 0) {
           // $add_noti=$db->add_notification();
            $data['success'] = true;        
            $data['message'] = "Sai Ram, we have received your
request for Divya Udi Prasadam.
It will be dispatched by ".Date("d-m-Y",strtotime($date)).".
Thanks.";  
        
        }
        else
        {
            $data['success'] = false;        
            $data['message'] = "An error occurred!";
        }
    }
    else
    {
        $delivery_date=explode(" ", $user_data["delivery_date"]);
        $now = strtotime(Date('Y-m-d')); // or your date as well
        $your_date = strtotime($delivery_date[0]);
        $datediff = $now - $your_date;

        $diff= round($datediff / (60 * 60 * 24));
                
                $date=date('Y-m-d', strtotime($delivery_date[0]. ' + 30 days'));
                $current_date=Date("Y-m-d");
                  if($date<$current_date)
                  {
                     $date=Date("Y-m-d",strtotime("+3 days"));
                     $flag=0;
                  }
                  
                  if($flag==0)
                {
                $user_order=$db->add_order($cid,$product,$feedback,$date,$order_from);
                if ($user_order > 0) {
                $data['success'] = true;        
                $data['message'] = "Sai Ram, we have received your
request for Divya Udi Prasadam.
It will be dispatched by ".Date("d-m-Y",strtotime($date)).".
Thanks.";  
            
            }
            else
            {
                $data['success'] = false;        
                $data['message'] = "An error occurred!";
            }
        }
        else
        {
           $data['success'] = true;        
                $data['message'] = "Since your Divya Udi request is already placed, you can not place another request before ".Date("d-m-Y",strtotime($date)).".
Om Sai Ram .";  
        }
    }
  
  
    echoResponse(200, $data);

});







/* *
 * get user profile
 * Parameters: user_id,payment_type,amount,start_date,end_date,payment
 * Authorization: Put API Key in Request Header
 * Method: post
 * */
$app->get('/get_user_profile/:user_id', 'authenticateUser', function($user_id) use ($app){
    
    
    $db = new DbOperation();
    $data = array();
    $temp=new stdClass();
    $response = new stdClass();
    $user = $db->getUserData($user_id);
    if(!empty($user))
    {
        $response->id = $user['id'];
        $response->fname = $user['fname'];  
        $response->lname = $user['lname'];               
        $response->email= $user['email'];   
        $response->contact = $user['contact'];  
        $response->password = $user['password'];  
        $response->gender = $user['gender'];  
        $response->profile_for = $user['profile_for'];  
        $response->status = $user['status'];  
        $response->authentication = $user['authentication'];  
        $response->hide_photo  = $user['hide_photo']; 
        if($user["otp_verification"]=="verified")
        {
            $response->otp_verification=true;
        }
        else
        {
            $response->otp_verification=false;   
        }
       
    }
    
    
     
    
    // get personal details
    $personal=$db->get_personal_data($user_id);
    $personaldata = new stdClass();
    while ($row = $personal->fetch_assoc()) {
        
        foreach ($row as $key => $value) {
            $personaldata->$key = $value;
        }
        //$personaldata = array_map('utf8_encode', $personaldata);
        
    }
    $temp->personal_details=$personaldata;
    //get social details
    $social=$db->get_social_data($user_id);
    $socialdata = new stdClass();
    while ($row = $social->fetch_assoc()) {
        
        foreach ($row as $key => $value) {
            $socialdata->$key = $value;
        }
       // $socialdata = array_map('utf8_encode', $socialdata);
        
    }
    $temp->social_details=$socialdata;

    //get career details
    $career=$db->get_career_data($user_id);
    $careerdata = new stdClass();
    while ($row = $career->fetch_assoc()) {
        
        foreach ($row as $key => $value) {
            $careerdata->$key = $value;
        }
       // $careerdata = array_map('utf8_encode', $careerdata);
        
    }
    $temp->career_details=$careerdata;
    $temp->register=$response;
    $data["data"]=$temp;
    $data["success"]=true;
    $data["message"]="";

    
    
   
    echoResponse(200,$data);
});




/* *
 * add user profile
 * Parameters: none
 * Authorization: Put API Key in Request Header
 * Method: post
 * */
$app->post('/add_profile', 'authenticateUser', function() use ($app){
    verifyRequiredParams(array('profile_data'));
    $data = json_decode($app->request->post('profile_data','user_id'));
    $user_id = $app->request->post('user_id');
    $personal_details=$data->personal_details;
    $education=$data->education;
    $social_details=$data->social_details;
    $db = new DbOperation();
    $response = array();
    $data=array();
    $MainFileName="";
        //$personal_details->gender,$personal_details->hide_photo

    // update registration
    $register=$db->update_register($user_id,$personal_details->first_name,$personal_details->last_name,$personal_details->contact,$personal_details->gender,$personal_details->profile_for,$personal_details->hide_photo);

    //check for personal/social and career profile

    $check_personal_profile=$db->check_personal_profile($user_id);
    if($check_personal_profile->num_rows>0)
    {
        
        $personal_res=$db->update_personal_profile($user_id,$personal_details->dob,$personal_details->height,$personal_details->weight,$personal_details->complexion,$personal_details->country_id,$personal_details->state_id,$personal_details->city_id,$personal_details->smoking_habit,$personal_details->drinking_habit,$personal_details->diet_preference,$personal_details->about,$personal_details->thalassemia);
    }
    else
    {
        $personal_res=$db->add_personal_profile($user_id,$personal_details->dob,$personal_details->height,$personal_details->weight,$personal_details->complexion,$personal_details->country_id,$personal_details->state_id,$personal_details->city_id,$personal_details->smoking_habit,$personal_details->drinking_habit,$personal_details->diet_preference,$personal_details->about,$personal_details->thalassemia);
    }

    
    $check_social_profile=$db->check_social_profile($user_id);
    if($check_social_profile->num_rows>0)
    {
       $social_res=$db->update_social_profile($user_id,$social_details->marital_status,$social_details->mother_tongue,$social_details->languages_known,$social_details->religion_id ,$social_details->caste_id,$social_details->sub_caste_id,$social_details->manglik,$social_details->sai_devotee);
    }
    else
    {
        $social_res=$db->add_social_profile($user_id,$social_details->marital_status,$social_details->mother_tongue,$social_details->languages_known,$social_details->religion_id ,$social_details->caste_id,$social_details->sub_caste_id,$social_details->manglik,$social_details->sai_devotee);
    }
   
   $check_career_profile=$db->check_career_profile($user_id);
   if($check_career_profile->num_rows>0)
   {
        $education_res=$db->update_education($user_id,$education->higher_education,$education->occupation,$education->employed_in,$education->income);
   }
   else
   {
        $education_res=$db->add_education($user_id,$education->higher_education,$education->occupation,$education->employed_in,$education->income);
   }

   
   
    if($personal_res==1)
    {
        
        $data['personal_message']="Personal profile added successfully";
        if (isset($_FILES["id_proof"]["name"]) && $_FILES["id_proof"]["name"] != "")
        {
            if (file_exists("../../../vivaahRoots777/uploads/" . $_FILES["id_proof"]["name"] )) {
                $i = 1;
                $MainFileName = $_FILES["id_proof"]["name"];
                $Arr = explode('.', $MainFileName);
                $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                while (file_exists("../../../vivaahRoots777/uploads/" . $MainFileName)) {
                    $i++;
                    $MainFileName = $Arr[0] . $i . "." . $Arr[1];
                }

            } else {
                $MainFileName = $_FILES["id_proof"]["name"];;
            }
                   
                $add_id=$db->add_id_proof($user_id,$MainFileName);
        }
        
        if($MainFileName!=""){
            if(move_uploaded_file($_FILES["id_proof"]["tmp_name"], "../../../vivaahRoots777/uploads/" . $MainFileName))
            {
                      $data["image_upload"] = "success";
            }
            else
            {
                      $data["image_upload"] = "fail";
            }
            
        }
    
    }
    else
    {
        
        $data['personal_message']="Error in personal profile";
    }
    if($social_res==1)
    {
               
        $data['social_message']="Social profile added successfully";
    
    }
    else
    {
        
        $data['social_message']="Error in social profile";
    }
    if($education_res==1)
    {
        
        $data['education_message']="Career profile added successfully";
    
    }
    else
    {
        
        $data['education_message']="Error in career profile";
    }
    if($personal_res==1 || $social_res==1 || $education_res==1)
    {
        $response['success'] = true; 
        $response['message'] = "Profile added successfully";
    }
    else
    {
        $response['success'] = false;
    }
    $response['data']=$data;
    echoResponse(200,$response);
});


// upload files
$app->post('/uploadFiles', 'authenticateUser',function () use ($app) {
    verifyRequiredParams(array('reg_id'));
    $response = array();
    $reg_id = $app->request->post('reg_id');
    $db = new DbOperation();


   // upload images
   
    
       
    
    if (isset($_FILES["images"]["name"]) && $_FILES["images"]["name"] != "")
        {
            print_r($_FILES["images"]["name"]);
            //if (file_exists("../../../vivaahRoots777/uploads/" . $_FILES["images"]["name"] )) {
            for($j=0;$j<count($_FILES["images"]["name"]);$j++)
            {
                $i = 1;
                $MainFileName = $_FILES["images"]["name"][$j];
                $milliseconds = round(microtime(true) * 1000);
                $Arr = explode('.', $MainFileName);
                $MainFileName = $reg_id .date("Ymd")  . $milliseconds. ".".$Arr[1];              
           
                $type="image";
                $res = $db->uploadPhotos($MainFileName, $reg_id,$type);
                if($MainFileName!=""){
                    if(move_uploaded_file($_FILES["images"]["tmp_name"][$j], "../../../vivaahRoots777/uploads/" . $MainFileName))
                    {
                              $data["image_upload"] = "success";
                    }
                    else
                    {
                              $data["image_upload"] = "fail";
                    }
                    
                }
        }
        
        
    }

    // upload video

    if (isset($_FILES["video"]["name"]) && $_FILES["video"]["name"] != "")
        {
            //if (file_exists("../../../vivaahRoots777/uploads/" . $_FILES["images"]["name"] )) {
             
                $i = 1;
                $videoName = $_FILES["video"]["name"];
                $milliseconds = round(microtime(true) * 1000);
                $Arr = explode('.', $videoName);
                $videoName = $reg_id .date("Ymd")  . $milliseconds. ".".$Arr[1];              
           
                $type="video";
                $res = $db->uploadPhotos($videoName, $reg_id,$type);
        
        
        if($videoName!=""){
            if(move_uploaded_file($_FILES["video"]["tmp_name"], "../../../vivaahRoots777/uploads/" . $videoName))
            {
                      $data["image_upload"] = "success";
            }
            else
            {
                      $data["image_upload"] = "fail";
            }
            
        }
    }
    $db = new DbOperation();
    
       

    
    if ($res == 0) {


        $response["success"] = false;

        $response["message"] = "Oops! An error occurred while uploading";
        echoResponse(200, $response);
    } else {

        $response["success"] = true;

        $response["message"] = "Images uploaded";
        echoResponse(201, $response);

    }
});



/*
URL: https://pragmanxt.com/pragma_demo_multivendor/Mobile_Services/delivery/v1/logout
 * logout
 * Parameters:id,tokenid
 * Method: post
*/
$app->post('/logout', function () use ($app) {
    verifyRequiredParams(array('id', 'tokenid'));

    $response = array();

    $id = $app->request->post('id');
    $tokenid = $app->request->post('tokenid');

    $db = new DbOperation();
    $res = $db->logout($id, $tokenid);

    if ($res == 1) {


        $response['error'] = false;
        $response['value'] = "valid";
        $response['message'] = "Logged out";
        echoResponse(201, $response);
    } else {

        $response['error'] = true;
        $response['error_code'] = 1;
        $response['value'] = "invalid";
        $response['message'] = "Please try again";
        echoResponse(201, $response);
    }

});


/*
URL: https://pragmanxt.com/pragma_demo_multivendor/Mobile_Services/delivery/v1//get_privacy
 * privacy policy
 * Parameters:
 * Method: get
*/


// get privacy policy
$app->post('/get_privacy', function () use ($app) {
    
    $typ = $app->request->post('typ');
    $db = new DbOperation();
    $result = $db->get_privacy($typ);
    $response = array();
    $response['error'] = false;
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





$app->post('/get_terms', function () use ($app) {
    

    $typ = $app->request->post('typ');
    $db = new DbOperation();
    $result = $db->get_terms($typ);
    $response = array();
    $response['error'] = false;
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

 * view profile
 * Parameters:
 * Method: post
 * dev : jay
*/


$app->post('/view_profile', function () use ($app) {
    verifyRequiredParams(array('userid'));
    $userid = $app->request->post('userid');
    $db = new DbOperation();
    $result = $db->view_profile($userid);
    $response = array();
    $response['error'] = false;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        if($row["owned_by"]=="vendor")
        {
            $owner_res=$db->get_myvendor($row["id"]);
            $owner=$owner_res["business_name"];
        }
        else
        {
            $owner="Sai Store";
        }
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp["owner"]=$owner;
        $temp = array_map('utf8_encode', $temp);
        array_push($response['data'], $temp);
    }
    echoResponse(200, $response);
});

/*
 * call notification
 * Parameters:
 * Method: post
 * dev : jay
*/





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
        $response["error"] = true;
        $response["error_code"] = 99;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

function authenticateUser(\Slim\Route $route)
{
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    if (isset($headers['Authorization'])) {
        $db = new DbOperation();
        $api_key = $headers['Authorization'];
        if (!$db->isValidUser($api_key)) {
            $response["success"] = false;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        }
    } else {
        $response["success"] = false;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}



// fcm notificaton for android
function send_notification_android($data, $reg_ids_android, $title, $body)
{

    $url = 'https://fcm.googleapis.com/fcm/send';
   
   $api_key = 'AAAA2n2PB4A:APA91bEb_4LGpFCH3xTmzG763VWpuV02DGrMmunv1e-bza06vBLdIZgcHaqYu_f7P8a-druZ7buh6b1-OzcLGCP1Yc0bywdVb93dlKQ-BmOgZCVSD135Itw9UKSuNy6rWGqyWr7Q9eLX';
    
    
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
        $api_key = 'AAAAECnANz8:APA91bGYp0sVe-8WMW7EJt6SHsaHXplVfZb0jniq8kSuw62aruDgcfLkH_-lTSQR2tFu_NSexF7L9tl05c1N1LxcLbrry2q_vE8gv5k4_xXM8GQj32EJDPbJm-FeO532GPO2wp-9sg6K';
    }
    else
    {
        $api_key = 'AAAAECnANz8:APA91bGYp0sVe-8WMW7EJt6SHsaHXplVfZb0jniq8kSuw62aruDgcfLkH_-lTSQR2tFu_NSexF7L9tl05c1N1LxcLbrry2q_vE8gv5k4_xXM8GQj32EJDPbJm-FeO532GPO2wp-9sg6K';
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
