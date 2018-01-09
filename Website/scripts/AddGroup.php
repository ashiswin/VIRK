<?php
    // Use this script whenever you want to add a new group to an event
    // $_POST requires eventid, name (name refers to group name) and members
    // Members are in the format, Student1 id | Student1 Name | Student2 id | Student2 Name, etc
    require_once "utils/random_gen.php";
	require_once "utils/database.php";
	require_once "connectors/GroupConnector.php";
	
	$eventid = $_POST['eventid'];
	$name = $_POST['name'];
	$members = $_POST['members'];
	$password = random_str();

	$GroupConnector = new GroupConnector($conn); // GroupConnector has bunch of prepare statements for queries
	$result = $GroupConnector->createGroup($name, $members, $password, $eventid); // Create new group
	
	if(!$result) { // if it didn't work for some reason
		$response["success"] = false;
		$response["message"] = "Failed to add group";
	}
	else { // if it worked
		$response["success"] = true;
		$response["groupid"] = $result;
	}
	
	echo(json_encode($response)); // return a response
?>
