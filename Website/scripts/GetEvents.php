<?php
    // Use this script when you want to retrieve active events
    // Returns only event ids, event names, event dates and end timings
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	
	$EventConnector = new EventConnector($conn);
	$events = $EventConnector->selectAllCustom(EventConnector::$COLUMN_ID, EventConnector::$COLUMN_NAME, EventConnector::$COLUMN_DATE, EventConnector::$COLUMN_END);

	$activeEvents = array();
	
	date_default_timezone_set('Asia/Singapore');
	$currentDatetime = date('Y-m-d H:i:s', time());

	for($i = count($events) - 1; $i >= 0; $i--) {
		if(strtotime($events[$i]["date"] . " " . $events[$i]["end"]) >= strtotime($currentDatetime)) { // Only picks events that haven't pass yet
			array_push($activeEvents, $events[$i]);
		}
	}
	
	$response["success"] = true;
	$response["events"] = $activeEvents;
	
	echo(json_encode($response));
?>
