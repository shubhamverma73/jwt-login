<?php
include('config/config.php');
include('config/functions.php');
require('./vendor/autoload.php');
use \Firebase\JWT\JWT;

$jwt = blank($get->jwt);

if(!empty($jwt)) {

	try {

		$secret_key = 'shubh@321';
		$jwt_data = JWT::decode($jwt, $secret_key, array('HS256'));

		$user_id = $jwt_data->data->id;

		http_response_code(200);
		echo json_encode(array('status'=>'1', 'data'=>$jwt_data, 'message'=>'Got JWT Data', 'user_id'=> $user_id));

	} catch(Exception $ex) {

		http_response_code(401);
		echo json_encode(array('status'=>'0', 'data'=>'', 'message'=>$ex->getMessage()));
	}
}
?>