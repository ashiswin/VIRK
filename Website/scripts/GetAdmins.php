<?php
    // Use this script when you want to retrieve all admin entries
    // Except a specified one
    // $_GET required an 'adminid'. The entry corresponding the adminid 
    // would be left out

	require_once "utils/database.php";
	require_once "connectors/AdminConnector.php";
	
	$adminid = intval($_GET['adminid']);
	
	$AdminConnector = new AdminConnector($conn);
	$admins = $AdminConnector->selectAll(); // grab all entries
	
	for($i = 0; $i < count($admins); $i++) {
		if(intval($admins[$i]["id"]) == $adminid) { // remove the entry corresponding to $adminid
			unset($admins[$i]);
			break;
		}
	}
	
	$response["success"] = true;
	$response["admins"] = array_values($admins);
	
	echo(json_encode($response));
?>
