<?php 
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); 
header('Content-Type: application/json');

$mysqli = new mysqli("localhost","root","","php_jwt");

if($mysqli->connect_errno) {

	trigger_error('Connection failed: '.$mysqli->error);

} else {
	
	//======= Common Php setting =============
	date_default_timezone_set('Asia/Kolkata');
	
	$date = date("Y-m-d");
	$time = date("H:i:s");

	//========= Get Json Data ==============
	$json = file_get_contents('php://input');
	$get = json_decode($json);

}

?>