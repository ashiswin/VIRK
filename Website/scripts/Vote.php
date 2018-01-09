<?php
	// Include required helper classes
	require_once "utils/database.php";
	require_once "utils/websocket.php";
	require_once "connectors/VoterConnector.php";
	require_once "connectors/GroupConnector.php";
	require_once "connectors/EventConnector.php";
	
	// Load POST variables from client
	$eventid = intval($_POST['eventid']);
	$groupid = intval($_POST['groupid']);
	$studentid = $_POST['studentid'];
	$score = $_POST['score'];
	
	// Check if the event has ended yet
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid);
	date_default_timezone_set('Asia/Singapore');
	$currentDatetime = date('Y-m-d H:i:s', time());
	// Display an error message if event has ended
	if(strtotime($event["date"] . " " . $event["end"]) < strtotime($currentDatetime)) {
		$response["success"] = false;
		$response["message"] = "The event has ended. Your vote was not counted.";
		$response["close"] = true;
		
		echo(json_encode($response));
		return;
	}
	
	// Check if the student has already voted for the group
	$VoterConnector = new VoterConnector($conn);
	if($VoterConnector->selectIfVoted($studentid, $eventid, $groupid) != 0) {
		$response["success"] = false;
		$response["message"] = "You have already voted for this group";
		$response["close"] = true;
		
		echo(json_encode($response));
		return;
	}
	
	// Insert the new vote into the database
	$GroupConnector = new GroupConnector($conn);
	$group = $GroupConnector->selectGroup($eventid, $groupid);
	if(strcmp($group["votes"], "0") == 0) {
		$group["votes"] = "";
	}
	
	if(!$GroupConnector->updateVotes($group["votes"] . "|" . $studentid . "|" . $score, $eventid, $groupid)) {
		$response["success"] = false;
		$response["message"] = "Failed to register vote";
		
		echo(json_encode($response));
		return;
	}
	
	$VoterConnector->createVote($studentid, $eventid, $groupid);
	
	// Update stats server
	$ws = new ws(array
	(
		'host' => 'devostrum.no-ip.info',
		'port' => 8080,
		'path' => ''
	));
	$result = $ws->send("update:" . $eventid . ":" . $groupid . ":" . $score);
	$ws->close();
	
	$response["success"] = true;
	$response["votes"] = $VoterConnector->selectNumberVotes($studentid, $eventid);
	
	echo(json_encode($response));
?>
