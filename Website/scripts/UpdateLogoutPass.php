<?php
    // Use this to update the Event logout password
    // $_POST requires eventid and new logout password

	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	
	// Load POST variables from client
	$eventid = $_POST['eventid'];
	$logoutpass = $_POST['logoutpass'];
	
	// Create a new Event Connector
	$EventConnector = new EventConnector($conn);
	$response["success"] = $EventConnector->updateLogoutPass($logoutpass, $eventid); // Perform update of logout password
	
	// Return status of update
	echo(json_encode($response));
?>
