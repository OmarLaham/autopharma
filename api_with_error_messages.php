<?php

//important: this api is not secure and is only for prototyping ;)

//turn off error reporting to use only our response-codes
error_reporting(0);

if(isset($_GET["pid"]) && isset($_GET["code"]) && isset($_GET["operation"])) {
	$pid = $_GET["pid"];
	$pcode = $_GET["code"];
	$operation = $_GET["operation"];
	
	if($operation == "validate_user") {
		validate_user($pid, $pcode);
	} elseif ($operation == "get_dose") {
		if(!isset($_GET["must_order"])) {
			print("404\n[must order] is missing in your request");
			exit();
		}
		$must_order = $_GET["must_order"];
		get_dose($pid, $pcode, $must_order);
	} else {
		print("404\nInvalid operation");
		exit();
	}
	
} else {
	print("404\nInvalid request");
	exit();
}


function exec_query($query) {

	$host = "localhost";
	$username = "id17824824_ranim_5th_year_admin";
	$password = "LjXDC_+5{[=<1GfF";
	$dbname = "id17824824_ranim_5th_year";

	$mysqli = new mysqli($host, $username, $password, $dbname);

	// Check connection
	if ($mysqli -> connect_errno) {
		return false;
		print("404\nCan't connect to database. Server  error");
		exit();
	}

	// Perform query
	if ($result = $mysqli -> query($query)) {

		$mysqli -> close();
		return $result;

	} else  {
		return false;
	}

}


function validate_user($pid, $pcode) {

	$query = "SELECT * FROM `patients` WHERE id=$pid AND passcode='$pcode'";
	$result = exec_query($query);

	if(mysqli_num_rows($result)) {
		print("200");
	} else {
		print("404\nInvalid user");
		exit();
	}
}

function get_dose($pid, $pcode, $must_order) {

	$query = "SELECT timestamp FROM `monitoring` WHERE patient_id=$pid ORDER BY timestamp DESC LIMIT 1";
	$result = exec_query($query);

	if($result) {
		//if the user has already got at least 1 dose from our system
		if($record = $result->fetch_object()) {
			$last_dose_timestamp = $record->timestamp;
			$now_timestamp = time();
			$diff = (int)date('H', $now_timestamp - $last_dose_timestamp);
			
			unset($record);
		
			$ordering_error = false;
			
			//give dose only if at least 8 hours difference form last dose
			if($diff >= 8) {
			
				//add monitoring data
				$query = "INSERT INTO `monitoring` (patient_id) VALUES ($pid)";
				$result = exec_query($query);
				
				//order if must
				if($must_order == "yes") {
					$query = "INSERT INTO `orders` (patient_id) VALUES ($pid)";
					$result = exec_query($query);

					if($result == false) {
						$ordering_error = true;
					}		
				}
				
				if($ordering_error) {
					print("434\nWasn't able to order new medicine");
					exit();
				} else {
					print("200");
				}

				
			} else {
				print("444\nThe patient is trying to get another dose in less that 8 hours.");
				exit();
			}
		} else {//if this is the first time for this user getting a dose from our system (nothing in monitoring from the past)
			//add monitoring data
			$query = "INSERT INTO `monitoring` (patient_id) VALUES ($pid)";
			$result = exec_query($query);
			//order if must
			if($must_order == "yes") {
				$query = "INSERT INTO `orders` (patient_id) VALUES ($pid)";
				$result = exec_query($query);

				if($result == false) {
					$ordering_error = true;
				}		
			}
			
			if($ordering_error) {
				print("434\nWasn't able to order new medicine");
				exit();
			} else {
				print("200");
			}
		}	
		
	} else {
		print("404\nCan't execute databse monitoring query. Server error.");
		exit();
	}
}


?>
