<?php

//important: this api is not secure and is only for prototyping ;)


if(isset($_GET["pid"]) && isset($_GET["pcode"]) && isset($_GET["operation"])) {
	$pid = $_GET["pid"];
	$pcode = $_GET["pcode"];
	$operation = $_GET["operation"];
	
	if($operation == "validate_user") {
		validate_user($pid, $pcode);
	} elseif ($operation == "get_dose") {
		if(!isset($_GET["must_order"])) {
			print("404");
			exit();
		}
		$must_order = $_GET["must_order"];
		get_dose($pid, $pcode, $must_order);
	} else {
		print("404");
		exit();
	}
	
} else {
	print("404");
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
	}

	// Perform query
	if ($result = $mysqli -> query($query)) {

		$mysqli -> close();
		return $result;

	}

}


function validate_user($pid, $pcode) {

	$query = "SELECT FROM patients WHERE pid=$pid AND passcode=$pcode";
	$result = exec_query($query);

	if($result != "404" && mysqli_num_rows($result)) {
		print("200");
	} else {
		print("404");
		exit();
	}
}

function get_dose($pid, $pcode, $must_order) {

	$query = "SELECT TOP 1 FROM monitoring WHERE pid=$pid ORDER BY timestampe DESC";
	$result = exec_query($query);

	if($result && $result != "404") {
	
		$record = $result->fetch_object())
		$last_dose_timestamp = $obj->timestamp;
		$now_timestamp = time();
		$diff = (int)date('H', $now_timestamp - $last_dose_timestamp);
		
		unset($record);
	
		$ordering_error = false;
		
		//give dose only if at least 8 hours difference form last dose
		if($diff >= 8) {
		
			//add monitoring data
			$query = "INSERT INTO monitoring (patient_id) VALUES ($pid)";
			$result = exec_query($query);
			
			//order if must
			if($must_order == "yes") {
				$query = "INSERT INTO orders (patient_id) VALUES ($pid)";
				$result = exec_query($query);

				if($result->affected_rows == 0) {
					$ordering_error = true;
				}		
			}
			
			if($ordering_error) {
				print("434");
			} else {
				print("200");
			}

			
		} else {
			print("444");
			exit();
		}
	
		
	} else {
		print("404");
		exit();
	}
}


?>
