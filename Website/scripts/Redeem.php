<?php
    // Use this script when you want to redeem the reward
    // Use RedeemTagId to check for eligibility before redeeming
    // $_POST requires studentid and eventid
	require_once "utils/database.php";
	require_once "utils/websocket.php";
	require_once "connectors/RedemptionConnector.php";
	
	$studentid = $_POST['studentid'];
	$eventid = intval($_POST['eventid']);
	
	$RedemptionConnector = new RedemptionConnector($conn);
	
	$result = $RedemptionConnector->select($studentid, $eventid); // Try to search for this record in database
	if(!$result) { // If record doesn't exist
		$RedemptionConnector->create($studentid, $eventid); // Creating record in database
		// Update stats server
		$ws = new ws(array
		(
			'host' => 'devostrum.no-ip.info',
			'port' => 8080,
			'path' => ''
		));
		$result = $ws->send("update:" . $eventid . ":" . $studentid);
		$ws->close();
		
		$response["success"] = true;
		echo(json_encode($response));
	}
	else { // If record already exists
		$response["success"] = false; 
		$response["message"] = "You have already redeemed your reward";
		echo(json_encode($response));
	}
?>
