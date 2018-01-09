<?php
    // Use this script to delete a group at a particular event
    // $_POST requires 'eventid' and 'groupid'
	require_once "utils/database.php";
	require_once "connectors/GroupConnector.php";
	
	$eventid = $_POST['eventid'];
	$groupid = $_POST['groupid'];
	
	$GroupConnector = new GroupConnector($conn);
	$GroupConnector->deleteGroup($eventid, $groupid);
	
	$response["success"] = true;
	
	echo(json_encode($response));
?>
