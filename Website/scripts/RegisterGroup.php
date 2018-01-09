<?php
    // Use this when you want to register a group for an event
    // $_POST requires eventid, groupid and password
	require_once "utils/database.php";
	require_once "connectors/GroupConnector.php";
	require_once "connectors/EventConnector.php";
	
    // get data
	$eventid = $_POST['eventid'];
	$groupid = $_POST['groupid'];
	$password = trim($_POST['password']);
	
	$GroupConnector = new GroupConnector($conn);
	$group = $GroupConnector->selectGroup($eventid, $groupid); // select group based on eventid and group id
	
	if(strcmp($group['password'], $password) == 0) { // check if password is correct
		$EventConnector = new EventConnector($conn);
		$event = $EventConnector->select($eventid); // grab the event
		
		$response["success"] = true;
		$response["groupname"] = $group['name'];
		$response["event"] = $event;
	}
	else {
		$response["success"] = false;
		$response["message"] = "Invalid password entered!";
	}
	
	echo(json_encode($response));
?>
