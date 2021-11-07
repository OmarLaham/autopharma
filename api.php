<?php

//important: this api is not secure and is only for prototyping ;)

//turn off error reporting to use only our response-codes
error_reporting(0);




if(isset($_GET["operation"]) && $_GET["operation"] == "check_time") {
	print("Time on server now:<br />");
	print(date("Y-m-d H:i:s"));
	exit();
} else {
	if(isset($_GET["operation"]) && isset($_GET["pid"]) && isset($_GET["code"])) {
		$pid = $_GET["pid"];
		$pcode = $_GET["code"];
		$operation = $_GET["operation"];
		
		if($operation == "validate_user") {
			validate_user($pid, $pcode);
		} elseif ($operation == "get_dose") {
			if(!isset($_GET["must_order"])) {
				print("404");
				exit();
			}
			$must_order = $_GET["must_order"];
			$ignore_time_difference = false;
			$multi_pill_per_med=false;
			if(!isset($_GET["ignore_time_difference"]) && $_GET["ignore_time_difference"] == "yes") {
				$ignore_time_difference = true;
			}
			if(!isset($_GET["multi_pill_per_med"]) && $_GET["multi_pill_per_med"] == "yes") {
				$multi_pill_per_med = true;
			}
			get_dose($pid, $pcode, $must_order, $ignore_time_difference, $multi_pill_per_med);
		}
		
	} else {
		
		print("404");
		exit();
	}
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
		print("404");
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
		print("404");
		exit();
	}
}

function get_dose($pid, $pcode, $must_order, $ignore_time_difference, $multi_pill_per_med) {

	$patients_tubes = array(
		array("1,3", "1,3,5"),
		array("2,3", "2,3,6"),
		array("", "4")
	);
	if($multi_pill_per_med) {
		$patients_tubes = array(
			array("1:1,3:2", "1:1,3:2,5:1"),
			array("2:2,3:1", "2:2,3:1,6:1"),
			array("", "4:2")
		);
	}

	$query = "SELECT timestamp FROM `monitoring` WHERE patient_id=$pid ORDER BY timestamp DESC LIMIT 1";
	$result = exec_query($query);

	if($result) {
		//if the user has already got at least 1 dose from our system
		if($record = $result->fetch_object()) {
			$last_dose_timestamp = $record->timestamp;
			$now_timestamp = date("Y-m-d H:i:s");
			$diff = (int)date('H', $now_timestamp - $last_dose_timestamp);
			
			$now_24_hour = (int)date('H');
			//which medication tubes should be open?
			$patient_tubes = "";
			if($now_24_hour >= 6 && $now_24_hour < 18) {
				$patient_tubes = $patients_tubes[$pid-1][0];
			} elseif($now_24_hour >= 18 && $now_24_hour <= 23) {
				$patient_tubes = $patients_tubes[$pid-1][1];
			} else {
				print("454");
				exit();
			}
			if($patient_tubes != "") {
				$patient_tubes = "," . $patient_tubes;
			} else {
				print("464");//the patient has no dose now.
				exit();
			}
			
			
			
			unset($record);
		
			$ordering_error = false;
			
			//give dose only if at least 8 hours difference form last dose
			if($diff >= 8 || $ignore_time_difference) {
			
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
					print("434");
					exit();
				} else {
					print("200" . $patient_tubes);
				}

				
			} else {
				print("444");
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
				print("434");
				exit();
			} else {
				print("200" . $patient_tubes);
			}
		}	
		
	} else {
		print("404");
		exit();
	}
}


?>
