<?php
    // Use this script to delete a particular NFC id record
    // $_POST requires 'tagid'
	require_once "utils/database.php";
	require_once "connectors/TagConnector.php";
	
	$tagid = $_POST['tagid'];
	
	$TagConnector = new TagConnector($conn);
	$TagConnector->delete($tagid);
	
	$response["success"] = true;
	
	echo(json_encode($response));
?>
