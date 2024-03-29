<?php
include "dbconnect.php";



$json_string=$GLOBALS['HTTP_RAW_POST_DATA'];

$obj=json_decode($json_string); 

$email=$obj -> email;
$password=$obj -> password;

$source=$obj -> source;
$devicetoken=$obj -> devicetoken;

$source="w";
$email="s@sohu.com";
$password="1234";

$ucode="";
$scode="";
$nickname="";
/*
if($email==null){
	echo json_encode(array('status'=>'403','err'=>'no email')); //邮件已存在
	exit;
}
if($password==null){
	echo json_encode(array('status'=>'404','err'=>'no password')); //邮件已存在
	exit;
}
if($source==null){
	echo json_encode(array('status'=>'405','err'=>'no source')); //邮件已存在
	exit;
}

*/

//--------check database--------------
$mysqli = new mysqli($mysql_server_name,$mysql_username,$mysql_password,$mysql_database); //创建mysqli实例

$sql = "select userid,salt from  accountinfo where email=?";

echo "select userid,salt from  accountinfo where email=$email";

//echo  "select userid,salt from  accountinfo where email=$email";
$stmt = $mysqli->stmt_init();
$stmt = $mysqli->prepare($sql); 
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($ucode,$salt);  
if(! $stmt->fetch()){
	echo json_encode(array('status'=>'101'));
	exit;
}
$stmt->close();
//echo "\n pass:$password, sale:$salt";
$password = convertpass($salt,$password);

$sql = "select * from  accountinfo where email=? and password=?"; 
$stmt = $mysqli->stmt_init();
$stmt = $mysqli->prepare($sql); 
$stmt->bind_param("ss", $email,$password);
$stmt->execute();
$stmt->store_result();

if(! $stmt->fetch()){
	echo json_encode(array('status'=>'101'));
	exit;
}
$stmt->close();

$ecode=randomkeys(16);


$sql="select id,nickname,seedkey from sensorinfo where userid=? ";

$stmt = $mysqli->stmt_init();
$stmt = $mysqli->prepare($sql); 
$stmt->bind_param("s", $ucode);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($scode,$nickname,$kcode);  
if(! $stmt->fetch()){
	echo json_encode(array('status'=>'202','userInfo'=>array('ucode'=>$ucode,'use'=>$ecode)));
	exit;
}
$stmt->close();

saveSession($ucode,$scode,$ecode,$source);
$now=date("Y-m-d H:i:s");
if($source=="a" && $devicetoken !=""){  //upload device token
	$sql="update accountinfo set devicetoken=?, userid=?";
	$stmt = $mysqli->stmt_init();
	$stmt = $mysqli->prepare($sql); //将sql添� 到mysqli进行预处
	$stmt->bind_param("ss",$devicetoken, $ucode);
	$stmt->execute();
	$sql="select id from devicelist where devicetoken=? and  userid=? ";
	$stmt = $mysqli->stmt_init();
	$stmt = $mysqli->prepare($sql); //将sql添� 到mysqli进行预处
	$stmt->bind_param("ss",$devicetoken, $ucode);
	$stmt->execute();
	if(! $stmt->fetch()){
		$sql="insert into  devicelist (devicetoken,userid,addtime) values (?,?,?)";
		$stmt = $mysqli->stmt_init();
		$stmt = $mysqli->prepare($sql); //将sql添� 到mysqli进行预处
		$stmt->bind_param("sss",$devicetoken, $ucode,$now);
		$stmt->execute();
	}
	
}

echo json_encode(array('status'=>'200','userInfo'=>array('ucode'=>$ucode,'scode'=>$scode,'nickname'=>$nickname,'ecode'=>$ecode,'kcode'=>$kcode)));


?>