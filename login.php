<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>

<?php

$con = mysqli_connect("localhost","root","","pragmanx_english_express") ;

?>

	<form name="frm1" method="post">

		<h1 align="center">Login</h1>

        User ID: <input type="text" name="utxt" required="required"/><br/>
        Password: <input type="password" name="ptxt" required="required"/><br/>
        <input type="submit" name="login" value="Login"/><br/>
        
	</form>


	<?php
		
		if(isset($_REQUEST["login"])){
			session_start();
			
			$ui = $_REQUEST["utxt"];
			$pa = $_REQUEST["ptxt"];
			
			$qr = $con->prepare("select userid,password from admin where userid=? and BINARY password=?");
			$qr->bind_param("ss",$ui,$pa);
			$qr->execute();
			$result = $qr->get_result();
			$qr->close();
			$row=mysqli_fetch_array($result);
			
			if($row["uid"]==$ui)
			{
				$_SESSION["utype"]=$ui;
				header("location:home.php");
			}
			else
			{
				header("location:login.php?msg=Incorect UserId/Password");	
			}
			
		/*	if($ui=="admin" && $pa=="123456"){
				$_SESSION["utype"]="admin";
				header("location:home.php");	
			}
			else if($ui=="teacher" && $pa=="123456"){
				$_SESSION["utype"]="teacher";
				header("location:home.php");	
			}
			else{
				echo "Incorect UserId/Password";	
			}	*/
		}
	
	?>

</body>
</html>