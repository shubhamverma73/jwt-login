<?php
include('config/config.php');
include('config/functions.php');
require('./vendor/autoload.php');
use \Firebase\JWT\JWT;

$email = blank($get->email);
$password = blank($get->password);

$login_query = "select * from `user` where `email`='".$email."' and status='1' ";
$login_result = $mysqli->query($login_query);
if( $login_result->num_rows > 0 ) {

	$row = $login_result->fetch_assoc();

	if(password_verify($password, $row['password'])) {

		$iat = time();
		$payload_data = array(
							'iss' => 'localhost', //Issued by
							'iat' => $iat, //Issued at
							'nbf' => $iat + 10, //Not before, means after 10 seconds we can use this token.
							'exp' => $iat + 300, //Expire at, after 300 seconds it will auto expire and can not use again, default time is 1 hour.
							'aud' => 'myuers', //For which user
							'data' => array(
											'id' => $row['id'],
											'name' => $row['name'],
											'email' => $row['email'],
											),
							);
		$secret_key = 'shubh@321';

		$jwt = JWT::encode($payload_data, $secret_key);

		//======== get data ============
		$data[] = array(
			'status' => $row['status'],
			'name' => $row['name'],
			'email' => $row['email'],
			'jwt' => $jwt
		);
		http_response_code(200);
		echo json_encode(array('status'=>'1', 'data'=>$data, 'message'=>''));
	} else {
		http_response_code(400);
		echo json_encode(array('status'=>'1', 'data'=>'', 'message'=>'Invalie username and password combination.'));
	}
} else {
	http_response_code(400);
	echo json_encode(array('status'=>'0', 'data'=>'', 'message'=>'Invalid User')); /*Account Not Approved*/
}
?>