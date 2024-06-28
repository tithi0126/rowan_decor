<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
//including the required files
require_once '../include/DbOperation.php';
require '../libs/Slim/Slim.php';

date_default_timezone_set("Asia/Kolkata");
\Slim\Slim::registerAutoloader();

require_once('../../../PHPMailer_v5.1/class.phpmailer.php');

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
    $mail_type = isset($data->mail_type)?$data->mail_type:"";
    $priority=isset($data->priority)?$data->priority:"";

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
        //check delivery hours
       // $del_hours=$db->get_delivery_hours($mail_type,$priority);
    
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
           if($key=="collection_date" || $key=="dispatch_date")
            {
                
                $temp[$key]=date("d-m-Y", strtotime($value));
            }
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
            if($key=="collection_date" || $key=="dispatch_date")
            {
                $temp[$key]=date("d-m-Y",strtotime($value));
            }  
        }
        $temp = array_map('utf8_encode', $temp);
        
    }
    while($image_row=$result_image->fetch_assoc())
    {
       
    
        
        if($image_row["image"]!="" && $image_row["status"]=="transit")
        {
            
            $temp["envalope_image"]="https://mykapot.com/mykapotroot555/post_images/".$image_row["image"];
        }
        else if($image_row["status"]=="transit" && $image_row["image"]=="")
        {
               
            $temp["envalope_image"]="";
        }
        if($image_row["image"]!="" && ($image_row["status"]=="dispatch" || $image_row["status"]=="dispatched"))
        {
                   
             $temp["post_image"]="https://mykapot.com/mykapotroot555/post_images/".$image_row["image"];
        }
        else if(($image_row["status"]=="dispatch" || $image_row["status"]=="dispatched") && $image_row["image"]=="")
        {
               
            $temp["post_image"]="";
            
        }
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
    $weight = $data->weight_range_id;
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
            

            //check if user token already exists
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

            } else if ($user["status"] == 'disable') {
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
    $priority=isset($data->priority)?$data->priority:"1";
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

/* *
 * get delivery_date (by Rachna 22-3-24)
 * Parameters: name,password,id
 * Method: POST
 * 
 */

 $app->post('/get_delivery_date', function () use ($app) {

    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $mail_type = $data->mail_type;
    $weight_range_id=$data->weight_range_id;
    $ack_charges=$data->ack_charges;
    $coupon_id=isset($data->coupon_id)?$data->coupon_id:"0";
    $priority=isset($data->priority)?$data->priority:"1";
    $collection_date=$data->collection_date;
    $collection_time=$data->collection_time;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();
    $response = array();
    $temp=array();
    
    //get collection start time & end time
    $collect_time_range=$db->get_collection_time_by_id($collection_time);
   
    // get delivery charges
    $del_charges=$db->get_delivery_charges(); // check mail_settings and add additional charge


    // calculate charges
   //$get_amount=$db->get_amount($weight,$mail_type);
   $get_amount=$db->get_amount($weight_range_id);

   
    //check additional charges & delivery date

    $last_post_time=date("18:00");
    $delivery_hours=$db->get_delivery_hours($mail_type,$priority);
   
    if($delivery_hours["hours"]!="" && $delivery_hours["hours"]<=8)
    {
        $extra_charges=$delivery_hours["amount"];
        if(strtotime($collect_time_range["start_time"])<strtotime($last_post_time))
        {
            
           
            //same del date with extra charges
            $delivery_date=$collection_date;
            
            $new_delivery_date=$db->check_holiday($delivery_date); 
        }
        else{
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
            }
           
            $extra_charges=$delivery_hours["amount"];
            $new_delivery_date=$db->check_holiday($delivery_date); 
        }
               

    }
    // else if($delivery_hours["hours"]<=15)
    // {
    //     //delivery date=collection date +1
    // }
    else
    {
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
        }
       
        $extra_charges=$delivery_hours["amount"];
        $new_delivery_date=$db->check_holiday($delivery_date); 
    }

   $tot_charges=$get_amount+$ack_charges+$del_charges["minimum_delivery_charge"];
   
  // $total_del_charge=$ack_charges+$del_charges["minimum_delivery_charge"]; by jay
   $total_del_charge=$del_charges["minimum_delivery_charge"];
   // get discount
   if($coupon_id!="")  
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
        $total_payable=($del_charges["minimum_delivery_charge"]+$ack_charges+$basic_charges+$extra_charges)-$discount;
        $total_charges=($del_charges["minimum_delivery_charge"]+$ack_charges+$basic_charges+$extra_charges);
       
            $data['result'] = true;
            $data['message'] = "";
            $temp['total_charges']=number_format($total_charges,2,'.',',');
            $temp['basic_charges']=number_format($basic_charges,2,'.',',');
            $temp['discount']=number_format($discount,2,'.',',');
            $temp['delivery_charge']=number_format($del_charges["minimum_delivery_charge"],2,'.',',');
            $temp['delivery_date']=$new_delivery_date;
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

/* *
 * check if user exists(by Rachna 09-04-24)
 * Parameters: contact
 * Method: POST
 * 
 */

 $app->post('/check_user_contact', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $contact = $data->contact;
    
	
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_user=$db->check_user_contact($contact);
    if($res_user>0)
    {
		$data['message'] = "User Exists";
        $data['success'] = true;
    }
    else
    {
        $data['message'] = "User not found";
        $data['success'] = false;
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



/* get config
method:get
parmas:

*/
$app->get('/get_config', function () use ($app) {
    // get_faq
    $db = new DbOperation();
    $result = $db->get_config();
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



/* get info
method:get
parmas:

*/
$app->get('/get_info', function () use ($app) {
    // get_faq
    $db = new DbOperation();
    $result = $db->get_info();
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
*get priority
params:cust_id
method:post
*/
$app->get('/get_priority', function () use ($app) {
   

    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->get_priority();
    
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
*get priority
params:cust_id
method:post
*/
$app->post('/get_weight', function () use ($app) {
   
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $mail_type = $data->mail_type;
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();

    
    $result=$db->get_weight($mail_type);
    
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



/*
 * get notifications
 * Parameters:
 * Method: get
 
*/


$app->post('/get_notifications', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $userid = $data->userid;
    $device_type = isset($data->device_type)?$data->device_type:"";
    $db = new DbOperation();
    $result = $db->get_notifications($userid,$device_type);
    $response = array();
    $response['result'] = true;
    $response['data'] = array();
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
            if($key=="post_id" && $row["noti_type"]!="specific_user")
            {
                $post=$value;
            }
            else
            {
                $post="";
            }
            if($key=="date_time")
            {
                $datetime=date("d-m-Y h:i A", strtotime($value));
                $temp["date_time"]=date("d-m-Y h:i A", strtotime($value));
            }
            if($key=="notif_msg")
            {
                $notif_msg=$value;
                
            }
            if($row["post_id"]!=0)
            {
                $temp["msg"]="Post id:#".$row["post_id"]."<br/>".$row["notif_msg"];  
                
            }
            else{
                $temp["msg"]=$row["notif_msg"];  
            }
         
          
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($response['data'], $temp);
    }
    echoResponse(200, $response);
});

/*

 * logout
 * Parameters:id,tokenid
 * Method: post
*/
$app->post('/logout', function () use ($app) {
    verifyRequiredParams(array('data'));

    $response = array();

    
    $tokenid = $app->request->post('tokenid');
    $data = json_decode($app->request->post('data'));
    $id = trim($data->id);
   
    $device_token = $data->device_token;
    $device_type = $data->device_type;

    $db = new DbOperation();
    $res = $db->logout($id,$device_token,$device_type);

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

/* *
 *  delete account (by Rachna 12-3-24)
 * Parameters: user_id
 * Method: POST
 * 
 */

$app->post('/delete_account', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
    $user_id = $data->user_id;
   
   
    $action='added';
    $db = new DbOperation();
    $data = array();
    $data["data"] = array();  
    $res_del=$db->delete_account($user_id);
    if($res_del>0)
    {
        $data['message'] = "Your account has been deleted successfully";
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
 *  mail type tariff list (by Rachna 13-3-24)
 * Parameters: mail_type
 * Method: POST
 * 
 */

 $app->post('/mail_type_tarrif_list', function () use ($app) {
    verifyRequiredParams(array('data'));
    $data = json_decode($app->request->post('data'));
      
    $mail_type = $data->mail_type;
   
   
    $action='added';
    $db = new DbOperation();
    $data = array();
    $response['result'] = false;
    $response['range_data'] = array();
    $response['mail_settings'] = array();
    $response['info'] = array();
    $result=$db->mail_type_tarrif_list($mail_type);
    while ($row = $result->fetch_assoc()) {
        $temp = array();
        foreach ($row as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($response['range_data'], $temp);
    }

    $res_mail_setting=$db->mail_settings($mail_type);
    while ($row_mail = $res_mail_setting->fetch_assoc()) {
        $temp = array();
        foreach ($row_mail as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($response['mail_settings'], $temp);
    }

    $res_info=$db->info();
    while ($row_info = $res_info->fetch_assoc()) {
        $temp = array();
        foreach ($row_info as $key => $value) {
            $temp[$key] = $value;
        }
        $temp = array_map('utf8_encode', $temp);
        array_push($response['info'], $temp);
    }
    echoResponse(200, $response);
});


//-----------------------------------//



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




//---------------------------------------//







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
