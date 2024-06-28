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


// register delivery boy
public function delivery_reg($name, $email, $password, $contact, $address,  $city, $pincode, $id_proof_type, $MainFileName, $zone_id, $status, $action,$ProofFileName)
{
    if (!$this->isemailIDExists($email))
    {

        if (!$this->isContactExists($contact))
        {
   

            $stmt = $this->con->prepare("INSERT INTO delivery_boy( `name`, `email`, `password`, `contact`, `address`, `city`, `pincode`, `id_proof_type`, `id_proof`, `zone_id`, `status`, `action`,`profile_pic`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssisssisss",$name, $email, $password, $contact, $address,  $city, $pincode, $id_proof_type, $ProofFileName, $zone_id, $status, $action,$MainFileName);
            $result = $stmt->execute();   
            $lastId = mysqli_insert_id($this->con);             
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

//delivery boy login

public function Delivery_boyLogin($email, $pass)
    {
       
        
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE email=? and BINARY password =?");
        $stmt->bind_param("ss", $email, $pass);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows;
        
    }



//get delivery boy reg data
     public function getDelivery_boy($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $deliveryboy = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $deliveryboy;
    }
    
    //get delivery boy reg data for otp login
public function getDelivery_boy_phno($phno)
{
    $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE contact=?");
    $stmt->bind_param("s", $phno);
    $stmt->execute();
    $deliveryboy = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $deliveryboy;
}

//delete delivery boy devices
    public function delete_delivery_boy_device($dbid)
    {

        $stmt = $this->con->prepare("DELETE FROM `delivery_boy_device` WHERE `db_id` = ?");
        $stmt->bind_param("i", $dbid);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            return 0;
        } else {
            return 1;
        }
    }

// insert delivery boy device

  public function insert_delivery_boy_device($dbid,$token,$type)
    {
 
        $datetime=date('Y-m-D h:i A');
         
        $stmt = $this->con->prepare("INSERT INTO `delivery_boy_device`(`db_id`, `token`, `type`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $dbid, $token, $type);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }


// view profile
    public function view_profile($userid)
    {
        $stmt = $this->con->prepare("select db_id,name,email,contact,id_proof,profile_pic from delivery_boy where db_id=?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $results = $stmt->get_result();
        $stmt->close();
        return $results;
    }


// check profile
    public function check_profile($userid)
    {
        $stmt = $this->con->prepare("select d2.id,db_id,name,email,contact,id_proof,profile_pic,d2.status from delivery_boy d1,delivery_boy_avalibility d2 where d1.db_id=d2.delivery_boy_id and d1.db_id=? ORDER by d2.id desc LIMIT 1");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $results = $stmt->get_result();
        $stmt->close();
        return $results;
    }



    // get job list 
    public function get_job_list($deliveryboy_id)
    {

        $today=date("Y-m-d");
        
        
        
        $stmt = $this->con->prepare("SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.acknowledgement,p1.collection_address,p1.priority, date_format(p1.dispatch_date,'%d-%m-%Y') as dispatch_date, date_format(p1.collection_date,'%d-%m-%Y') as collection_date, time_format(c3.start_time,'%h:%i %p') as collection_start_time, time_format(c3.end_time,'%h:%i %p') as collection_end_time,j1.job_status,c2.address_label,c2.house_no,c2.street,a1.area_name,c2.pincode,(p1.delivery_charge - p1.discount) as total_payment,p1.basic_charges,p1.ack_charges,p1.total_charges,p1.discount,p1.delivery_charge,p1.post_status,p1.payment_status FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id and db1.db_id=? and job_status='pending' and  p1.collection_date <='".$today."' order by p1.collection_date desc,FIELD(priority,'most urgent','urgent','normal')");
        // added (p1.delivery_charge - p1.discount) as total_payment which is actual delivery boys total earning in the app
        $stmt->bind_param("i", $deliveryboy_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;

    }

     // get active job list -> modified by jay
    public function get_active_job_list($deliveryboy_id)
    {
        $today=date("Y-m-d");
        $yesterday=date("Y-m-d",strtotime("-1 days"));

        $stmt = $this->con->prepare("SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.acknowledgement,p1.collection_address,p1.priority, date_format(p1.collection_date,'%d-%m-%Y') as collection_date, date_format(p1.dispatch_date,'%d-%m-%Y') as dispatch_date, time_format(c3.start_time,'%h:%i %p') as collection_start_time, time_format(c3.end_time,'%h:%i %p') as collection_end_time,j1.job_status,c2.address_label,c2.house_no,c2.street,a1.area_name,c2.pincode,p1.delivery_charge,p1.basic_charges,p1.ack_charges,p1.total_charges,p1.discount,(p1.delivery_charge - p1.discount) as total_payment,p1.post_status,p1.payment_status FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id  and db1.db_id=?  and (j1.job_status='accept')  and  p1.collection_date <='".$today."' order by p1.collection_date desc,FIELD(priority,'most urgent','urgent','normal')");
         // added (p1.delivery_charge - p1.discount) as total_payment which is actual delivery boys total earning in the app
        // and post status='pending' or 'collected'
        $stmt->bind_param("i", $deliveryboy_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;


    }
    
    
     // get post job list -> modified by jay
        // modified by Rachna 26-04-2023
    public function get_post_job_list($deliveryboy_id)
    {
        $today=date("Y-m-d");
        $yesterday=date("Y-m-d",strtotime("-1 days"));

        $stmt = $this->con->prepare("SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.acknowledgement,p1.collection_address,p1.priority, date_format(p1.dispatch_date,'%d-%m-%Y') as dispatch_date, time_format(c3.start_time,'%h:%i %p') as collection_start_time, time_format(c3.end_time,'%h:%i %p') as collection_end_time, j1.job_status,c2.address_label,c2.house_no,c2.street,a1.area_name,c2.pincode,p1.delivery_charge,p1.basic_charges,p1.ack_charges,p1.total_charges,p1.discount,(p1.delivery_charge-p1.discount) as total_payment,p1.post_status,p1.payment_status FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id  and db1.db_id=?  and (j1.job_status='transit')  order by FIELD(priority,'most urgent','urgent','normal'),p1.collection_date asc ");
        // added (p1.delivery_charge - p1.discount) as total_payment which is actual delivery boys total earning in the app
        // and post status='pending' or 'collected'
        $stmt->bind_param("i", $deliveryboy_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;


    }
    

    // get job history
    public function get_job_history($deliveryboy_id)
    {

        $today=date("Y-m-d");
        $stmt = $this->con->prepare("SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.acknowledgement,p1.collection_address,p1.priority, date_format(p1.dispatch_date,'%d-%m-%Y') as dispatch_date, time_format(c3.start_time,'%h:%i %p') as collection_start_time, time_format(c3.end_time,'%h:%i %p') as collection_end_time,j1.job_status,c2.address_label,c2.house_no,c2.street,a1.area_name,c2.pincode FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id and j1.delivery_boy_id=? and j1.job_status='dispatched'  order by p1.collection_date desc,FIELD(priority,'most urgent','urgent','normal')");
        $stmt->bind_param("i", $deliveryboy_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;


    }

    // get job detail
    public function get_job_detail($job_id)
    {

        $today=date("Y-m-d");
        $stmt = $this->con->prepare("SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.mail_type as mail_type_id,p1.acknowledgement,p1.collection_address,p1.priority, date_format(p1.dispatch_date,'%d-%m-%Y') as dispatch_date, date_format(p1.collection_date,'%d-%m-%Y') as collection_date ,p1.weight as weight_id, time_format(c3.start_time,'%h:%i %p') as collection_start_time, time_format(c3.end_time,'%h:%i %p') as collection_end_time,j1.job_status,c2.address_label,CONCAT(c2.house_no,',',c2.street,',',a1.area_name,',',c4.city_name,',',c2.pincode) as collection_address,concat(p1.house_no,',',p1.street_1,',',p1.area,',',p1.city,',',p1.pincode) as receiver_address,p1.total_charges,p1.total_payment,c2.map_location ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1,city c4,mail_type_tariff m2 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id and p1.weight=m2.id and c2.city_id=c4.city_id and j1.id=? order by p1.collection_date desc,FIELD(priority,'most urgent','urgent','normal')");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;

    }
    
     // get post job detail
    public function get_post_job_detail($job_id)
    {

        $today=date("Y-m-d");
        $stmt = $this->con->prepare("select tb.*,di1.image as barcode from (SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.mail_type as mail_type_id,p1.acknowledgement,p1.priority,p1.dispatch_date,c3.start_time as collection_start_time,c3.end_time as collection_end_time,j1.job_status,c2.address_label,CONCAT(c2.house_no,',',c2.street,',',a1.area_name,',',c4.city_name,',',c2.pincode) as collection_address,concat(p1.house_no,',',p1.street_1,',',p1.area,',',p1.city,',',p1.pincode) as receiver_address,p1.weight as weight_id,p1.total_payment,c2.map_location ,concat(m2.gm_from,'g - ',m2.gm_to,'g') as weight_range FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1,city c4,mail_type_tariff m2 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id and p1.weight=m2.id and c2.city_id=c4.city_id and j1.id=? order by j1.id desc) as tb LEFT JOIN delivery_images di1 on tb.job_id=di1.job_id and di1.status='barcode'");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;
    }

    // get total job
    public function get_total_job($deliveryboy_id,$start_date,$end_date)
    {

        $today=date("Y-m-d");
        
        
        $stmt = $this->con->prepare("SELECT j1.id as job_id,p1.id as post_id,p1.receiver_name,c1.name as sender_name,c1.contact as sender_phone,m1.mail_type,p1.acknowledgement,p1.collection_address,p1.priority, date_format(p1.dispatch_date,'%d-%m-%Y') as dispatch_date, time_format(c3.start_time,'%h:%i %p') as collection_start_time, time_format(c3.end_time,'%h:%i %p') as collection_end_time,j1.job_status,c2.address_label,c2.house_no,c2.street,a1.area_name,c2.pincode,(p1.delivery_charge - p1.discount) as total_payment,p1.basic_charges,p1.ack_charges,p1.total_charges,p1.discount,p1.delivery_charge,p1.post_status,p1.payment_status, date_format(p1.collection_date,'%d-%m-%Y') as collection_date, p1.weight FROM job_assign j1,delivery_boy db1,post p1,customer_reg c1,customer_address c2,area a1,collection_time c3,mail_type m1 where j1.post_id=p1.id and j1.delivery_boy_id=db1.db_id and p1.sender_id=c1.id and p1.collection_address=c2.ca_id and c2.area_id=a1.aid and p1.collection_time=c3.id and p1.mail_type=m1.id and db1.db_id=? and job_status='dispatched' and str_to_date(p1.dispatch_date ,'%Y-%m-%d')>=str_to_date('".$start_date."','%Y-%m-%d') and  str_to_date(p1.dispatch_date ,'%Y-%m-%d')<=str_to_date('".$end_date."','%Y-%m-%d') order by p1.collection_date desc,FIELD(priority,'most urgent','urgent','normal')");
        // added (p1.delivery_charge - p1.discount) as total_payment which is actual delivery boys total earning in the app
        $stmt->bind_param("i", $deliveryboy_id);
        $stmt->execute();
        $joblist = $stmt->get_result();
        $stmt->close();
        return $joblist;

    }
    

    //get city list
    public function get_city_list()
    {
        $stmt = $this->con->prepare("select c1.* from city c1,state s1 where c1.state=s1.state_id and c1.status='enable'");
        
        $stmt->execute();
        $results = $stmt->get_result();
        $stmt->close();
        return $results;
    }

// check if email already exists

private function isemailIDExists($email)
{

    $stmt = $this->con->prepare("SELECT db_id from delivery_boy WHERE email = ?");
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
    $stmt = $this->con->prepare("SELECT db_id from delivery_boy WHERE contact = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}



//  order info
public function order_info($job_id)
{

    
    $stmt = $this->con->prepare("select j1.id as job_id,j1.post_id,p1.sender_id,d1.name as delivery_boy_name,p1.post_status,p1.coupon_id,j1.date_time from job_assign j1,post p1,delivery_boy d1 where j1.post_id=p1.id and j1.delivery_boy_id=d1.db_id and j1.id=?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $order_list = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $order_list;
   

}
//update order status
public function update_order_status($pid,$status,$payment_status)
{

    //  $date_time =  date("Y-m-D h:i A");
    
    if($payment_status!="")
    {

        $stmt = $this->con->prepare("UPDATE `post` SET post_status = ?,payment_status=? where id = ?");
        $stmt->bind_param("ssi", $status,$payment_status, $pid);
        
    }
    else
    {
        $stmt = $this->con->prepare("UPDATE `post` SET post_status = ? where id = ?");
        $stmt->bind_param("si", $status, $pid);
       
    }
   
    
    $stmt->execute();
    $affected=$stmt->affected_rows;
    $stmt->close();
    return $affected;
}

//  post data
public function post_data($post_id)
{

    
    $stmt = $this->con->prepare("select * from post where id=?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $post_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $post_data;
   

}


//get_privacy 
public function get_privacy()
{
    $stmt = $this->con->prepare("select * from privacy_policy where `type`='delivery'");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}

// get_terms
public function get_terms()
{
    $stmt = $this->con->prepare("select * from termsandcondition where `type`='delivery'");
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
    return $results;
}


//  get amount 
public function get_amount($weight)
{

   $stmt = $this->con->prepare("SELECT amount FROM mail_type_tariff  WHERE  id=?");
  
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

  // check delivery boy location
    public function check_delivery_location($deliveryboy_id)
    {

        $stmt = $this->con->prepare("select * from delivery_boy_location where db_id=?");
        $stmt->bind_param("i", $deliveryboy_id);
        $stmt->execute();
        $location = $stmt->get_result();
        $stmt->close();
        return $location;


    }

    // add lcoation
     public function add_location($dbid,$lat,$long)
    {
 
        
         
        $stmt = $this->con->prepare("INSERT INTO `delivery_boy_location`(`db_id`, `lat`,`longi`) VALUES (?,?,?)");
        $stmt->bind_param("idd", $dbid, $lat,$long);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }
     // add lcoation
     public function update_location($loc_id,$lat,$long)
    {
 
        
        
        $stmt = $this->con->prepare("UPDATE `delivery_boy_location` SET `lat`=?,`longi`=? where id=?");
        $stmt->bind_param("ddi",  $lat,$long,$loc_id);
       
        $result = $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }

    //get weight data
    public function get_weight_data($weight_id) 
    {

        
        $stmt = $this->con->prepare("select * from mail_type_tariff where id=?");
        $stmt->bind_param("i", $weight_id);
        $stmt->execute();
        $post_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $post_data;
    

    }

     //get weight range list
     public function get_weight_range_list($mail_type_id,$weight_from) 
     {
 
        
         $stmt = $this->con->prepare("SELECT * FROM `mail_type_tariff` where gm_from>=? and mail_type=?");
         $stmt->bind_param("si", $weight_from,$mail_type_id);
         $stmt->execute();
         $post_data = $stmt->get_result();
         $stmt->close();
         return $post_data;
     
 
     }

    //---------------------------------------//

    // insert delivery boy availability

  public function delivery_boy_availability($dbid,$status)
    {
 
        $datetime=date('Y-m-D h:i A');
        


        $stmt = $this->con->prepare("INSERT INTO `delivery_boy_avalibility`(`delivery_boy_id`, `status`) VALUES (?,?)");
        $stmt->bind_param("is", $dbid,$status);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

//check delivery boy status
     public function check_delivery_boy_status($id)
    {

        
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE id =? and `status`='Enable'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;

    }


    //Method to get user details by username
    public function getUser($userid)
    {
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE userid=?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }




//change_password       //added by jay 23-04-23

    public function change_password($cur_password,$new_password,$uid,$action)
    {


        $stmt=$this->con->prepare("SELECT * FROM `delivery_boy` WHERE binary password=? and db_id=?");
        $stmt->bind_param("si", $cur_password,$uid);
        $stmt->execute();
         $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        
        
        
        if($num_rows>0)
        {
            
             $stmt1=$this->con->prepare("update delivery_boy set password=? where db_id=?");
             $stmt1->bind_param("si", $new_password,$uid);
             $stmt1->execute();
             $affected=$stmt1->affected_rows;
             $stmt1->close();
        }
       
        
        
        return $num_rows;
       
       

    }



   

//accept /reject job        //added

    public function job_action($job_id,$status)
    {

        if($status=="accept" || $status=="reject")
        {
            $stmt = $this->con->prepare("update job_assign set job_status=? where id=? and job_status='pending'");						
        }
		else
        {
            $stmt = $this->con->prepare("update job_assign set job_status=? where id=?");			
        }
      
        $stmt->bind_param("si",$status,$job_id);
        $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
		
        return $affected > 0;

    }
    
   /* public function post_status_update($post_id,$job_status)
    {
    
      $stmt = $this->con->prepare("update post set post_status=? where id=?");
        
        
        $stmt->bind_param("si",$job_status,$post_id);
        $stmt->execute();
         $affected=$stmt->affected_rows;
       
        $stmt->close();
        return $affected > 0;
    
    }*/
    
    

// update multiple job status
    public function job_update($p_id,$status,$job_id)
    {

       // added and condition by Jay 21-04-23 so that delivery boy who rejected doesnt get overwrite
        $stmt = $this->con->prepare("update job_assign set job_status=? where post_id=? and id!=? and job_status='pending'");
        
        
        $stmt->bind_param("sii",$status,$p_id,$job_id);
        $stmt->execute();
         $affected=$stmt->affected_rows;
        /*$stmt->store_result();
        $num_rows = $stmt->num_rows;*/
        $stmt->close();
        return $affected > 0;

    }
    
    // update job status -> reject
      public function job_assign_update($status,$job_id)
    {

        
        $stmt = $this->con->prepare("update job_assign set job_status=? where id=?");
        
        
        $stmt->bind_param("si",$status,$job_id);
        $stmt->execute();
         $affected=$stmt->affected_rows;
       
        $stmt->close();
        return $affected > 0;

    }

    // logout

     public function logout($id,$tokenid)
    {

         $stmt = $this->con->prepare("delete from delivery_boy_device where db_id=? and token=?");
        
        $stmt->bind_param("is", $id,$tokenid);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }

    // update delivery boy availability
 public function update_delivery_boy_availability($dbid,$reason,$status)
    {
        //$status='off';
         
        
        $stmt = $this->con->prepare("INSERT INTO `delivery_boy_avalibility`(`delivery_boy_id`, `status`,`reason`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $dbid,$status,$reason);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }

    }

     // update weight
     public function update_weight($post_id,$weight,$basic_charges,$total_charges,$total_payment,$discount,$coupon_id)
    {
       
        
        $stmt = $this->con->prepare("UPDATE `post` SET `weight`=?,basic_charges=?,total_payment=?,total_charges=?,discount=?,`coupon_id`=? where id=?");
        $stmt->bind_param("dssssii",  $weight,$basic_charges,$total_payment,$total_charges,$discount,$coupon_id,$post_id);
       
        $result = $stmt->execute();
        $affected=$stmt->affected_rows;
        $stmt->close();
        
        return $affected;
    }

    // order list
    public function order_list($job_id)
    {

        
        $stmt = $this->con->prepare("select j1.id as job_id,o1.id as order_id,o1.detail as order_detail,o2.detail as order_list from job_assign j1,ordr o1,order_detail o2 where j1.order_id=o1.id and o2.o_id=o1.id
 and j1.id=?");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $order_list = $stmt->get_result();
        $stmt->close();
        return $order_list;
       

    }
    //get weight range
    public function get_weight_range($weight,$mail_type_id)
    {

        $stmt = $this->con->prepare("SELECT id FROM mail_type_tariff  WHERE gm_from<=$weight and gm_to>=$weight and mail_type=?");
        $stmt->bind_param("i", $mail_type_id);
        $stmt->execute();
        $states = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $states;
    }



    public function fetch_user_android($o_id)
    {

        $stmt = $this->con->prepare("SELECT cd.* FROM `customer_devices` cd,post p1 where cd.cust_id=p1.sender_id and cd.device_type='android' and p1.id=? ");
        $stmt->bind_param("i", $o_id);
        $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;
    }
     public function fetch_user_ios($o_id)
    {

        $stmt = $this->con->prepare("SELECT cd.* FROM `customer_devices` cd,post p1 where cd.cust_id=p1.sender_id and  cd.device_type='ios' and p1.id=? ");
        $stmt->bind_param("i", $o_id);
        $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;
    }
// fetch vendor devices
    public function fetch_vendor_devices_android($o_id)
    {

        $stmt = $this->con->prepare("SELECT vd.* FROM `vendor_device` vd,ordr o1 where vd.vid=o1.v_id and vd.type='android' and o1.id=? ");
        $stmt->bind_param("i", $o_id);
        $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;
    }
    public function fetch_vendor_devices_ios($o_id)
    {

        $stmt = $this->con->prepare("SELECT vd.* FROM `vendor_device` vd,ordr o1 where vd.vid=o1.v_id and vd.type='ios' and o1.id=? ");
        $stmt->bind_param("i", $o_id);
        $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;
    }

    // fecth admin ios device
     public function fetch_admin_devices_ios()
    {

        $stmt = $this->con->prepare("SELECT * FROM `admin_device` where type='ios'  group by token");        
        $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;
    }

    // fecth admin android device
     public function fetch_admin_devices_android()
    {

        $stmt = $this->con->prepare("SELECT * FROM `admin_device` where type='android'  group by token");        
        $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;
    }

    public function delivery_boy_info($o_id)
    {

        $stmt = $this->con->prepare("select db.id,db.contact,db.name from job_assign j1,delivery_boy db where j1.delivery_boy_id=db.id and j1.order_id=? ");
        $stmt->bind_param("i", $o_id);
        $stmt->execute();
        $del = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $del;
    }

    // check phone number
    public function fetch_contact($phone_number)
    {


        $stmt = $this->con->prepare("SELECT contact FROM `delivery_boy` WHERE contact=?");
        $stmt->bind_param("s", $phone_number);
        $result = $stmt->execute();
        $states = $stmt->get_result();
        $stmt->close();
        return $states;

    }
    // get vendor id
    public function get_deliveryboy($contact)
    {
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE REPLACE(contact,' ', '')=?");
        $stmt->bind_param("s", $contact);
        $result = $stmt->execute();
        $faculty = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($result) {
            return $faculty;
        } else {
            return 0;
        }
    }
    // get delivery boy by email
    public function get_dboy($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy WHERE email=?");
        $stmt->bind_param("s", $email);
        $result = $stmt->execute();
        $faculty = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($result) {
            return $faculty;
        } else {
            return 0;
        }
    }

//check delivery boy availability
     public function check_delivery_boy_availability($id)
    {

        
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy_avalibility WHERE delivery_boy_id =? and `status`='on' order by id desc limit 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;

    }



    //insert delivery image
    public function add_delivery_image($job_id,$MainFileName,$status)
    {
       
        $stmt = $this->con->prepare("INSERT INTO `delivery_images`(`job_id`, `image`,`status`) VALUES (?,?,?)");
        $stmt->bind_param("iss", $job_id,$MainFileName,$status);
       
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return 1;
        } else {
            return 0;
        }

    }
// check delivery boy
     public function check_delivery_boy($id)
    {

        
        $stmt = $this->con->prepare("SELECT * FROM delivery_boy_avalibility WHERE delivery_boy_id =? order by id desc limit 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resp=$stmt->get_result();
        
        $stmt->close();
        return $resp;

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

    //  get coupon discount 
public function get_discount($coupon_id,$tot_charges,$total_del_charge)
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

                if ($tot_charges >= $min_amount) {

                    // $amount_discount1 = $total_amt * $discount;
                    // $amount_discount = $amount_discount1 / 100;
                    $amount_discount1 = $total_del_charge * $discount;
                    $amount_discount = $amount_discount1 / 100.0;

                    if ($amount_discount < $max_discount_amount) {
                        $final_discount = $amount_discount;
                        //$final_amount = $total_amt - $final_discount;
                       
                        return $final_discount;
                    } else {

                        $final_discount = $max_discount_amount;
                        //$final_amount = $total_amt - $final_discount;
                        
                        return $final_discount;
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
    
//update coupon counter
    public function update_counter($cust_id,$coupon_id)
    {

       
        $stmt = $this->con->prepare("update coupon_counter set counter=counter+1 where customer_id=? and coupon_id=?");
        $stmt->bind_param("ii",$cust_id,$coupon_id);
        $result = $stmt->execute();
       
        $stmt->close();

        if ($result) {
            return 1;
        } else {
            return 0;
        }

    }
  
}
    
