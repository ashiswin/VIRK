<?php
    // This scripts grabs all the registered NFC ids
	require_once 'utils/database.php';
	require_once 'connectors/TagConnector.php';
	
	$TagConnector = new TagConnector($conn);
	
	$response["success"] = true;
	$response["tags"] = $TagConnector->selectAll();
	
	echo(json_encode($response));
?>
