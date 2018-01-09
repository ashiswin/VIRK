<?php
    // Call this script when you want to download all the stats for an event
    // $_GET requires just event id as 'id'
	require_once "utils/database.php";
	require_once "connectors/EventConnector.php";
	require_once "connectors/GroupConnector.php";
	
	$eventid = $_GET['id'];
	
	$EventConnector = new EventConnector($conn);
	$event = $EventConnector->select($eventid);
	
	$GroupConnector = new GroupConnector($conn);
	$groups = $GroupConnector->selectGroupsByEvent($eventid);
	
    // stats will be downloaded in a csv file
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="' . $event['name'] . ' - Statistics.csv"');
	header('Content-Transfer-Encoding: binary');
	
    // set titles of the csv
	echo("#,Group Name, Members, 1 Star, 2 Stars, 3 Stars, Score\n");
	
	for($i = 0; $i < count($groups); $i++) {
        
        // writes down the members of the group in a nice format
		$membersArray = explode("|", $groups[$i]["members"]); // in membersArray, the odd entries are student ids, even entries are names
		$members = "";
		for($j = 0; $j < count($membersArray); $j += 2) {
			$members .= $membersArray[$j + 1] . " (" . $membersArray[$j] . ")";
			if($j != count($membersArray) - 2) {
				$members .= "; ";
			}
		}
		
        	// counts number of 1 star, 2 star and 3 star votes
		$votesArray = explode("|", $groups[$i]["votes"]); // in votesArray, the odd entries are student ids, even entries are votes
		$votes = 0;
		$stars = array(0, 0, 0);
		
		for($j = 0; $j < count($votesArray); $j++) {
			if(strlen($votesArray[$j]) < 5 && // if length of entry is not 7 (studentid) or 5 (staffid)
			strcmp($votesArray[$j], "") != 0 && // if entry is not empty
			intval($votesArray[$j]) != 0) // if entry is not 0
            		{
				$votes += intval($votesArray[$j]); // total number of votes increases by number of stars
				$stars[intval($votesArray[$j]) - 1]++; // the count for that number of stars increases by 1
			}
		}
		
        	// print out group name, group members, number of votes for each number of stars, and total number of stars
		echo(($i + 1) . "," . $groups[$i]["name"] . "," . $members . ",");
		for($j = 0; $j < count($stars); $j++) {
			echo($stars[$j] . ",");
		}
		echo($votes . "\n");
	}
?>
