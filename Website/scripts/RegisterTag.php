<?php
    // Use this to register a NFC card or tag
    // $_POST requires tagid and studentid
	require_once "utils/database.php";
	require_once "connectors/TagConnector.php";
	
	$tagid = $_POST['tagid'];
	$studentid = $_POST['studentid'];
	
	$TagConnector = new TagConnector($conn);
	
	$response["success"] = $TagConnector->create($tagid, $studentid); // create new record
	
	if(!$response["success"]) { // if record couldn't be created
		$response["message"] = "This tag or student ID has already been registered";
	}
	
	echo(json_encode($response));
?>
