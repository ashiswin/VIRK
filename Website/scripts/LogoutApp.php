<?php
    // Use this script when you are trying to logout of the Android apps
    // $_POST requires eventid and logout password
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	
	$eventid = intval($_POST['eventid']);
	$password = $_POST['password'];
	
	$EventConnector = new EventConnector($conn);
	
	$event = $EventConnector->select($eventid);
	
	if(strcmp($event["logoutpass"], $password) == 0) { // checks if the logout password is correct
		$response["success"] = true;
	}
	else {
		$response["success"] = false;
		$response["message"] = "Invalid password provided";
	}
	
	echo(json_encode($response));
?>
