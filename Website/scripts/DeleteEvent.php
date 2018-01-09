<?php
    // Use this script to delete a particular event with event id, 'eventid'
    // $_POST required 'eventid'
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	require_once "connectors/GroupConnector.php";
	require_once "connectors/VoterConnector.php";
	require_once "connectors/RedemptionConnector.php";
	
	$eventid = $_POST['eventid'];
	
	$EventConnector = new EventConnector($conn);
	$GroupConnector = new GroupConnector($conn);
	$VoterConnector = new VoterConnector($conn);
	$RedemptionConnector = new RedemptionConnector($conn);
	
    $EventConnector->delete($eventid); // The event is deleted
	$GroupConnector->deleteEvent($eventid); // Groups registered for the event are deleted
	$VoterConnector->deleteEvent($eventid); // All voters who voted at the event are deleted
	$RedemptionConnector->deleteEvent($eventid); // All redemption records at the event are deleted
	
	$response["success"] = true;
	
	echo(json_encode($response));
?>
