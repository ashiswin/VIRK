<?php
    // Use this script when you want to retrieve all the groups for an event
    // $_GET requires event id as 'id'

	require_once "utils/database.php";
	require_once "connectors/GroupConnector.php"; 
	
	$eventid = $_GET['id'];
	
	$GroupConnector = new GroupConnector($conn);
	$groups = $GroupConnector->selectGroupsByEvent($eventid); // Grabs all the groups with eventid
	
	$response["success"] = true;
	$response["groups"] = $groups;
	
	echo(json_encode($response));
?>
