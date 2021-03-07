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

	// ============= Var Dump ===================
	function dump($value){
		echo "<pre>"; var_dump($value); echo "</pre>";
	}

?>