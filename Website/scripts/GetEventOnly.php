<?php
    // Use this script if you only want information regarding an event and nothing extra
    // $_GET requires eventid
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	
	$eventid = $_GET['eventid'];
	
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid);
	
	$response["success"] = true;
	$response["event"] = $event;
	
	echo(json_encode($response));
?>
