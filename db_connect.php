<?php
ob_start();
class DB_Connect {
  public  $con1;
    // constructor
    function __construct() {
	 $this->connect();
    }
 
   
 
    // Connecting to database
    public function connect() {
       
		$con = mysqli_connect("localhost","root","","rowan_db") or die("Connection Failed...!");
		// $con = mysqli_connect("localhost","root","","rowan_db") or die("Connection Failed...!");
		
		if (!$con)
  {
  die("Connection error: " . mysqli_connect_errno());
  }
		mysqli_autocommit($con, true);
      
		$this->con1=$con;
 
        // return database handler
        return $con;
    }
	
	
 
    // Closing database connection
    public function insert($query) {
     
		$res=mysqli_query($this->con1,$query);
	
		return $res;
    }
	
	 public function update_id($query) {
        
		$res=mysqli_query($this->con1,$query);
		$id=mysqli_insert_id();
		return $id;
    }
	 public function update($query) {
     
		$res=mysqli_query($this->con1,$query);
		
		return $res;
    }
	
	 public function delete($query) {
      
		$res=mysqli_query($this->con1,$query);
		return $res;
    }
	
	public function select($query) {      		
		$res=mysqli_query($this->con1,$query);	
//		$this->con1->next_result();	
		return $res;
    }
	
	public function selectProc($proc) {      		
		$res=mysqli_query($this->con1,$proc);
		if(mysqli_more_results($this->con1))
		{
			$this->con1->next_result();
		}
		return $res;
    }
	public function selectProcWithParams($proc,$vars) {      			
		$rs = $this->con1->query($proc);
//		$this->con1->next_result();
		return $rs = $this->con1->query($vars);
    }
	
	
	 public function insert_id($query) {
     

		if($res=mysqli_query($this->con1,$query))
		{
			$id=mysqli_insert_id($this->con1);
			return $id;
		}
		else
		{
			return false;			
		}
		
		 
    }
	 public function create($query) {
        
		$res=mysqli_query($this->con1,$query);
		return $res;
    }
	function runQuery($query) {
		$result = mysqli_query($this->con1,$query);
		while($row=mysqli_fetch_assoc($result)) {
			$resultset[] = $row;
		}		
		if(!empty($resultset))
			return $resultset;
	}
	function test()
	{
		echo "sdsfsf";
			}
} 
?>