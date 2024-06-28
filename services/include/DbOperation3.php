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
 

// get all post order customerwise (by Jay 8-4-2023)
 public function get_all_order_customerwise($cid)
{
    $stmt = $this->con->prepare("SELECT * FROM `post` WHERE sender_id=? order by id desc");
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
	      $stmt1 = $this->con->prepare("SELECT p1.collection_date,p1.receiver_name,p1.order_date,d1.profile_pic, p1.weight, p1.priority, ca1.address_label as collection_address, concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time, p1.total_payment, p1.dispatch_date, d1.name, d1.contact, p1.post_status, '' as trackid FROM post p1, delivery_boy d1 , job_assign j1, customer_address ca1, collection_time ct1 where p1.id = j1.post_id and j1.delivery_boy_id = d1.db_id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and p1.id=?"); 
  
    $stmt1->bind_param("i", $pid);
    $stmt1->execute();
    $orders = $stmt1->get_result();
    $stmt1->close();
	}
	else if($row["post_status"]=='transit')
	{
	     $stmt1 = $this->con->prepare("SELECT  p1.collection_date,p1.receiver_name,p1.order_date,d1.profile_pic, p1.weight, p1.priority,ca1.address_label as collection_address,concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time, p1.total_payment, p1.dispatch_date, d1.name, d1.contact, p1.post_status, d2.image as trackid FROM post p1, delivery_boy d1 , delivery_images d2 , job_assign j1, customer_address ca1, collection_time ct1 where p1.id = j1.post_id and j1.delivery_boy_id = d1.db_id and j1.id = d2.job_id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and d2.status='barcode' and p1.id=?");
         $stmt1->bind_param("i", $pid);
         $stmt1->execute();
        $orders = $stmt1->get_result();
        $stmt1->close();
	}
	else if($row["post_status"]=='dispatch' || $row["post_status"]=='dispatched')
	{
	     $stmt1 = $this->con->prepare("SELECT  p1.collection_date,p1.receiver_name,p1.order_date,d1.profile_pic, p1.weight, p1.priority,ca1.address_label as collection_address,concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time, p1.total_payment, p1.dispatch_date, d1.name, d1.contact, p1.post_status, d2.image as trackid FROM post p1, delivery_boy d1 , delivery_images d2 , job_assign j1, customer_address ca1, collection_time ct1 where p1.id = j1.post_id and j1.delivery_boy_id = d1.db_id and j1.id = d2.job_id and ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and d2.status='barcode' and p1.id=?");
         $stmt1->bind_param("i", $pid);
         $stmt1->execute();
        $orders = $stmt1->get_result();
        $stmt1->close();
	}
    else
	{
	 $stmt1 = $this->con->prepare("SELECT  p1.collection_date,p1.receiver_name,p1.order_date,'' as profile_pic, p1.weight, p1.priority,ca1.address_label as collection_address, concat(time_format(ct1.start_time, '%h:%i %p'),' - ', time_format(ct1.end_time, '%h:%i %p')) as collection_time,  p1.total_payment, p1.dispatch_date, '' as name, '' as contact, p1.post_status, '' as trackid FROM post p1, customer_address ca1, collection_time ct1 where ca1.ca_id = p1.collection_address and ct1.id = p1.collection_time and p1.id=?");
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
        $stmt = $this->con->prepare("SELECT * FROM customer_reg WHERE contact=?");
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

// get_terms
public function get_terms()
{
    $stmt = $this->con->prepare("select * from termsandcondition where `type`='user'");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

// user login

public function customerLogin($email, $pass)
{
   
    
    $stmt = $this->con->prepare("SELECT * FROM customer_reg WHERE email=? and BINARY password =?");
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
public function get_amount($weight,$mail_type)
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
    
   
    
    $stmt = $this->con->prepare("SELECT m2.amount FROM mail_type m1,mail_type_tariff m2 WHERE m2.mail_type=m1.id and m2.gm_from<=$weight and m2.gm_to>=$weight and  m1.id=?");
    $stmt->bind_param("i", $mail_type);
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
            if(strtok($this->con-> error,  ':')=="Cannot delete or update a parent row")
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

    //check if rating is already done
    if (!$this->isreviewexists($post_id)) {
       
    
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
    else{
     
        $action='updated';
      
        $stmt = $this->con->prepare("update post_review set rating=?,review=?,action=? where post_id=?");
        $stmt->bind_param("dssi", $rating,$review,$action,$post_id);

        $result = $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        return 1;

    }
 }

 // get_notifications
public function get_notifications($userid)
{
    $stmt = $this->con->prepare("SELECT n1.id,n1.noti_type,n1.noti_type_id as post_id,n1.msg,n1.dt as date_time,CONCAT(n1.msg,' on ',DATE_FORMAT(n1.dt, '%d-%m-%Y %h:%i %p')) as notif_msg FROM `notification` n1,post p1,customer_reg c1 WHERE n1.noti_type_id=p1.id and p1.sender_id=c1.id and (n1.noti_type='post_accepted' or n1.noti_type='post_in_transit' or n1.noti_type='post_dispatched') and c1.id=?
 union 
 SELECT id,notification_type as noti_type,'0' as post_id,msg,date_time,CONCAT(msg,' on ',DATE_FORMAT(date_time, '%d-%m-%Y %h:%i %p')) as notif_msg FROM notification_center where case WHEN notification_type='specific_user' then user_ids=? end
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


private function isreviewexists($post_id)
{

    $stmt = $this->con->prepare("SELECT * from post_review WHERE post_id = ?  ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
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


public function logout($id,$device_token,$device_type)
{

   
    $stmt = $this->con->prepare("DELETE FROM `customer_devices` WHERE `cust_id`=? and `device_token`=? and `device_type`=?");

    $stmt->bind_param("iss", $id, $device_token,$device_type);
    $result = $stmt->execute();
    $stmt->close();
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}
//------------------------------------//

    
    // get student data
    public function getStudent($userid)
    {
        
        $stmt = $this->con->prepare("SELECT s1.*,b1.id as batch_id,b3.name as branch_name,b3.location as branch_location FROM `student` s1,batch b1,batch_assign b2,branch b3 WHERE b2.student_id=s1.sid and b2.batch_id=b1.id and b1.branch_id=b3.id and  s1.user_id=?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $stu = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $stu;
    }

    

    // faculty login

    public function facultyLogin($userid, $pass)
    {
        $stmt = $this->con->prepare("SELECT * FROM faculty WHERE uid=? and BINARY password =?");
        $stmt->bind_param("ss", $userid, $pass);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
        
    }
    // get faculty data
    public function getFaculty($userid)
    {
        $stmt = $this->con->prepare("SELECT f1.*,b1.id as batch_id,b2.name as branch_name,b2.location as branch_location FROM faculty f1,batch b1, branch b2 where b1.faculty_id=f1.id and b1.branch_id=b2.id and f1.uid=?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $stu = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $stu;
    }

    //add faculty device
    public function insert_faculty_device($userid,$token,$type)
    {
 
       
         
        $stmt = $this->con->prepare("INSERT INTO `faculty_device`(`f_id`, `device_token`, `device_type`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $userid, $token, $type);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }



// get batch list
     public function batch_list($faculty_id)
    {
        $stmt = $this->con->prepare("SELECT b1.*,c1.coursename,f1.name as faculty_name FROM batch b1,course c1,faculty f1 where b1.course_id=c1.courseid and b1.faculty_id=f1.id and  faculty_id=? ");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $faculty = $stmt->get_result();
        $stmt->close();
        return $faculty;
    }

// get student list
public function student_list($batch_id)
{
    $stmt = $this->con->prepare("SELECT s1.sid,s1.name,s1.gender,s1.education,s1.stu_type,s1.phone FROM batch b1,batch_assign b2,student s1 where b2.batch_id=b1.id and b2.student_id=s1.sid and  b2.batch_id=? ");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $faculty = $stmt->get_result();
    $stmt->close();
    return $faculty;
}


//add student attendence from faculty side
public function faculty_attendence($batch_id,$stu_id,$attendence,$remark)
{

    $date=date('Y-m-d');


    $stmt = $this->con->prepare("update attendance set faculty_attendance=?, remark=? where student_id=? and batch_id=? and dt=?");
    $stmt->bind_param("ssii", $attendence,$remark,$stu_id,$batch_id,$date);
   
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}

//add student attendence from stu side
public function stu_attendence($batch_id,$stu_id,$attendence)
{

    $date=date('Y-m-d');

    $stmt = $this->con->prepare("update attendance set stu_attendance=? where student_id=? and batch_id=? and dt=?");
    $stmt->bind_param("ssii", $attendence,$stu_id,$batch_id,$date);
   
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}

//skil list

 public function skill_list()
{
    $stmt = $this->con->prepare("SELECT * from skill");
    
    $stmt->execute();
    $skill = $stmt->get_result();
    $stmt->close();
    return $skill;
}



//course list

 public function course_list()
{
    $stmt = $this->con->prepare("SELECT * from course");
    
    $stmt->execute();
    $course = $stmt->get_result();
    $stmt->close();
    return $course;
}





// get book list from course
 public function book_list($course_id)
{
    $stmt = $this->con->prepare("SELECT * from books where courseid=? ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $book = $stmt->get_result();
    $stmt->close();
    return $book;
}

// get chapter list from book
 public function chapter_list($book_id)
{
    $stmt = $this->con->prepare("SELECT * from chapter where book_id=? ");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $chapter = $stmt->get_result();
    $stmt->close();
    return $chapter;
}

// get exercise list from book & chapter
public function exercise_list($book_id,$chapter_id)
{
    $stmt = $this->con->prepare("SELECT * from exercise where book_id=? and chap_id=? ");
    $stmt->bind_param("ii", $book_id,$chapter_id);
    $stmt->execute();
    $exercise = $stmt->get_result();
    $stmt->close();
    return $exercise;
}


// get skills progress
 public function skill_status($stu_id)
{
    $stmt = $this->con->prepare("SELECT count( if(s1.skill='grammer',1,null)) as grammer_total,count( if(s1.skill='grammer' and `status`!='pending',1,null)) as grammer_completed,count(case s1.skill when 'vocabulary'  then 1 else null end) as vocabulary_total,count( if(s1.skill='vocabulary' and `status`!='pending',1,null)) as vocabulary_completed,count(case s1.skill when 'pronunciation' then 1 else null end) as pronunciation_total,count( if(s1.skill='pronunciation' and `status`!='pending',1,null)) as pronunciation_completed,count(case s1.skill when 'writing' then 1 else null end) as writing_total,count( if(s1.skill='writing' and `status`!='pending',1,null)) as writing_completed,count(case s1.skill when 'spelling' then 1 else null end) as spelling_total,count( if(s1.skill='spelling' and `status`!='pending',1,null)) as spelling_completed,count(case s1.skill when 'reading' then 1 else null end) as reading_total,count( if(s1.skill='reading' and `status`!='pending',1,null)) as reading_completed,count(case s1.skill when 'speaking' then 1 else null end) as speaking_total,count( if(s1.skill='speaking' and `status`!='pending',1,null)) as speaking_completed,count(case s1.skill when 'listening' then 1 else null end) as listening_total,count( if(s1.skill='listening' and `status`!='pending',1,null)) as listening_completed,count(case s1.skill when 'presentation' then 1 else null end) as presentation_total,count( if(s1.skill='presentation' and `status`!='pending',1,null)) as presentation_completed FROM `stu_assignment` s1,exercise e1 where s1.exercise_id=e1.eid  and s1.stu_id=? ");
    $stmt->bind_param("i", $stu_id);
    $stmt->execute();
    $exercise = $stmt->get_result();
    $stmt->close();
    return $exercise;
}


// roadmap
public function roadmap($stu_id)
{
    $stmt = $this->con->prepare("SELECT b1.* from student s1,books b1,stu_course sc where sc.stu_id=s1.sid and sc.course_id=b1.courseid and s1.sid=? ");
    $stmt->bind_param("i", $stu_id);
    $stmt->execute();
    $books = $stmt->get_result();
    $stmt->close();
    return $books;
}
public function roadmap_exercise_count($stu_id,$book)
{
    $stmt = $this->con->prepare("SELECT b1.*,count( s1.skill) as total_skill,count( if( s1.status!='pending',1,null)) as completed_skill FROM `stu_assignment` s1,exercise e1,books b1 where s1.exercise_id=e1.eid and s1.book_id=b1.bid and s1.stu_id=? and b1.bid=? group by s1.book_id order by s1.book_id ");
    $stmt->bind_param("ii", $stu_id,$book);
    $stmt->execute();
    $exercise = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $exercise;
}

// student wise chapter_list
 public function stu_chapter_list($stu_id,$book_id)
{
    $stmt = $this->con->prepare("SELECT s1.*,c1.chapter_name FROM stu_assignment s1, chapter c1 where s1.chap_id=c1.cid and s1.stu_id=? and s1.book_id=?");
    $stmt->bind_param("ii", $stu_id,$book_id);
    $stmt->execute();
    $exercise = $stmt->get_result();
    $stmt->close();
    return $exercise;
}


// banner
 public function banner()
{
    $stmt = $this->con->prepare("SELECT * FROM (select * from motivation where `type`='image' and status='enabled' and banner_display='on' union (select * from motivation where `type`='video' and status='enabled' and banner_display='on' order by mid desc limit 1)) as tbl1 ");
    
    $stmt->execute();
    $exercise = $stmt->get_result();
    $stmt->close();
    return $exercise;
}

// edit stu profile
public function edit_stu_profile($uid,$email,$pic)
{
    
    if($pic!="")
    {
        $stmt = $this->con->prepare("update student set email=?,pic=? where sid=? ");
        $stmt->bind_param("ssi",$email,$pic,$uid);
    }
    else
    {
        $stmt = $this->con->prepare("update student set email=? where sid=? ");
        $stmt->bind_param("si",$email,$uid);
    }
    
    
    $result=$stmt->execute();
    
    $stmt->close();
    if($result)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

// update faculty profile
public function edit_faculty_profile($uid,$email,$pic)
{

    if($pic!="")
    {
        $stmt = $this->con->prepare("update faculty set email=?,profilepic=? where id=? ");
        $stmt->bind_param("ssi",$email,$pic,$uid);
    }
    else
    {
        $stmt = $this->con->prepare("update faculty set email=? where id=? ");
        $stmt->bind_param("si",$email,$uid);
    }
    
    
    $result=$stmt->execute();
    
    $stmt->close();
    if($result)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

//student logout


//faculty logout
public function faculty_logout($uid, $device_token,$device_type)
{
    $stmt = $this->con->prepare("DELETE FROM `faculty_device` WHERE `f_id`=? and `device_token`=? and `device_type`=?");

    $stmt->bind_param("iss", $uid, $device_token,$device_type);
    $result = $stmt->execute();
    $stmt->close();
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}


//student password change
public function stu_password_update($uid, $password)
{
    $stmt = $this->con->prepare("update student set password=? where sid=?");

    $stmt->bind_param("si",$password,$uid);
    $result = $stmt->execute();
    $stmt->close();
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}

public function faculty_password_update($uid, $password)
{
    $stmt = $this->con->prepare("update faculty set password=? where id=?");

    $stmt->bind_param("si", $password,$uid);
    $result = $stmt->execute();
    $stmt->close();
    if ($result) {
        return 1;
    } else {
        return 0;
    }
}



  //---------- MyKapot--------------//  

//Method to register a new User
    public function do_reg_customer($email, $name, $contact, $password,$gender,$profile_for)
    {

        $status='Enabled';
        $operation='Added';
        $otp_verification="unverified";
        if (!$this->isemailIDExists($email)) {


            if (!$this->isContactExists($contact)) {

                
              
                $stmt = $this->con->prepare("INSERT INTO `register`(`fname`, `email`, `contact`, `password`, `gender`,`profile_for`,`status`,`otp_verification`,`operation`) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssssss", $name,$email,$contact,$password,$gender,$profile_for,$status,$otp_verification,$operation);
                $result = $stmt->execute();
                $stmt->close();
                if ($result) {
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 3;
            }
        } else {
            return 2;
        }
    }

    // add aid user device
    public function insert_aid_user_device($userid,$token,$type)
    {
 
       
         
        $stmt = $this->con->prepare("INSERT INTO `aid_user_devices`( `cid`, `token`, `type`)VALUES (?,?,?)");
        $stmt->bind_param("iss", $userid, $token, $type);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }


     // add user via email
    public function reg_user_aid($email)
    {
 
        $notif=1;
        $verified="no";
        
        $stmt = $this->con->prepare("select * from aid_user where user=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result=$stmt->get_result();
        $num_rows = mysqli_num_rows($result);
        $stmt->close();
        if($num_rows>0)
        {
            
            $user_data=mysqli_fetch_array($result);
            $lastId=$user_data["srno"];
           

        }
        else
        {
            $stmt = $this->con->prepare("INSERT INTO `aid_user`( `user`, `notification`, `otp_verified`)VALUES (?,?,?)");
            $stmt->bind_param("sis", $email, $notif, $verified);
           
            $result = $stmt->execute();
            $stmt->store_result();
            $lastId = mysqli_insert_id($this->con);
            $stmt->close();
        }

         
        
        if ($lastId>0) {
            return $lastId;
        } else {
            return 0;
        }
    }
 
    // add user via contact
    public function reg_user_aid_contact($phone)
    {
 
        $notif=1;
        $verified="yes";
        
        $stmt = $this->con->prepare("INSERT INTO `aid_user`( `contact`, `notification`, `otp_verified`)VALUES (?,?,?)");
        $stmt->bind_param("sis", $phone, $notif, $verified);
       
        $result = $stmt->execute();
        $stmt->store_result();
        $lastId = mysqli_insert_id($this->con);
        $stmt->close();
         
        
        if ($lastId>0) {
            return $lastId;
        } else {
            return 0;
        }
    }

    // verify otp
     public function verify_otp($user,$otp)
    {
        $stmt = $this->con->prepare("SELECT *  from aid_otp WHERE user_id = ? and  otp=?");
        $stmt->bind_param("ss", $user,$otp);
        $stmt->execute();
        $user=$stmt->get_result();
      
        $stmt->close();
        return $user;
    }

    

    // insert user otp
    public function insert_user_otp($user,$otp)
    {
 
       
        $date=date("d/m/Y");
        $time=date("H:i");
        $stmt = $this->con->prepare("INSERT INTO `aid_otp`( `user_id`, `otp`, `date`,`time`)VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $user, $otp, $date,$time);
       
        $result = $stmt->execute();
        
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }


    // update user status
    public function update_user($user)
    {
        $stmt = $this->con->prepare("update aid_user set verified='yes' where srno=?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $user=$stmt->get_result();
      
        $stmt->close();
        return $user;
    }
   

    public function getUser_with_phone($phone_number)
    {
        $stmt = $this->con->prepare("SELECT * FROM register WHERE contact=?");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user;
    }

     public function getUserData($user_id)
    {
        $stmt = $this->con->prepare("SELECT * FROM register WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user;
    }

      public function check_user($email,$contact)
    {
        $stmt = $this->con->prepare("SELECT *  from register WHERE email = ? or contact=?");
        $stmt->bind_param("ss", $email,$contact);
        $stmt->execute();
        $user=$stmt->get_result();
      
        $stmt->close();
        return $user;
    }



     public function UserLogin($userid, $pass)
    {
       
        
        $stmt = $this->con->prepare("SELECT * FROM register WHERE email=? and BINARY password =?");
        $stmt->bind_param("ss", $userid, $pass);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
        
    }



    public function insert_device_type($reg_from, $fcm_token, $user_id, $user_type)
    {
        
        $stmt = $this->con->prepare("INSERT INTO `customer_devices`(`cid`, `token`, `type`,`user_type`) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $user_id, $fcm_token, $reg_from,$user_type);

        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

    // Add API key for user
    public function add_APIkey($userid){

         $apikey = $this->generateApiKey(); 
        if ($this->isAPIExists($userid)>0) {
                    

                $stmt = $this->con->prepare("update authentication set api_key=? where user_id=?");
                $stmt->bind_param("si", $apikey, $userid);
                $stmt->execute();
                $affected=$stmt->affected_rows;
                $stmt->close();  
                return $affected > 0;

           
        }
        else
        {
            $stmt = $this->con->prepare("INSERT INTO `authentication`(`user_id`, `api_key`) VALUES (?,?)");
                $stmt->bind_param("is",$userid,$apikey);
                $result = $stmt->execute();
                $stmt->close();
                if($result){
                    return true;
                }
                else
                {
                    return false;
                }
        }
    }

    // get api key for user
     public function getAPIkey($userid)
    {
        $stmt = $this->con->prepare("SELECT * FROM authentication WHERE user_id=?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user;
    }

    //add user device
    public function insert_user_device($userid,$token,$type)
    {
 
       
         
        $stmt = $this->con->prepare("INSERT INTO `user_devices`(`user_id`, `device_token`, `device_type`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $userid, $token, $type);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

    // check phone number

     public function check_phone_number($phone_number,$phone_code)
    {
        $phone_number="'%".$phone_number."%'";
        
        $stmt = $this->con->prepare("SELECT * FROM `external_users` where phone LIKE ".$phone_number." and phone_code=?");
        $stmt->bind_param("s", $phone_code);
        $result = $stmt->execute();
        $user = $stmt->get_result();
        $stmt->close();
        return $user;

    }

    // add user address
    public function add_address($name,$phone,$address,$pincode,$qty,$email,$country,$phone_code,$reg_from)
    {
 
       
        // echo "INSERT INTO `external_users`( `name`, `phone`, `address`, `pincode`,`quantity`) VALUES ($name,$phone,$address,$pincode,$qty)";
        $stmt = $this->con->prepare("INSERT INTO `external_users`( `name`,`phone_code`, `phone`,`email`, `address`, `pincode`,`country`,`quantity`,`reg_from`) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssiis", $name,$phone_code,$phone,$email,$address,$pincode,$country,$qty,$reg_from);
       
        $result = $stmt->execute();
        $stmt->store_result();
        $lastId = mysqli_insert_id($this->con);
        $stmt->close();
        
        
        return  $lastId;
        
    }

    // get external user order data
     public function get_user_order($cid)
    {
        
        
        $stmt = $this->con->prepare("SELECT * FROM `external_orders` where cid=? order by id desc limit 1");
        $stmt->bind_param("i", $cid);
        $result = $stmt->execute();
        $user = $stmt->get_result();
        $stmt->close();
        return $user;

    }

    // uodate address
    public function update_address($name,$phone,$address,$pincode,$qty,$email,$country,$phone_code,$reg_from)
    {

        
        $stmt = $this->con->prepare("UPDATE `external_users` SET name=?,phone_code=?,email=?,address=?,pincode=?,country=?,quantity=?,reg_from=? where phone=? ");
        $stmt->bind_param("sssssiiss",$name,$phone_code,$email,$address,$pincode,$country,$qty,$reg_from,$phone);
        $stmt->execute();
        $affected=$stmt->affected_rows;
        
        $stmt->close();
        return $affected;

    }


    // add external user order
    public function add_order($cid,$product,$feedback,$date,$order_from)
    {
 
       
        $qty=2;
        $status='Pending';
        $stmt = $this->con->prepare("INSERT INTO `external_orders`( `cid`, `product`, `qty`,`feedback`, `status`,`order_from`,`delivery_date`) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("isissss", $cid,$product,$qty,$feedback,$status,$order_from,$date);
       
        $result = $stmt->execute();
        $lastId = mysqli_insert_id($this->con);
        $stmt->close();
        
        if ($result) {
            $noti_type='external_order_confirm';
              $v_id=345;
              $msg="You have received an order";
              $sender_type="user";
              $status=1;
              $playstatus=1;
              $date_time=Date('m/d/Y h:i a');
             
            $stmt3 = $this->con->prepare("INSERT INTO `notification`( `order_id`, `type`, `v_id`, `user_id`, `msg`, `sender_type`, `status`, `playstatus`, `adminstatus`, `adminplaystatus`, `datetime`)  VALUES (?,?,?,?,?,?,?,?,?,?,?)");
              $stmt3->bind_param("isiissiiiis", $lastId,$noti_type,$v_id,$cid,$msg,$sender_type,$status,$playstatus,$status,$playstatus,$date_time);    
             
              $result3 = $stmt3->execute();
              $stmt3->close();
            return 1;
        } else {
            return 0;
        }
    }

    // add food request
    public function add_food_request($reference_contact,$child_below_six,$children,$adults,$senior_citizen,$needy_contact,$order_type,$lat,$long,$address,$distance,$image,$image2,$date,$time,$kitchen_id)
    {
 
       
        
      
        $order_from='app';
        $stmt = $this->con->prepare("INSERT INTO `aid`( `reference_contact`, `child_below_six`, `children`, `adults`, `senior_citizen`, `needy_contact`, `order_type`, `lat`, `longi`,`address`,`distance`, `image`,`image2`,`delivery_date`,`delivery_time`,`kitchen_id`,`order_from`) VALUES  (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("siiiissssssssssis", $reference_contact,$child_below_six,$children,$adults,$senior_citizen,$needy_contact,$order_type,$lat,$long,$address,$distance,$image,$image2,$date,$time,$kitchen_id,$order_from);
       
        $result = $stmt->execute();
        $lastId = mysqli_insert_id($this->con);
        $stmt->close();
        
        if ($result) {
            
            return $lastId;
        } else {
            return 0;
        }
    }

    // add advance seva
     public function add_advance_seva($reference_contact,$date,$time)
    {
 
       
        $current_date=date("d/m/Y");
        $current_time=date("h:i a");
        $noti=1;
        $playstatus=1;
       
        $stmt = $this->con->prepare("INSERT INTO `aid_advance_seva`( `reference_contact`, `delivery_date`,`delivery_time`,`punch_date`,`punch_time`,`notification_status`,`play_status`) VALUES  (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssii", $reference_contact,$date,$time,$current_date,$current_time,$noti,$playstatus);
       
        $result = $stmt->execute();
        $lastId = mysqli_insert_id($this->con);
        $stmt->close();
        
        if ($result) {
            
            return $lastId;
        } else {
            return 0;
        }
    }



    public function add_aid_notification($orderid)
    {

        $msg="You have received Food request";
        $status=1;
         $stmt = $this->con->prepare("INSERT INTO `aid_notification`( `order_id`, `msg`, `status`,`playstatus`) VALUES  (?,?,?,?)");
        $stmt->bind_param("isii", $orderid,$msg,$status,$status);
       
        $result = $stmt->execute();
        
        $stmt->close();
        
        if ($result) {
            
            return 1;
        } else {
            return 0;
        }
    }

    public function add_insterested_seva($phone)
    {

        $date_time=date("d/m/Y H:i");
       
        $notif=1;

        $stmt1=$this->con->prepare("select * from seva where phone=?");
        $stmt1->bind_param("s",$phone);
        
        $stmt1->execute();

        $res_stmt1 = $stmt1->get_result();
        $num_rows=mysqli_num_rows($res_stmt1);
        $stmt1->close();
        if($num_rows>0)
        {
            return 2;
        }
        else
        {
            $stmt = $this->con->prepare("INSERT INTO `seva`( `phone`, `notification`,`date_time`) VALUES  (?,?,?)");
            $stmt->bind_param("sis", $phone,$notif,$date_time);
           
            $result = $stmt->execute();
            
            $stmt->close();
            
            if ($result) {
                
                return 1;
            } else {
                return 0;
            }
        }

        
    }

    // get near by kitchen
    public function find_kitchen($pincode)
    {

        
        // find city from pincode
         $url = str_replace(" ","","https://api.postalpincode.in/pincode/".$pincode);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);

        $curlresp=json_decode($result);

        //print_r($curlresp);

        if($curlresp[0]->Status=="Success")
        { 
            $postal=$curlresp[0]->PostOffice;
            //print_r($postal);
            $city_name=$postal[0]->District;

            $stmt = $this->con->prepare("SELECT * FROM `aid_kitchen` k1,city c1 WHERE k1.city=c1.id and soundex(?) = soundex(c1.city_name) ");
            $stmt->bind_param("s", $city_name);
           
            $stmt->execute();

            $result = $stmt->get_result();
            $num_rows=mysqli_num_rows($result);
            $stmt->close();
            
            if ($num_rows>0) {
                
                return $result;
            } else {
                // isnert city & pincode to db
                $date_time=date("d/m/Y H:i");
       
                $stmt = $this->con->prepare("INSERT INTO `aid_kitchen_search`(`pincode`, `city_name`, `date_time`) VALUES (?,?,?)");
                $stmt->bind_param("sss", $pincode,$city_name,$date_time);
               
                $result=$stmt->execute();

                
                $stmt->close();

                return 0;
            }
        }
        else
        {
            return 0;

        }
    }



    // get near by kitchen
    public function find_kitchen_from_city($city_name,$pincode)
    {

        
       
            $stmt = $this->con->prepare("SELECT * FROM `aid_kitchen` k1,city c1 WHERE k1.city=c1.id and soundex(?) = soundex(c1.city_name) ");
            $stmt->bind_param("s", $city_name);
           
            $stmt->execute();

            $result = $stmt->get_result();
            $num_rows=mysqli_num_rows($result);
            $stmt->close();
            
            if ($num_rows>0) {
                
                return $result;
            } else {
                // isnert city & pincode to db
                $date_time=date("d/m/Y H:i");
       
                $stmt = $this->con->prepare("INSERT INTO `aid_kitchen_search`(`pincode`, `city_name`, `date_time`) VALUES (?,?,?)");
                $stmt->bind_param("sss", $pincode,$city_name,$date_time);
               
                $result=$stmt->execute();

                
                $stmt->close();

                return 0;
            }
        
    } 
    // add contact enquiry
     public function add_contact($name,$email,$mobile,$message,$address,$pincode)
    {
 
       
       
       
        $stmt = $this->con->prepare("INSERT INTO `aid_contact`(`name`, `email`, `mobile`, `message`,`address`,`pincode`) VALUES  (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $name,$email,$mobile,$message,$address,$pincode);
       
        $result = $stmt->execute();
        
        $stmt->close();
        
        if ($result) {
            
            return 1;
        } else {
            return 0;
        }
    }
    
    //update customer status
    public function update_customerstatus($mobile)
    {

        $stmt = $this->con->prepare("UPDATE `register` set `otp_verification` = 'verified' where `contact` = ? ");
        $stmt->bind_param("s", $mobile);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            return 0;
        } else {
            return 1;
        }
    }

    // get all counter 
    public function get_counters()
    {

        
        $stmt = $this->con->prepare("SELECT count(*) as donation FROM `external_orders` where donation>0  ");
        
        $result = $stmt->execute();
        $country = $stmt->get_result();
        $stmt->close();
        return $country;

    }
    // get kitchen count
    public function get_kitchen_count()
    {

        
        $stmt = $this->con->prepare("SELECT count(*) as kitchen FROM `aid_kitchen`  ");
        
        $result = $stmt->execute();
        $kitchn = $stmt->get_result();
        $stmt->close();
        return $kitchn;

    }

    // get meal count
    public function get_meal_count()
    {

        
        $stmt = $this->con->prepare("SELECT count(*) as meals FROM `aid` where LOWER(`status`)='delivered'  ");
        
        $result = $stmt->execute();
        $meal = $stmt->get_result();
        $stmt->close();
        return $meal;

    }

    // get volunteer count
    public function get_volunteer_count()
    {

        
        $stmt = $this->con->prepare("SELECT count(*) as volunteers FROM `seva`  ");
        
        $result = $stmt->execute();
        $volunteer = $stmt->get_result();
        $stmt->close();
        return $volunteer;

    }

    // get banners
    public function get_banner()
    {

        $stmt = $this->con->prepare("SELECT * FROM `aid_banner` where status='Enable' ");
        
        $result = $stmt->execute();
        $country = $stmt->get_result();
        $stmt->close();
        return $country;

    }

    // get all country list
    public function get_allCountry()
    {

        $stmt = $this->con->prepare("SELECT * FROM `country` ");
        
        $result = $stmt->execute();
        $country = $stmt->get_result();
        $stmt->close();
        return $country;

    }

    // get all state
    public function get_allState()
    {

        $stmt = $this->con->prepare("SELECT id,name FROM `states` ");
        
        $result = $stmt->execute();
        $state = $stmt->get_result();
        $stmt->close();
        return $state;

    }

    // get country wise state
    public function get_country_state($country_id)
    {

        $stmt = $this->con->prepare("SELECT id,name FROM `states` where country_id=? ");
         $stmt->bind_param("i", $country_id);
        $result = $stmt->execute();
        $state = $stmt->get_result();
        $stmt->close();
        return $state;

    }

    // get state wise city
    public function get_state_city($state_id)
    {

        $stmt = $this->con->prepare("SELECT id,city_name as name FROM `city` where state_id=? ");
         $stmt->bind_param("i", $state_id);
        $result = $stmt->execute();
        $city = $stmt->get_result();
        $stmt->close();
        return $city;

    }

    // get all religion list
    public function get_allReligion()
    {

        $stmt = $this->con->prepare("SELECT * FROM `religion` ");
        
        $result = $stmt->execute();
        $religion = $stmt->get_result();
        $stmt->close();
        return $religion;

    }


    // get religion wise caste
    public function get_religion_caste($religion_id)
    {

        $stmt = $this->con->prepare("SELECT id,caste_name as name FROM `caste` where religion=? ");
         $stmt->bind_param("i", $religion_id);
        $result = $stmt->execute();
        $caste = $stmt->get_result();
        $stmt->close();
        return $caste;

    }


    // get sub caste
    public function get_sub_caste($caste_id)
    {

        $stmt = $this->con->prepare("SELECT id,sub_caste as name FROM `sub_caste` where caste=? ");
        $stmt->bind_param("i", $caste_id);
        $result = $stmt->execute();
        $caste = $stmt->get_result();
        $stmt->close();
        return $caste;

    }
     // get all mb_language list
    public function get_allLanguages()
    {

        $stmt = $this->con->prepare("SELECT id,language_name as name FROM `languages` ");
        
        $result = $stmt->execute();
        $lang = $stmt->get_result();
        $stmt->close();
        return $lang;

    }

    public function add_id_proof($user_id,$MainFileName)
    {

        
        $stmt = $this->con->prepare("update personal_profile set id_proof=? where reg_id=? ");
        $stmt->bind_param("si",$MainFileName,$user_id);
        $stmt->execute();
        $affected=$stmt->affected_rows;
        
        $stmt->close();
        return $affected > 0;

    }

    // get user personal details
    public function get_personal_data($user_id)
    {
        $stmt = $this->con->prepare("SELECT * FROM personal_profile  WHERE reg_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result();
        $stmt->close();
        return $user;
    }

    // get user socail details
    public function get_social_data($user_id)
    {
        $stmt = $this->con->prepare("SELECT * FROM social_profile  WHERE reg_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result();
        $stmt->close();
        return $user;
    }

    // get user career details
    public function get_career_data($user_id)
    {
        $stmt = $this->con->prepare("SELECT * FROM career_profile  WHERE reg_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result();
        $stmt->close();
        return $user;
    }
   


    // get graduation list
    public function get_graduation_list()
    {

        $stmt = $this->con->prepare("SELECT id,graduation as name FROM `graduation`  ");
        
        $result = $stmt->execute();
        $grad = $stmt->get_result();
        $stmt->close();
        return $grad;

    }

    // add user payment
     public function user_payment($user_id,$payment_type,$amount,$start_date,$end_date)
    {
 
       
         
        $stmt = $this->con->prepare("INSERT INTO `payment`(`reg_id`, `payment_type`, `amount`, `start_date`, `end_date`) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $user_id,$payment_type,$amount,$start_date,$end_date);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

    // add user trial
    public function user_trial($user_id,$payment_type,$amount,$start_date,$end_date)
    {
 
       
         
        $stmt = $this->con->prepare("INSERT INTO `trial`(`reg_id`, `payment_type`, `amount`, `start_date`, `end_date`) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $user_id,$payment_type,$amount,$start_date,$end_date);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

    // add personal profile
    public function add_personal_profile($user_id,$dob,$height,$weight,$complexion,$country_id,$state_id,$city_id,$smoking_habit,$drinking_habit,$diet_preference,$about,$thalassemia)
    {
 
           
        $stmt = $this->con->prepare("INSERT INTO `personal_profile`( `reg_id`, `dob`, `height`, `weight`, `complexion`, `country`, `state`, `city`, `smoking_habit`, `drinking_habit`, `diet_preference`, `about`,`thalassemia`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssiiisssss", $user_id,$dob,$height,$weight,$complexion,$country_id,$state_id,$city_id,$smoking_habit,$drinking_habit,$diet_preference,$about,$thalassemia);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }


    // add social profile
    public function add_social_profile($user_id,$marital_status,$mother_tongue,$languages_known,$religion,$caste,$sub_caste,$manglik,$sai_devotee)
    {
 
           
        $stmt = $this->con->prepare("INSERT INTO `social_profile`(`reg_id`, `marital_status`, `mother_tongue`,`languages_known`, `religion`, `caste`, `sub_caste`, `manglik`, `sai_devotee`) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssiiiss",$user_id,$marital_status,$mother_tongue,$languages_known,$religion,$caste,$sub_caste,$manglik,$sai_devotee);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }


    // add career profile
    public function add_education($user_id,$higher_education,$occupation,$employed_in,$income)
    {
 
           
        $stmt = $this->con->prepare("INSERT INTO `career_profile`(`reg_id`, `education`, `occupation`, `employed_in`, `income`) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss",$user_id,$higher_education,$occupation,$employed_in,$income);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }
  /*   private function isUserExists($username)
    {
        $stmt = $this->con->prepare("SELECT id from register WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
*/


     //Checking the user is valid or not by api key
    public function isValidUser($api_key) {
        $stmt = $this->con->prepare("SELECT id from authentication WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    //check user payment
    public function check_user_payment($user_id) {
        $stmt = $this->con->prepare("SELECT * from payment WHERE reg_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $payment=$stmt->get_result();
       
        $stmt->close();
        return $payment;
    }
    //check user trail
    public function check_user_trial($user_id) {
        $stmt = $this->con->prepare("SELECT * from trial WHERE reg_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $payment=$stmt->get_result();
       
        $stmt->close();
        return $payment;
    }

    // check user personal profile
    public function check_personal_profile($user_id) {
        $stmt = $this->con->prepare("SELECT * from personal_profile WHERE reg_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $user=$stmt->get_result();        
        $stmt->close();
        return $user;
    }

    // check user social profile
    public function check_social_profile($user_id) {
        $stmt = $this->con->prepare("SELECT * from social_profile WHERE reg_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $user=$stmt->get_result();        
        $stmt->close();
        return $user;
    }

    // check user career profile
    public function check_career_profile($user_id) {
        $stmt = $this->con->prepare("SELECT * from career_profile WHERE reg_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $user=$stmt->get_result();        
        $stmt->close();
        return $user;
    }

    // update personal profile
    public function update_personal_profile($user_id,$dob,$height,$weight,$complexion,$country_id,$state_id,$city_id,$smoking_habit,$drinking_habit,$diet_preference,$about,$thalassemia)
    {
 
           
        $stmt = $this->con->prepare("UPDATE `personal_profile` SET `dob`=?,`height`=?,`weight`=?,`complexion`=?,`country`=?,`state`=?,`city`=?,`smoking_habit`=?,`drinking_habit`=?,`diet_preference`=?,`thalassemia`=?,`about`=? where reg_id=?");
        $stmt->bind_param("ssssiiisssssi",$dob,$height,$weight,$complexion,$country_id,$state_id,$city_id,$smoking_habit,$drinking_habit,$diet_preference,$thalassemia,$about,$user_id);
       
        $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }

    public function update_social_profile($user_id,$marital_status,$mother_tongue,$languages_known,$religion,$caste,$sub_caste,$manglik,$sai_devotee)
    {
 
           
        $stmt = $this->con->prepare("UPDATE `social_profile` SET `marital_status`=?,`mother_tongue`=?,`languages_known`=?,`religion`=?,`caste`=?,`sub_caste`=?,`manglik`=?,`sai_devotee`=? WHERE reg_id=?");
        $stmt->bind_param("sisiiissi",$marital_status,$mother_tongue,$languages_known,$religion,$caste,$sub_caste,$manglik,$sai_devotee,$user_id);
       
        $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }

    public function update_education($user_id,$higher_education,$occupation,$employed_in,$income)
    {
 
           
        $stmt = $this->con->prepare("UPDATE `career_profile` SET `education`=?,`occupation`=?,`employed_in`=?,`income`=? WHERE reg_id=?");
        $stmt->bind_param("isssi",$higher_education,$occupation,$employed_in,$income,$user_id);
       
        $result = $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }

    public function update_register($user_id,$first_name,$last_name,$contact,$gender,$profile_for,$hide_photo)
    {
 
           
        $stmt = $this->con->prepare("UPDATE `register` SET `fname`=?,`lname`=?,`contact`=?,`gender`=?,`profile_for`=?,`hide_photo`=? WHERE id=?");
        $stmt->bind_param("ssssssi",$first_name,$last_name,$contact,$gender,$profile_for,$hide_photo,$user_id);
       
        $result = $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }

     public function uploadPhotos($uploaded_images, $reg_id,$type)
    {
        $response = array();
        $response["data"] = array();
        $product = array();
        

        //print_r($uploaded_images);
        $stmt = $this->con->prepare("insert into user_gallery ( `reg_id`, `typ`, `file_name`) values(?,?,?)");
        $stmt->bind_param("iss", $reg_id, $type,$uploaded_images);
        $result = $stmt->execute();                    
        $stmt->close();
        
        return $result;
    }


    // get user gallery
    public function get_user_gallery($user_id) {
        $stmt = $this->con->prepare("SELECT * from user_gallery WHERE reg_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user=$stmt->get_result();        
        $stmt->close();
        return $user;
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
	
