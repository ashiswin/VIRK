<?php
    // Call this script to download all the group login passwords
    // for a given event
    // $_POST requires 'id' for the event id
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	require_once "connectors/GroupConnector.php";
	
	$eventid = $_GET['id'];
	
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid);
	
	$GroupConnector = new GroupConnector($conn);
	$groups = $GroupConnector->selectGroupsByEvent($eventid);
	
    // The passwords will be downloaded in a csv file
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="' . $event['name'] . ' - Passwords.csv"');
	header('Content-Transfer-Encoding: binary');
	
	
	echo("#,Group Name, Members, Password\n");
	
	for($i = 0; $i < count($groups); $i++) {
		$membersArray = explode("|", $groups[$i]["members"]); // in membersArray, the odd entries are student ids, even entries are names
		$members = "";
		for($j = 0; $j < count($membersArray); $j += 2) {
			$members .= $membersArray[$j + 1] . " (" . $membersArray[$j] . ")";
			if($j != count($membersArray) - 2) {
				$members .= "; ";
			}
		}
		
		echo(($i + 1) . "," . $groups[$i]["name"] . "," . $members . "," . $groups[$i]["password"] . "\n");
	}
?>
