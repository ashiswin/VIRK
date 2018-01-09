<?php
    // Use this script when you want to check whether someone is eligible to redeem the reward
    // If the result is true, call Redeem.php to redeem
    // $_POST requires studentid and eventid

	require_once "utils/database.php";
	require_once "connectors/VoterConnector.php";
	require_once "connectors/EventConnector.php";
	require_once "connectors/RedemptionConnector.php";
	require_once "connectors/TagConnector.php";

	$tagId = $_POST['tagId'];
	$eventid = intval($_POST['eventid']);
	
    $TagConnector = new TagConnector($conn);
    $tag = $TagConnector->select($tagId); // Checks if the NFC id has been registered
    if(!$tag) {
        $response["success"] = false;
        $response["message"] = "This tag has not been registered for voting";
        
        echo(json_encode($response));
        return;
    }
	
	$studentid = $tag['studentid']; // grab the student id from the tag data
	
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid);
	
	$VoterConnector = new VoterConnector($conn);
	if($VoterConnector->selectNumberVotes($studentid, $eventid) < $event['min']) { // Checks if student has met min number of votes
		$response["success"] = false;
		$response["message"] = "You have not met the required number of votes. " . ($event['min'] - $VoterConnector->selectNumberVotes($studentid, $eventid)) . " votes required.";
		
		echo(json_encode($response));
		return;
	}
	
	$RedemptionConnector = new RedemptionConnector($conn);
	$redemptions = $RedemptionConnector->selectTotalRedemptions($eventid); 
	
	if($RedemptionConnector->select($studentid, $eventid) != false) { // Check if record of student redeeming can be found
		$response["success"] = false;
		$response["message"] = "You have already redeemed your reward";
		
		echo(json_encode($response));
		return;
	}
	
	$response["success"] = true; // The student is eligible to redeem!
	$response["studentid"] = $studentid;
	
	echo(json_encode($response));
