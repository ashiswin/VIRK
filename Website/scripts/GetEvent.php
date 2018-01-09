<?php
    // Use this script to get information about an event
    // Returns the event, groups, total number of reward redemptions and attendees
    // $_GET requires event id
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	require_once "connectors/GroupConnector.php";
	require_once "connectors/RedemptionConnector.php";
	require_once "connectors/VoterConnector.php";
	
	$eventid = $_GET['eventid'];
	
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid); // grabs event information
	
	$GroupConnector = new GroupConnector($conn);
	$groups = $GroupConnector->selectGroupsByEvent($eventid); // grabs all the groups in the event
	
	$RedemptionConnector = new RedemptionConnector($conn);
	$redemptions = $RedemptionConnector->selectTotalRedemptions($eventid); // counts how many students have redeemed for an event
	
	$VoterConnector = new VoterConnector($conn);
	$voters = $VoterConnector->selectUniqueVoters($eventid); // grabs all the voters in the event (including repeats)
	
	$attendees = null;

	for($i = 0; $i < count($voters); $i++) {
		$attendees[$voters[$i]["studentid"]] += 1; // creates a dictionary with student ids as keys 
                                                   // and number of votes by the students as values
	}
    
	$response["success"] = true;
	$response["event"] = $event;
	$response["event"]["groups"] = $groups;
	$response["event"]["attendees"] = $attendees;
	$response["event"]["redemptions"] = $redemptions;
	
	echo(json_encode($response)); // return everything
?>
