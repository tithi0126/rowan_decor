<?php
date_default_timezone_set("Asia/Kolkata");
class DbOperation
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect();
        $this->con = $db->connect();
    }
    
 // get coupon list customerwise ( by Jay 14-4-23)    
public function coupon_list1($sender_id)
{
    $stmt = $this->con->prepare("(select c1.c_id,c1.couponcode,c1.discount from coupon_counter cc1, coupon c1 where cc1.coupon_id=c1.c_id and c1.status='enable' and cc1.counter>0 and cc1.customer_id=? and curdate()>=start_date and curdate()<=end_date and c1.type='specific') union (select c_id,couponcode,discount from coupon where status='enable' and curdate()>=start_date and curdate()<=end_date and type='generic')");
    $stmt->bind_param("i",$sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

    
    
    
    // customer address add ( by Jay 4-4-23)
public function add_customer_address($cust_id,$address_label,$house_no,$street,$area_id,$city_id,$pincode,$action,$map_location)
{
        
            $stmt = $this->con->prepare("INSERT INTO `customer_address` (`cust_id`, `address_label`, `house_no`, `street`,`area_id`,`city_id`,`pincode`,`map_location`,`action`) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("isssiisss", $cust_id,$address_label,$house_no,$street,$area_id,$city_id,$pincode,$map_location,$action);
           
            $result = $stmt->execute();
            $lastId=mysqli_insert_id($this->con);
            $stmt->close();
            
            if ($result) {

                return $lastId;
            } else {
                return 0;
            }
 }
 
 
 // get collection_time added by nidhi
public function get_collection_time($collection_date)
{
    $todays_date =  date('Y-m-d');
    $current_time = date('H:i');

    if($collection_date==$todays_date){
        
      $stmt = $this->con->prepare("select id,time_format(start_time,'%h:%i %p') as start_time ,time_format(end_time,'%h:%i %p') as end_time from collection_time where status='enable' and start_time>=?");
      $stmt->bind_param("s",$current_time);
      $stmt->execute();
      $result = $stmt->get_result();
     $stmt->close();
    } else if($collection_date>$todays_date){
      
      $stmt = $this->con->prepare("select id,time_format(start_time,'%h:%i %p') as start_time ,time_format(end_time,'%h:%i %p') as end_time from collection_time where status='enable'");
      $stmt->execute();
      $result = $stmt->get_result();
      $stmt->close();
    }
    //$stmt->close();
    return $result;
}
 //get collection time range by id
public function get_collection_time_by_id($collection_time)
{
       
    $stmt = $this->con->prepare("select id,start_time ,end_time from collection_time where id=?");
    $stmt->bind_param("i",$collection_time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// get all post order customerwise (by Jay 8-4-2023)
public function get_all_order_customerwise($cid)
{
    $stmt = $this->con->prepare("SELECT p1.*,m1.mail_type as mail_type_name ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM `post` p1,mail_type m1,mail_type_tariff m2 WHERE p1.mail_type=m1.id and p1.weight=m2.id and  p1.sender_id=? order by p1.id desc");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $orders = $stmt->get_result();
    $stmt->close();
    return $orders;
}



//update customer profile ( by Jay 4-4-23)
public function update_customer_profile($name,$password,$action,$id)
{

 $stmt = $this->con->prepare("UPDATE `customer_reg` SET `name`=?,`password`=?,`action`=? where `id`=?");
          $stmt->bind_param("sssi", $name,$password,$action,$id);
           
            $result = $stmt->execute();
           
            $stmt->close();
            
            if ($result) {

                return $id;
            } else {
                return 0;
            }
 }

// Cancel post order (by jay 8/4/2023)
public function cancel_post_order($pid,$action)
{
	// $action will be used later -> no column found in table.
	 $stmt = $this->con->prepare("update post set post_status='canceled_user' where post_status='pending' and id=?");
          $stmt->bind_param("i",$pid);
           
            $result = $stmt->execute();
            $num_rows_aff = mysqli_affected_rows($this->con);
            $stmt->close();
            
            if ($result) {

                return $num_rows_aff;
            } else {
                return -1;
            }
}
    
 // get post order details  (by Jay 8-4-2023)
public function get_post_order_details($pid)
{
   $stmt= $this->con->prepare("SELECT post_status FROM `post` WHERE id=?");
	
	$stmt->bind_param("i", $pid);
    $stmt->execute();
    $resultset = $stmt->get_result();
    $stmt->close();
   
    $row=mysqli_fetch_array($resultset);
	
	if($row["post_status"]=='accept')
	{
	      $stmt1 = $this->con->prepare("SELECT p1.collection_date,p1.receiver_name,p1.order_date,d1.profile_pic, p1.weight, p1.priority, ca1.address_label as collection_address, concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time, p1.total_payment, p1.dispatch_date, d1.name, d1.contact, p1.post_status, '' as trackid,m1.mail_type as mail_type_name ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM post p1, delivery_boy d1 , job_assign j1, customer_address ca1, collection_time ct1,mail_type m1,mail_type_tariff m2  where p1.id = j1.post_id and j1.delivery_boy_id = d1.db_id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and p1.mail_type=m1.id and p1.weight=m2.id  and p1.id=?"); 
  
    $stmt1->bind_param("i", $pid);
    $stmt1->execute();
    $orders = $stmt1->get_result();
    $stmt1->close();
	}
	else if($row["post_status"]=='transit')
	{
	     $stmt1 = $this->con->prepare("SELECT  p1.collection_date,p1.receiver_name,p1.order_date,d1.profile_pic, p1.weight, p1.priority,ca1.address_label as collection_address,concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time, p1.total_payment, p1.dispatch_date, d1.name, d1.contact, p1.post_status, d2.image as trackid,m1.mail_type as mail_type_name ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM post p1, delivery_boy d1 , delivery_images d2 , job_assign j1, customer_address ca1, collection_time ct1 ,mail_type m1,mail_type_tariff m2 where p1.id = j1.post_id and j1.delivery_boy_id = d1.db_id and j1.id = d2.job_id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and p1.mail_type=m1.id and p1.weight=m2.id and d2.status='barcode' and p1.id=?");
         $stmt1->bind_param("i", $pid);
         $stmt1->execute();
        $orders = $stmt1->get_result();
        $stmt1->close();
	}
	else if($row["post_status"]=='dispatch' || $row["post_status"]=='dispatched')
	{
	     $stmt1 = $this->con->prepare("SELECT  p1.collection_date,p1.receiver_name,p1.order_date,d1.profile_pic, p1.weight, p1.priority,ca1.address_label as collection_address,concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time, p1.total_payment, p1.dispatch_date, d1.name, d1.contact, p1.post_status, d2.image as trackid,m1.mail_type as mail_type_name ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM post p1, delivery_boy d1 , delivery_images d2 , job_assign j1, customer_address ca1, collection_time ct1,mail_type m1,mail_type_tariff m2  where p1.id = j1.post_id and j1.delivery_boy_id = d1.db_id and j1.id = d2.job_id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and  p1.mail_type=m1.id and p1.weight=m2.id and d2.status='barcode' and p1.id=?");
         $stmt1->bind_param("i", $pid);
         $stmt1->execute();
        $orders = $stmt1->get_result();
        $stmt1->close();
	}
    else
	{
	 $stmt1 = $this->con->prepare("SELECT  p1.collection_date,p1.receiver_name,p1.order_date,'' as profile_pic, p1.weight, p1.priority,ca1.address_label as collection_address, concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time,  p1.total_payment, p1.dispatch_date, '' as name, '' as contact, p1.post_status, '' as trackid,m1.mail_type as mail_type_name ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM post p1, customer_address ca1, collection_time ct1,mail_type m1,mail_type_tariff m2 where p1.mail_type=m1.id and p1.weight=m2.id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and p1.id=?");
    $stmt1->bind_param("i", $pid);
    $stmt1->execute();
    $orders = $stmt1->get_result();
    $stmt1->close();
	
		
	}
	
	
    return $orders;
}
   
    
    


// customer registration
public function customer_reg($name,$email,$password,$contact,$status,$action)
{

    if (!$this->isemailIDExists($email)) 
    {

        if (!$this->isContactExists($contact)) 
        {

            $stmt = $this->con->prepare("INSERT INTO `customer_reg`( `name`, `email`, `password`, `contact`,`status`,`action`) VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $name,$email,$password,$contact,$status,$action);
           
            $result = $stmt->execute();
            $lastId=mysqli_insert_id($this->con);
            $stmt->close();
            
            if ($result) {

                return $lastId;
            } else {
                return 0;
            }
        }
        else
        {
            return -2;
        }
    }
    else
    {
        return -3;
    }
}

//get delivery boy reg data for otp login
     public function getUser_phno($phno)
    {
        $stmt = $this->con->prepare("SELECT * FROM customer_reg WHERE contact=? and action!='deleted'");
        $stmt->bind_param("s", $phno);
        $stmt->execute();
        $deliveryboy = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $deliveryboy;
    }




// add post
public function add_post($receiver_name,$sender,$house,$street,$area,$city,$pincode,$mail_type,$weight,$ack,$priority,$coll_address,$coll_time,$dispatch_date,$basic_charge,$delivery_charge,$ack_charges,$total_charges,$coupon_id,$discount,$total_payment,$collection_date)
{
    $poststatus="pending";
    $paymentstatus="unpaid";
    
    
    $stmt = $this->con->prepare("INSERT INTO `post`( `receiver_name`, `sender_id`, `house_no`, `street_1`, `area`, `city`, `pincode`, `mail_type`, `weight`, `acknowledgement`, `priority`, `collection_address`, `collection_time`, `dispatch_date`, `basic_charges`, `delivery_charge`, `ack_charges`, `total_charges`, `coupon_id`, `discount`, `total_payment`,`post_status`,`payment_status`,`collection_date`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sisssssissssssssssisssss",$receiver_name,$sender,$house,$street,$area,$city,$pincode,$mail_type,$weight,$ack,$priority,$coll_address,$coll_time,$dispatch_date,$basic_charge,$delivery_charge,$ack_charges,$total_charges,$coupon_id,$discount,$total_payment,$poststatus,$paymentstatus, $collection_date);
   
    $result = $stmt->execute();
    $lastId=mysqli_insert_id($this->con);
    $stmt->close();
    
    if ($result) {
		
		
		
	//assign post to dboy

    //get zoneid from customer address
    $stmt_zone = $this->con->prepare("SELECT a1.aid,a1.zone,a1.pincode FROM customer_address c1 ,area a1 where c1.area_id=a1.aid and c1.ca_id=?");
    $stmt_zone->bind_param("i",$coll_address);
    $stmt_zone->execute();
    $Resp_zone=$stmt_zone->get_result()->fetch_assoc();
    $stmt_zone->close();

    // get dboy from zone
    
    $stmt_dboy = $this->con->prepare("select db1.* from delivery_boy db1,deliveryboy_zone dbz where dbz.deliveryboy_id=db1.db_id and db1.status='enable' and dbz.zone_id=?");
    $stmt_dboy->bind_param("i",$Resp_zone["zone"]);
    $stmt_dboy->execute();
    $Resp_dboy=$stmt_dboy->get_result();
    $stmt_dboy->close();
    while($dboy=mysqli_fetch_array($Resp_dboy))
    {
      // assign post to dboy
      $distance="0";
      $job_status="pending";

      // check if dboy status is on

    $stmt_check_dboy = $this->con->prepare("select id,delivery_boy_id,status from delivery_boy_avalibility where delivery_boy_id=? order by id desc limit 1");
      $stmt_check_dboy->bind_param("i",$dboy["db_id"]);
      $stmt_check_dboy->execute();
      $Resp_check_dboy=$stmt_check_dboy->get_result()->fetch_assoc();
      $stmt_check_dboy->close();

      if($Resp_check_dboy["status"]=="on")
      {

      
      $stmt_post_assign = $this->con->prepare("INSERT INTO `job_assign`( `delivery_boy_id`, `post_id`, `job_status`, `distance`) VALUES (?,?,?,?)");
      $stmt_post_assign->bind_param("iiss",$dboy["db_id"],$lastId,$job_status,$distance);
      $Resp_post=$stmt_post_assign->execute();
      $stmt_post_assign->close();

      // send notification to dboy

        $today=date("Y-m-d");
        if($collection_date==$today)
        {

        
            $stmt_device = $this->con->prepare("SELECT * FROM `delivery_boy_device` where `type`='android' and db_id=?");
            $stmt_device->bind_param("i",$dboy["db_id"]);
            $stmt_device->execute();
            $res_devices=$stmt_device->get_result();
            $stmt_device->close();
            $not = new stdClass();
            $reg_ids_android = array();
            
            $inc=0;
        
            $not->message = "New job has been assigned";
            $not->title =  "Post Id:#".$lastId;
            $not->job_id =  $lastId;
            $not->body =  "New job has been assigned";
            $not->status="New";
            $title ="Post Id:#".$lastId;
            $body =   "New job has been assigned";

            
            // notification to android devices
            
            while ($token = mysqli_fetch_array($res_devices)) {
                $reg_ids_android[$inc++] = $token["token"];
            }
        
            $resp=send_notification_android($not, $reg_ids_android, $title, $body);
        }
     }

    }
    //decrement coupon counter
	  if($coupon_id!="")
       {
        //decrease coustomer coupon count
        $stmt_coupon = $this->con->prepare("update coupon_counter set counter=counter-1 where customer_id=? and coupon_id=?");
        $stmt_coupon->bind_param("ii",$sender,$coupon_id);
        $Resp_coupon=$stmt_coupon->execute();
        $stmt_coupon->close();
       }
       
      //insert into notification
      $noti_type="post";
      $noti_status=1;
      $playstatus=1;
      $stmt_noti = $this->con->prepare("INSERT INTO `notification`( `noti_type`, `noti_type_id`, `status`, `playstatus`) VALUES (?,?,?,?)");
      $stmt_noti->bind_param("siii",$noti_type,$lastId,$noti_status,$playstatus);
      $Resp_noti=$stmt_noti->execute();
      $stmt_noti->close();	
		
		

        return 1;
    } else {
        return 0;
    }
}



//update customer address ( by Rachna 18-4-23)
public function update_customer_address($address_id,$cust_id,$address_label,$house_no,$street,$area_id,$city_id,$pincode,$action,$map_location)
{

    $stmt = $this->con->prepare("UPDATE `customer_address` SET `cust_id`=?,`address_label`=?,`house_no`=?,`street`=?,`area_id`=?,`city_id`=?,`pincode`=?,`map_location`=?,`action`=? where `ca_id`=?");
    $stmt->bind_param("isssiisssi", $cust_id,$address_label,$house_no,$street,$area_id,$city_id,$pincode,$map_location,$action,$address_id);

    $result = $stmt->execute();

    $stmt->close();

    if ($result) {

        return $address_id;
    } else {
        return 0;
    }
 }



// get city list
 public function city_list()
{
    $stmt = $this->con->prepare("select c1.* from city c1,state s1 where c1.state=s1.state_id and c1.status='enable' ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}


// get area and pincode
 public function area_list($ct_id)
{
    $stmt = $this->con->prepare("SELECT * FROM `area` WHERE city=? and status='enable'");
     $stmt->bind_param("i",$ct_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}


// get collection time list
 public function collection_time()
{
    
    $stmt = $this->con->prepare("SELECT id, TIME_FORMAT(start_time, '%h:%i %p') as start_time, TIME_FORMAT(end_time, '%h:%i %p') as end_time, status, user_id,action,dt from collection_time where `status`='enable' ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// get collection adress list
 public function collection_address_list($sender_id)
{
    $stmt = $this->con->prepare("select ca.*,c1.city_name,a1.area_name from customer_address ca, area a1,city c1 where ca.area_id=a1.aid and ca.city_id=c1.city_id and ca.action!='deleted' and c1.status='enable' and ca.cust_id=?");
    $stmt->bind_param("i",$sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// get coupon list
 public function coupon_list()
{
    $stmt = $this->con->prepare("SELECT c_id,name FROM coupon where `status`='enable' ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// get mail type list
 public function mail_type()
{
    $stmt = $this->con->prepare("SELECT * FROM mail_type where `status`='enable' ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}


//get_privacy 
public function get_privacy()
{
    $stmt = $this->con->prepare("select * from privacy_policy where `type`='user'");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

//get_config 
public function get_config()
{
    $stmt = $this->con->prepare("select * from config");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

//get_info 
public function get_info()
{
    $stmt = $this->con->prepare("select * from info");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

// get_terms
public function get_terms()
{
    $stmt = $this->con->prepare("select * from termsandcondition where `type`='user'");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

// get_priority
public function get_priority()
{
    $stmt = $this->con->prepare("select * from priority");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
} 

//get_weight
public function get_weight($mail_type)
{
    $stmt = $this->con->prepare("select * from mail_type_tariff where mail_type=?");
	$stmt->bind_param("i", $mail_type);
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

// user login

public function customerLogin($email, $pass)
{
   
    
    $stmt = $this->con->prepare("SELECT * FROM customer_reg WHERE email=? and BINARY password =? and action!='deleted'");
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
    
}


// get profile  added by jay 23-04-23
    public function get_profile($uid)
    {
        $stmt = $this->con->prepare("SELECT * FROM `customer_reg` WHERE id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $results = $stmt->get_result();
        $stmt->close();
        return $results;
    }



  //Method to get user details by email
public function getUser($email)
{
    $stmt = $this->con->prepare("SELECT * FROM customer_reg WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user;
}





//add customer device
public function insert_customer_device($userid,$token,$type)
{
   
    if (!$this->iscustomerExists($userid,$token,$type)) {
       

        $stmt = $this->con->prepare("INSERT INTO `customer_devices`(`cust_id`, `device_token`, `device_type`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $userid, $token, $type);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }
   
}
//check if user exist
public function check_user_contact($contact)
{
    $stmt = $this->con->prepare("SELECT id from customer_reg WHERE contact = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}
private function iscustomerExists($cust_id, $device_token, $device_type)
{

    
    $stmt = $this->con->prepare("select * from  `customer_devices` where cust_id=? and device_token=? and device_type=? ");
    $stmt->bind_param("iss", $cust_id, $device_token, $device_type);
    $stmt->execute();
    $stmt->store_result();
   $num_rows = $stmt->num_rows;
    $stmt->close();
    
    return $num_rows>0;
}

// check if email already exists

private function isemailIDExists($email)
{

    $stmt = $this->con->prepare("SELECT id from customer_reg WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}
// check if contact already exists

private function isContactExists($contact)
{
    $stmt = $this->con->prepare("SELECT id from customer_reg WHERE contact = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}

//insert notification
public function new_notification($noti_type,$pid,$msg,$status,$playstatus)
{

   
    $stmt = $this->con->prepare("INSERT INTO `notification`(`noti_type`, `noti_type_id`,`msg`,`status`,`playstatus`) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sisii",$noti_type,$pid,$msg,$status,$playstatus);
    $result = $stmt->execute();
   
    $stmt->close();

    if ($result) {
        return 1;
    } else {
        return 0;
    }

}

//  get amount 
public function get_amount($weight)
{

    
  /*  $stmt = $this->con->prepare("SELECT * FROM mail_type m1,mail_type_tariff m2 WHERE m2.mail_type=m1.id and m1.id=?");
    $stmt->bind_param("i", $mail_type);
    $stmt->execute();
    $post_data = $stmt->get_result();
    $stmt->close();
    while($mail_amount=mysqli_fetch_array($post_data))
    {
        
        if($weight>=$mail_amount["gm_from"] && $weight<=$mail_amount["gm_to"])
        {
            $basic_charges=$mail_amount["amount"];
        }
        else
        {
            $basic_charges=0;
        }
    }*/
    
    //$stmt = $this->con->prepare("SELECT m2.amount FROM mail_type m1,mail_type_tariff m2 WHERE m2.mail_type=m1.id and m2.gm_from<=$weight and m2.gm_to>=$weight and  m1.id=?");
	$stmt = $this->con->prepare("SELECT m2.amount FROM mail_type_tariff m2 WHERE m2.id=?");
    $stmt->bind_param("i", $weight);
    $stmt->execute();
    $post_data = $stmt->get_result();
    $stmt->close();
    
    if($mail_amount=mysqli_fetch_array($post_data))
    {
            $basic_charges=$mail_amount["amount"];
    }
    else
    {
            $basic_charges=0;
    }
    
    
    return $basic_charges;
   

}
//  get delivery charges 
public function get_delivery_charges()
{

    
    $stmt = $this->con->prepare("select * from delivery_settings where status='enable'");
    
    $stmt->execute();
    $del_charges = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $del_charges;
   

}

//  get coupon discount 
public function get_discount($coupon_id,$total_amt,$total_del_charge)
{

    
    $stmt = $this->con->prepare("select * from coupon where c_id=?");
    $stmt->bind_param("i",$coupon_id);
    $stmt->execute();
    $res_coupon = $stmt->get_result();
    $stmt->close();

    $row_num = mysqli_num_rows($res_coupon);

         
        if ($row_num > 0) {
            $date = date('Y-m-d');
            $p_row = mysqli_fetch_array($res_coupon);
            $start_date = $p_row['start_date'];
            $end_date = $p_row['end_date'];
            $discount = $p_row['discount'];
            $max_discount_amount = $p_row['max_discount'];
            $min_amount = $p_row['min_amount'];
            $percentage = $p_row["discount"];
         
            $paymentDate = date('Y-m-d', strtotime($date));
            //echo $paymentDate; // echos today! 
            $contractDateBegin = date('Y-m-d', strtotime($start_date));
            $contractDateEnd = date('Y-m-d', strtotime($end_date));

           
            if ((strtotime($date) >= strtotime($start_date)) && (strtotime($date) <= strtotime($end_date))) {

               
                if ((float)$total_amt >= (float)$min_amount) {

                    
                    // $amount_discount1 = $total_amt * $discount;
                    // $amount_discount = $amount_discount1 / 100;
                    $amount_discount1 = $total_del_charge * $discount;
                    $amount_discount = $amount_discount1 / 100.0;

                    if ($amount_discount < $max_discount_amount) {
                        $final_discount = $amount_discount;
                        //$final_amount = $total_amt - $final_discount;
                       
                        return number_format($final_discount,2,'.',',');
                    } else {

                        $final_discount = $max_discount_amount;
                        //$final_amount = $total_amt - $final_discount;
                        
                        return number_format($final_discount,2,'.',',');
                    }
                } else {
                    return 2;
                }
            } else {
                return 1;
            }
        } else {   
            return 1;
        }
}

// get post image
public function post_image($pid)
{
    $stmt = $this->con->prepare("SELECT d1.* FROM delivery_images d1,job_assign j1 WHERE d1.job_id=j1.id and j1.post_id=? and d1.status!='barcode' ");
     $stmt->bind_param("i",$pid);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

 // get customer address list  
public function get_customer_address($cid)
{
    $stmt = $this->con->prepare("select concat(ca.address_label,'-',ca.house_no,',',ca.street,',',c1.city_name,',',a1.area_name) as address from customer_address ca, area a1,city c1 where ca.area_id=a1.aid and ca.city_id=c1.city_id and ca.action!='deleted' and ca.cust_id=?");
    $stmt->bind_param("i",$cid);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}


// check holiday
public function check_holiday($delivery_date)
{
   
    $delivery_date=date("Y-m-d",strtotime($delivery_date));
    $stmt = $this->con->prepare("SELECT * FROM holidays WHERE `date`=? ");
    $stmt->bind_param("s",$delivery_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $num_rows=$result->num_rows;
    $stmt->close();
    
    if($num_rows>0)
    {   
        $new_delivery_date=date("Y-m-d",strtotime('+1 days',strtotime($delivery_date)));
        $dispatch_day=date('l',strtotime($new_delivery_date));
        $month=date('F',strtotime($new_delivery_date));
        $year=date('Y',strtotime($new_delivery_date));
        if($dispatch_day=="Friday" ){
            $dod_date = new DateTime($new_delivery_date);
            $dod_date->modify('+1 day');
            $d_date = $dod_date->format('d-m-Y');
            if(date('d-m-Y', strtotime("Second Saturday Of ".$month." {$year}"))==$d_date || date('d-m-Y', strtotime("Fourth Saturday Of ".$month." {$year}"))==$d_date)
            {
                $d_date = new DateTime($new_delivery_date);
                $d_date->modify('+3 day');
                $dispatch_date = $d_date->format('d-m-Y');      
            }
            else{
                $dispatch_date = $d_date;
            }
        }
        else if($dispatch_day=="Saturday"){
            
            $d_date = new DateTime($new_delivery_date);
            $d_date->modify('+2 day');
            $dispatch_date = $d_date->format('d-m-Y');
        }
        else if($dispatch_day=="Sunday")
        {
            
            $d_date = new DateTime($new_delivery_date);
            $d_date->modify('+1 day');
            $dispatch_date = $d_date->format('d-m-Y');

        }
        else
        {
            $dispatch_date=date("d-m-Y",strtotime($new_delivery_date));
        }



    }
    else
    {
        $dispatch_date=date("d-m-Y",strtotime($delivery_date));
    }
    
    
    return $dispatch_date;
    
}

//get delivery hours
public function get_delivery_hours($mail_type,$priority)
{
    
    
    $stmt = $this->con->prepare("select m1.*,p1.priority from mail_settings m1,priority p1 where m1.priority_id=p1.id and m1.mail_type_id=? and p1.priority=?");
    $stmt->bind_param("is",$mail_type,$priority);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}



//delete customer address

public function delete_customer_address($address_id)
{

    try
    {
        $stmt = $this->con->prepare("update customer_address set action='deleted' where ca_id=? ");
        
        $stmt->bind_param("i", $address_id);
        $result = $stmt->execute();
      
        
        if ($result) {
            return 1;
        } else {
            if(strtok($obj->con1-> error,  ':')=="Cannot delete or update a parent row")
              {
                throw new Exception("Address already in use!");
              }
            return 0;
        }
         $stmt->close();
    } 
    catch(\Exception  $e) {
    return 2;
  }
}

public function add_post_review($post_id,$rating,$review,$action)
{

    $stmt = $this->con->prepare("INSERT INTO `post_review`(`post_id`,`rating`,`review`,`action`) VALUES (?,?,?,?)");
    $stmt->bind_param("idss", $post_id,$rating,$review,$action);

    $result = $stmt->execute();
    $lastId=mysqli_insert_id($this->con);
    $stmt->close();

    if ($result) {

        return $lastId;
    } else {
        return 0;
    }
 }

 // get_notifications
public function get_notifications($userid,$device_type)
{
    
    $device_str=($device_type!="")?" WHEN notification_type='".$device_type."' then user_ids=''":"";
    $stmt = $this->con->prepare("SELECT n1.id,n1.noti_type,n1.noti_type_id as post_id,n1.msg,n1.dt as date_time,CONCAT(n1.msg,' on ',DATE_FORMAT(n1.dt, '%d-%m-%Y %h:%i %p')) as notif_msg FROM `notification` n1,post p1,customer_reg c1 WHERE n1.noti_type_id=p1.id and p1.sender_id=c1.id and (n1.noti_type='post_accepted' or n1.noti_type='post_in_transit' or n1.noti_type='post_dispatched') and c1.id=?
 union 
 SELECT id,notification_type as noti_type,'0' as post_id,msg,date_time,CONCAT(msg,' on ',DATE_FORMAT(date_time, '%d-%m-%Y %h:%i %p')) as notif_msg FROM notification_center where case WHEN notification_type='specific_user' then user_ids=?  WHEN notification_type='all' then user_ids='' ".$device_str." end
 order by date_time desc 

limit 25");
    //SELECT n1.id,n1.noti_type,n1.noti_type_id as post_id,n1.msg,n1.dt as date_time FROM `notification` n1,post p1,customer_reg c1 WHERE n1.noti_type_id=p1.id and p1.sender_id=c1.id and (n1.noti_type='post_accepted' or n1.noti_type='post_in_transit' or n1.noti_type='post_dispatched') and c1.id=? order by n1.id desc limit 25
    //SELECT id,notification_type as noti_type,'0' as post_id,msg,date_time FROM notification_center where case WHEN notification_type="specific_user" then user_ids=87 end
    $stmt->bind_param("ii",$userid,$userid);
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}


public function delete_account($user_id)
{
    
    $stmt = $this->con->prepare("UPDATE `customer_reg` SET `action`='deleted',contact=CONCAT(contact,'_deleted'),email=CONCAT(email,'_deleted') where id=?");
    $stmt->bind_param("i",$user_id);
    
    $result = $stmt->execute();
    $affected=$stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

public function mail_type_tarrif_list($mail_type)
{
    
    $stmt = $this->con->prepare("select m2.id,m1.mail_type,m2.gm_from,m2.gm_to,m2.amount from mail_type m1, mail_type_tariff m2 where m2.mail_type=m1.id and m2.mail_type=?");
    $stmt->bind_param("i",$mail_type);
    
    $result = $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

public function mail_settings($mail_type)
{
    //$stmt = $this->con->prepare("SELECT ms.id,m1.mail_type,p1.priority,ms.hours,ms.amount FROM `mail_settings`ms ,mail_type m1, priority p1 WHERE ms.mail_type_id=m1.id and ms.priority_id=p1.id and ms.mail_type_id=?");
	$stmt = $this->con->prepare("SELECT concat(t1.gm_from,'g - ',t1.gm_to,'g') as weight, p1.priority, s1.hours, t1.amount, s1.amount as charges FROM mail_settings s1, mail_type m1, mail_type_tariff t1, priority p1 WHERE s1.mail_type_id=m1.id AND s1.mail_tariff_id=t1.id and s1.priority_id=p1.id AND s1.mail_type_id=?");
    $stmt->bind_param("i",$mail_type);
    
    $result = $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

public function info()
{
    
    $stmt = $this->con->prepare("select * from info");
  
    
    $result = $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}


// logout

public function logout($id,$device_token,$device_type)
{

     $stmt = $this->con->prepare("delete from customer_devices where cust_id=? and device_token=? and device_type=?");

    $stmt->bind_param("iss", $id,$device_token,$device_type);
    $result = $stmt->execute();
    $stmt->close();
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}


// fcm notificaton for android
private function send_notification_android($data, $reg_ids_android, $title, $body)
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




    

    

  
     private function generateApiKey(){
        return md5(uniqid(rand(), true));
    }

    private function isAPIExists($userid)
    {
        $stmt = $this->con->prepare("SELECT * from authentication WHERE user_id = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
}
	
