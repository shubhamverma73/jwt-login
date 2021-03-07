<?php 
include('config/config.php');
include('config/functions.php');

$name = blank($get->name, 'Name');
$email = blank($get->email, 'email');
$password = blank($get->password, 'Password');

$exists_query = "select * from `user` where `email`='".$email."'";
$exists_result = $mysqli->query($exists_query);
if( $exists_result->num_rows < 1 ) {

	$insert_array = array(
		'password' => password_hash($password, PASSWORD_DEFAULT),
		'name' => $name,
		'email' => $email,
		'status' => '1',
		'date' => date("Y-m-d"),
		'time' => date("H:i:s"),
	);
	$success = insert($insert_array, 'user');

	if( $success > 0 ) {

		$login_query = "select * from `user` where `email`='".$email."' ";
		$login_result = $mysqli->query($login_query);
		if( $login_result->num_rows > 0 ) {

			$row = $login_result->fetch_assoc();
			$data[] = array(
				'status' => $row['status'],
				'name' => $row['name'],
				'email' => $row['email'],
				//'token' => $row['token']
			);
		}
		http_response_code(200);
		echo json_encode(array("status"=>"2", 'data'=>$data, "message"=>""));	
	} else {
		http_response_code(400);
		echo json_encode(array("status"=>"0", "data"=>'Registration failed, please try after some time', "message"=>""));
	}
} else {
	http_response_code(400);
	echo json_encode(array("status"=>"0", "data"=>'Registration failed, user already exists', "message"=>""));
}

?>