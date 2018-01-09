<?php
    // This script returns the md5 hash of the app apk
	$appName = $_GET['appName'];
	
	$response["success"] = true;
	$response["md5"] = md5_file("../bin/" . $appName);
	
	echo(json_encode($response));
?>
