<?php
    // Use this script when details for an event is changed
    // $_POST requires 'event_name', 'date', 'start', 'end', 'min', 'max', 'reward', 'adminid'
    // start and end refers to timings which the event starts and end
    // min is minimum number of votes required for reward
    // max is maximum number of votes per person
	$date = trim($_POST['date']);
	$start = trim($_POST['start']);
	$end = trim($_POST['end']);
	$min = trim($_POST['min']);
	$max = trim($_POST['max']);
	$reward = trim($_POST['reward']);
	$eventid = trim($_POST['eventid']);
        
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	
	$EventConnector = new EventConnector($conn);
	$result = $EventConnector->update($date, $start, $end, $min, $max, $reward, $eventid);

	if(!$result) {
		$response["message"] = "Event not updated";
		$response["success"] = false;
	}
	else {
		$response["message"] = "Event updated";
		$response["success"] = true;
	}

	echo(json_encode($response));
?>
