<?php 

	//======== Real escape string ============
	function escape($text) {
		global $mysqli;
		return $mysqli->real_escape_string($text);
	}

	//========= Empty check ==========
	function blank($var, $name='') {
		if( empty($var) ) {
			$msg = error_code('1005');
			if( !empty($name) ){
				$msg = $msg.' ( '.$name.' ) ';
			}
			http_response_code(404);
			echo json_encode(array('status'=>'0', 'data'=>'', 'message'=>$msg)); /*Check Your Input*/
			exit;
		}
		return $var;
	}

	//========= Empty Gift check ==========
	function blank_gift($var, $name='') {
		if( empty($var) ) {
			$msg = 'Update your app from the link in notifcation';
			if( !empty($name) ){
				$msg = $msg.' ( '.$name.' ) ';
			}
			echo json_encode(array('status'=>'0', 'data'=>'', 'message'=>$msg)); /*Update your app from the link in notifcation*/
			exit;
		}
		return $var;
	}

	//========= Empty check Forgot Password ==========
	function blank_mob($var, $name='') {
		if( empty($var) ) {
			$msg = error_code('1032');
			if( !empty($name) ){
				$msg = $msg.' ( '.$name.' ) ';
			}
			echo json_encode(array('status'=>'0', 'data'=>'', 'message'=>$msg)); /*Check Your Input*/
			exit;
		}
		return $var;
	}

	//=========== Exception entry =========
	function exception($code='', $api='', $input='', $msg='', $role='', $device='') {
		$insert_array = array(
			"api" => $api,
			"json_input" => $input,
			"error_msg" => $msg,
			"retailer_code" => $code,
			"role" => $role,
			"date" => date("Y-m-d"),
			"time" => date("H:i:s"),
			"device_info" => $device,
			"ip" => $_SERVER['REMOTE_ADDR'],
		);
		$exception = insert($insert_array, 'log_exception');
		return true;
	}
	//=========== Query Exception entry =========
	function query_exception($code='', $api='', $query='', $input='', $msg='', $role='', $device='', $master_txn='') {
		$insert_array = array(
			"retailer_code" => $code,
			"role" => $role,
			"api" => $api,
			"query" => json_encode($query),
			"json_input" => $input,
			"message" => $msg,
			"date" => date("Y-m-d"),
			"time" => date("H:i:s"),
			"device_info" => $device,
			"group_txn" => $master_txn,
			"ip" => $_SERVER['REMOTE_ADDR'],
		);
		$query_exception = insert($insert_array, 'log_query');
		return true;
	}

	//======== Authentication Function =========
	function authenticate($unique_code, $token, $type) {
		global $mysqli;
		if( empty($unique_code) ) {
			echo json_encode(array('status'=>'0', 'data'=>'', 'message'=>error_code('1005')));
			exit;
		}
		if($type == 'rso') {
			$sql = "select * from `rso_user` where ".$type."_code='".$unique_code."' and `token`='".$token."' ";
		} else {
			$sql = "select * from `user` where ".$type."_code='".$unique_code."' and `token`='".$token."' ";
		}		
		$result = $mysqli->query($sql);
		if( $result->num_rows > 0 ) {
			return true;
		} else {
			$data = array('status'=>'-1', 'data'=>'', 'message'=>error_code('1006'));
			echo json_encode($data);
			exit;
		}
	}

	//========= Decrypt =====================
	function decrypt($Str, $Key) {
		$decrypted= @mcrypt_decrypt(
			MCRYPT_RIJNDAEL_128,
			$Key,
			base64_decode($Str),
			@MCRYPT_MODE_ECB
		);
		$dec_s = strlen($decrypted);
		$padding = ord($decrypted[$dec_s-1]);
		$decrypted = substr($decrypted, 0, -$padding);
		return $decrypted;
	}

	//============= Master Log txn id ============== 
	function txn_id($code, $retailer_code='', $type='', $device='') { 
		global $mysqli;
		$type = txn_type($code);
		$sql = "select `txn_id` from `log_transaction` where `txn_id` LIKE '$code%' order by `id` desc limit 1"; 
		$result = $mysqli->query($sql); 
		if( $result->num_rows > 0  ) { 
			$row = $result->fetch_assoc();
			$txid = $code.date("dmy",time()).(substr($row['txn_id'], -7)+1);
			$arr = array(
				"txn_id" => $txid,
				"type" => $type,
			);
			
			if( !empty($retailer_code) ){
	
				$insert_array = array(
					"date_created" => date("Y-m-d"), 
					"time_created" => date("H:i:s"), 
					"txn_id" => $txid, 
					"txn_type" => $type, 
					"retailer_code" => $retailer_code, 
					"role" => $type, 
					"device_info" => $device, 
					"status" => 'Pending',
					"ip_add" => $_SERVER['REMOTE_ADDR'],
				);
				$transaction = insert($insert_array, 'log_transaction'); 
			}
			
			return $arr; 
		} else { 
			$txid = $code.date("dmy",time()).'1000001';
			$arr = array(
				"txn_id" => $txid,
				"type" => $type,
			); 
			
			if( !empty($retailer_code) ){
	
				$insert_array = array(
					"date_created" => date("Y-m-d"), 
					"time_created" => date("H:i:s"), 
					"txn_id" => $txid, 
					"txn_type" => $type, 
					"retailer_code" => $retailer_code, 
					"role" => $type, 
					"device_info" => $device, 
					"status" => 'Pending',
					"ip_add" => $_SERVER['REMOTE_ADDR'],
				);
				$transaction = insert($insert_array, 'log_transaction'); 
			}
			
			return $arr;
		}
	}

	//========== txn type ==========================
	function txn_type($code) {
		global $mysqli;
		$sql = "select `type` from `m_transaction_code` where `code`='".$code."' "; 
		$result = $mysqli->query($sql); 
		if( $result->num_rows > 0  ) { 
			$row = $result->fetch_assoc();
			return $row['type']; 
		} else { 
			return $type=""; 
		}
	}

	//============= Update master txn table ========  
	function update_txn($txn_id, $type, $retailer_code, $role, $device_info, $status='', $update='') {
		if( !empty($update) ){

			$update_array = array(
				'status' => 'Complete',
			);
			$update_where = "txn_id='".$txn_id."' ";
			$transaction = update($update_array, 'log_transaction', $update_where);
			return true; 
			
		} else {
			$insert_array = array( 
				"date_created" => date("Y-m-d"), 
				"time_created" => date("H:i:s"), 
				"txn_id" => $txn_id, 
				"txn_type" => $type, 
				"retailer_code" => $retailer_code, 
				"role" => $role, 
				"device_info" => $device_info, 
				"status" => $status,
				"ip_add" => $_SERVER['REMOTE_ADDR'],
			); 
			$transaction = insert($insert_array, 'log_transaction'); 
			return true; 
		}
	}

	//=========== rso =====================
	function rso( $rso_code ) {
		global $mysqli;
		$kre = $mysqli->query("SELECT * FROM rso_user WHERE rso_code = '$rso_code' AND role = 'rso' ");
		return $kre;
	}

	//=========== retailer ================
	function retailer( $retailer_code ) {
		global $mysqli;
		$retailer = $mysqli->query("SELECT * FROM user WHERE retailer_code = '$retailer_code' AND role = 'retailer' ");
		return $retailer;
	}

	//=========== rsm =====================
	function asm( $asm_code ) {
		global $mysqli;
		$kre = $mysqli->query("SELECT * FROM rso_user WHERE rso_code = '$asm_code' AND role = 'asm' ");
		return $kre;
	}

	//========= Insert query function ======
	function insert($array, $table) {
		global $mysqli;
		$query = "";
				
		if(! is_array($array) ) {
			die("ERROR: Invalid Operation.");
		}
		foreach($array as $key => $value ) {
		  $query .= "`$key`='$value',";   		
		}
		$query = " insert into `$table` set ".substr($query, 0, -1);
		
		$mysqli->query($query) or die($mysqli->error);
		return $mysqli->insert_id;
		//return true;
	} 

	//============= Update query ==========================
	function update($array, $table, $where) {
		global $mysqli;
		$query = "";	

		if( !is_array($array) ) {
			die("Invalid Array");
		} else {
			foreach($array as $key => $value ) {
				$query .= "`$key`='$value',";   		
			}
		}
		
		if( !is_array($where) ) {
			$where = " where ".$where;
		} else {
			foreach($where as $key => $value ) {
				$where .= "`$key`='$value' and";
			}
			$where = substr($where, 0, -3);
		}

		$query = "update `$table` set ".substr($query,0,-1).$where;
		$mysqli->query($query) or die($mysqli->error);
		if( $mysqli->affected_rows > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	// ============= Error Code =============
	function error_code($code){
		global $mysqli;

		$result = $mysqli->query("select * from m_error_code where code='$code'");
		if($result->num_rows > 0){
			$row = $result->fetch_assoc();
			return $row["message"];
		} else {
			return '';
		}
	}

	// ============= Var Dump ===================
	function dump($value){
		echo "<pre>"; var_dump($value); echo "</pre>";
	}
	
	//========= Check feedback ===============
	function check_feedback($retailer_code) {

		global $mysqli;
		$data['feedback_id'] = '';
		$data['feedback_title'] = '';
		$retailer_data = retailer($retailer_code);
		$row = $retailer_data->fetch_assoc();
		
		$result = $mysqli->query("SELECT * FROM feedback WHERE end_date >= '".date("Y-m-d")."'");
		if( $result->num_rows > 0 ) {
			while($row1 = $result->fetch_assoc() ) {

				if($row1['city']!=''){
					if($row1['city'] == $row['city'] AND $row1['state'] == $row['state'] AND $row1['zone'] == $row['zone']){
						$data[] = [
							'feedback_id' => $row1['id'],
							'feedback_name' => $row1['name'],
							'start_date' => $row1['start_date'],
							'end_date' => $row1['end_date']
						];	
					}
				}

				if($row1['city'] == '' AND $row1['state'] !=''){
					if($row1['state'] == $row['state'] AND $row1['zone'] == $row['zone']){
						$data[] = [
							'feedback_id' => $row1['id'],
							'feedback_name' => $row1['name'],
							'start_date' => $row1['start_date'],
							'end_date' => $row1['end_date']
						];		
					}
				}
				
				if($row1['state'] == '' AND $row1['zone'] !='' AND $row1['zone'] !='all' ){
					if($row1['zone'] == $row['zone']){
						$data[] = [
							'id' => $row1['id'],
							'name' => $row1['name'],
							'start_date' => $row1['start_date'],
							'end_date' => $row1['end_date']
						];		
					}
				}

				if($row1['zone'] == 'all'){
					$data[] = [
						'feedback_id' => $row1['id'],
						'feedback_name' => $row1['name'],
						'start_date' => $row1['start_date'],
						'end_date' => $row1['end_date']
					];	
				}

				if( count($data) > 0 ){

					$sql = "select * from `feedback_response` where `feedback_id`='".$row1['id']."' and `retailer_id`='".$retailer_code."' ";
					$result1 = $mysqli->query($sql);
					if( $result1->num_rows == 0 ){
						$data['feedback_id'] = $row1['id'];
						$data['feedback_title'] = $row1['name'];
						return $data;
						break;
					}
				}
			}		
		}
		return $data;
	}
	
	//=========== retailer temp ================
	function retailer_temp( $retailer_code ) {
		global $mysqli;
		$retailer = $mysqli->query("SELECT * FROM user_temp WHERE retailer_code = '$retailer_code' AND role = 'retailer' ");
		return $retailer;
	}

	function get_zone($state, $city) {
		global $mysqli;
		$zone = $mysqli->query("SELECT region FROM m_citylist WHERE statename = '$state' AND cityname = '$city' ");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row["region"];
		} else {
			return false;
		}
	}
	
	function rso_by_phone( $phone ) {
		global $mysqli;
		$kre = $mysqli->query("SELECT * FROM user WHERE mobile = '$phone' AND role = 'rso' ");
		return $kre;
	}

	/* Keep json req and res */
	function keep_req_res($section = '', $json_input = '', $json_output = '', $message = '', $uniquecode = '', $device = '', $execution_time = '') {
		$insert_array = array(
			"section" => $section,
			"json_input" => $json_input,
			"json_output" => $json_output,
			'message' => $message,
			"uniquecode" => $uniquecode,
			"role" => 'rso',
			"date" => date("Y-m-d"),
			"time" => date("H:i:s"),
			"device_info" => $device,
			"execution_time" => $execution_time,
			"ip" => $_SERVER['REMOTE_ADDR'],
		);
		$exception = insert($insert_array, 'json_req_res');
		return true;
	}

	//=========== User =====================
	function user( $rso_code ) {
		global $mysqli;
		$kre = $mysqli->query("SELECT * FROM user WHERE rso_code = '$rso_code'  ");
		return $kre;
	}

	//============== Send Notification ===================
	function send_notification($rso_code, $type, $role= '') {
		global $mysqli;
		$user = rso($rso_code);
		$user_data = $user->fetch_assoc();

		$registrationIds[] = $user_data['device_token'];

		if( $type == 'update_group_request' ){
			$title = 'Beat group change requested';
			$message = $rso_data['name'].' has raised the request for changing the Beat Groups. Kindly take necessary actions';
		} elseif( $type == 'Approved' ){
			$title = 'Approved';
			$message = 'Your request for group change has been Confirmed';
		} else if( $type == 'Rejected' ){
			$title = 'Rejected';
			$message = 'Your request for group change has been Cancelled';
		} else if( $type == 'Deleted' ){
			$title = 'Todays beat has been deleted';
			$message = 'Your todays beat has been deleted by the ASM';
		} 

		$insert_array = array(
		    "role" => '',
			"date" => date("Y-m-d"),
			"title" => $title,
			"message" => $message,
			"rso_code" => $rso_code,
			"user_name" => $user_data['name'],
		);
		$notif = insert($insert_array, 'rso_notifications');

		define( 'API_ACCESS_KEY', 'AAAAwwtNpDM:APA91bEFfdUtj1PZ4iUB2kgltKJ5mDMeLlb2Z2uiOhi4xONPKJNkJiW-rsXPaLN016ngV-m-86n87uL1AKSHA1Dewp1hiAyWR_7UfR2EUotwt434-8lwIaTVVyjGjCWn3UWsA_P40Nzf' );

		$msg = array(
			'message'   => $message,
			'title'     => $title
		);
		
		$fields = array(
			'registration_ids'  => $registrationIds,
			'data'          => $msg
		);
		 	
		$headers = array(
			'Authorization: key=' . API_ACCESS_KEY,
			'Content-Type: application/json'
		);
			
		 
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		//print_r($result);
		curl_close( $ch );
	}
	
	//============== Send Notification ===================
	function send_notification_asm($rso_code, $type, $role= '') {
		global $mysqli;
		
		if($type == 'update_group_request') {
    		$rso_result = rso($rso_code);
    		$rso_data = $rso_result->fetch_assoc();
    		
    		$asm_code = $rso_data['asm_code'];
    		$asm_result = asm($asm_code);
    		$asm_data = $asm_result->fetch_assoc();
    
            $user_code = $asm_data['rso_code'];
    		$user_name = $asm_data['name'];
    		$registrationIds[] = $asm_data['device_token'];
		} else {
    		$rso_result = rso($rso_code);
    		$rso_data = $rso_result->fetch_assoc();
    		
    		$user_code = $rso_data['rso_code'];
    		$user_name = $rso_data['name'];
    		$registrationIds[] = $rso_data['device_token'];
		}

		if( $type == 'Confirmed' ){
			$title = 'Confirmed';
			$message = 'Your request for group change has been Confirmed';
		} else if( $type == 'Cancelled' ){
			$title = 'Cancelled';
			$message = 'Your request for group change has been Cancelled';
		} else if( $type == 'update_group_request' ){
			$title = 'Beat group change requested';
			$message = $rso_data['name'].' has raised the request for changing the Beat Groups. Kindly take necessary actions';
		}

		$insert_array = array(
			"role" => 'asm',
			"date" => date("Y-m-d"),
			"title" => $title,
			"message" => $message,
			"rso_code" => $user_code,
			"user_name" => $user_name,
		);
		$notif = insert($insert_array, 'rso_notifications');

		define( 'API_ACCESS_KEY', 'AAAAxCaucy8:APA91bHWCtMQxvQb6_SE2JntU4W3EAx_yUfWlsi5ghbATrxczyfpkD1iGtHPHLAkhyFRASMA0eFRtuKn9LCNiM_4viuuDxCPIc4uoLvqxydIFeHSqtjkHHDBNbCLSrlSnvXJTsO3ujpD' );

		$msg = array(
			'message'   => $message,
			'title'     => $title
		);
		
		$fields = array(
			'registration_ids'  => $registrationIds,
			'data'          => $msg
		);
		 	
		$headers = array(
			'Authorization: key=' . API_ACCESS_KEY,
			'Content-Type: application/json'
		);
			
		 
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		//print_r($result);
		curl_close( $ch );
	}

	function get_retailer_details($retailer) {
		global $mysqli;
		$zone = $mysqli->query("SELECT * FROM retailer_list WHERE retailer_code = '$retailer'");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row;
		} else {
			return false;
		}
	}

	function get_category($cat_id) {
		global $mysqli;
		$zone = $mysqli->query("SELECT name FROM category WHERE id = '$cat_id'");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row['name'];
		} else {
			return false;
		}
	}

	function get_rso_details($rso_code) {
		global $mysqli;
		$zone = $mysqli->query("SELECT * FROM rso_user WHERE rso_code = '$rso_code'");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row;
		} else {
			return false;
		}
	}

	function get_retailer_details_by_rso($rso_code) {
		global $mysqli;
		$zone = $mysqli->query("SELECT * FROM retailer_list WHERE rso_code = '$rso_code'");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row;
		} else {
			return false;
		}
	}

	function woking_days_except_sunday() {
		$number_of_days = date('t');
		$workdays = array();
		$type = CAL_GREGORIAN;
		$month = date('n'); // Month ID, 1 through to 12.
		$year = date('Y'); // Year in 4 digit 2009 format.
		$day_count = cal_days_in_month($type, $month, $year); // Get the amount of days

		//loop through all days
		for ($i = 1; $i <= $day_count; $i++) {

		    $date = $year.'/'.$month.'/'.$i; //format date
		    $get_name = date('l', strtotime($date)); //get week day
		    $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars

		    //if not a weekend add day to array
		    if($day_name != 'Sun'){
		        $workdays[] = $i;
		    }
		}
		return count($workdays);
	}

	function get_date_except_sunday_till_date() {
		$number_of_days = date('t');
		$workdays = array();
		$type = CAL_GREGORIAN;
		$month = date('n'); // Month ID, 1 through to 12.
		$year = date('Y'); // Year in 4 digit 2009 format.

		//loop through all days
		for ($i = 1; $i <= date('d'); $i++) {

		    $date = $year.'/'.$month.'/'.$i; //format date
		    $get_name = date('l', strtotime($date)); //get week day
		    $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars

		    //if not a weekend add day to array
		    if($day_name != 'Sun'){
		        $workdays[] = $i;
		    }
		}
		return count($workdays);
	}

	function get_rso_under_asm($asm_code) {
	    global $mysqli;
		$sql = "select rso_code from rso_user where asm_code='".$asm_code."' and role='rso'";
		$result = $mysqli->query($sql);
		$rsoCode = '';
		if( $result->num_rows > 0 ) {
			while( $row = $result->fetch_assoc() ){
				$rsoCode .= '"'.$row['rso_code'].'",';
			}
		}
		return rtrim($rsoCode,',');
	}

	/* Keep mail logs */
	function mail_logs($rso_code = '', $role = '', $subject = '', $body = '', $device = '') {
		$insert_array = array(
			"rso_code" => $rso_code,
			"role" => $role,
			"subject" => $subject,
			'body' => $body,
			"date" => date("Y-m-d"),
			"time" => date("H:i:s"),
			"device" => $device,
		);
		$exception = insert($insert_array, 'mail_logs');
		return true;
	}

    //===================== Get Distributor Details
	function get_distributor_details($distributor) {
		global $mysqli;
		$zone = $mysqli->query("SELECT * FROM distributor_list_rso WHERE distributor_code = '$distributor'");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row;
		} else {
			return false;
		}
	}

    //===================== Get Distributor Details
	function get_ad_details($ad) {
		global $mysqli;
		$zone = $mysqli->query("SELECT * FROM ad_list WHERE ad_code = '$ad'");
		if($zone->num_rows > 0){
			$row = $zone->fetch_assoc();
			return $row;
		} else {
			return false;
		}
	}
	
	function clean($string) {
        //$string = str_replace(' ', ' ', $string); // Replaces all spaces with hyphens.
        
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

	//========= Insert query function ======
	function insert2nd_db($array, $table) {
		global $mysqli2;
		$query = "";
				
		if(! is_array($array) ) {
			die("ERROR: Invalid Operation.");
		}
		foreach($array as $key => $value ) {
		  $query .= "`$key`='$value',";   		
		}
		$query = " insert into `$table` set ".substr($query, 0, -1);
		
		$mysqli2->query($query) or die($mysqli2->error);
		return $mysqli2->insert_id;
		//return true;
	} 
	
	function unread_noti_count($uniquecode) {
	    global $mysqli;
		$notification = $mysqli->query("select count(id) as unread_noti_count FROM rso_notifications WHERE rso_code = '".$uniquecode."' and is_read = 'No'");
		if($notification->num_rows > 0){
			$row = $notification->fetch_assoc();
			return $row['unread_noti_count'];
		} else {
			return "0";
		}
	} 

?>