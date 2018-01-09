<?php
	require_once "utils/database.php";
	require_once "connectors/VoterConnector.php";
	require_once "connectors/GroupConnector.php";
	require_once "connectors/EventConnector.php";
	require_once "connectors/TagConnector.php";
	
	$tagId = $_POST['tagId'];
	$eventid = intval($_POST['eventid']);
	$groupid = intval($_POST['groupid']);
	
    // Checks if the TagID exists in Database
	$TagConnector = new TagConnector($conn);
	$student = $TagConnector->select($tagId); 
	if($student == false) {
		$response["success"] = false;
		$response["message"] = "This tag has not been registered for voting";
		
		echo(json_encode($response));
		return;
	}
	
    // If the TagID exists
    // Checks if this voter has already voted for this group in this event
	$studentid = $student["studentid"];
	
	$VoterConnector = new VoterConnector($conn);
	if($VoterConnector->selectIfVoted($studentid, $eventid, $groupid) != 0) { 
		$response["success"] = false;
		$response["message"] = "You have already voted for this group";
		
		echo(json_encode($response));
		return;
	}
	
    // If the Voter has not voted before
    // Check if this voter is from the group itself
	$GroupConnector = new GroupConnector($conn);
	$group = $GroupConnector->selectGroup($eventid, $groupid);
	
	if(strpos($group["members"], $studentid) !== false) { 
		$response["success"] = false;
		$response["message"] = "You cannot vote for your own group";
		
		echo(json_encode($response));
		return;
	}
	
    // If Voter has been registered, has not voted for the group yet and is not from the group
    // Check if the voter has exceeded max number of votes
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid);
	
	if($VoterConnector->selectNumberVotes($studentid, $eventid) >= $event['max']) {
		$response["success"] = false;
		$response["message"] = "You have reached the maximum number of votes";
		
		echo(json_encode($response));
		return;
	}
	
    // If everything goes true, this voter is allowed to vote
	$response["success"] = true;
	$response["studentid"] = $studentid;
	
	echo(json_encode($response));
