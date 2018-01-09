<?php
    // Call this script when uploading group list 
    // Group list has to be in .csv format
    // $_FILES requires 'group_list' 
    // $_POST requires 'eventid'

	ini_set("auto_detect_line_endings", true); // PHP will examine the data read by fgets() and file() 
                                               // to see if it is using Unix, MS-Dos or Macintosh line-ending conventions.
    
    function remove_utf8_bom($text)
    //this function removes BOM characters
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    if(isset($_FILES['group_list'])){
        // This block of code checks whether the file has been uploaded properly
        // And if the file format is correct
        try {
            // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it invalid.
            if (
                !isset($_FILES['group_list']['error']) ||
                is_array($_FILES['group_list']['error'])
            ) {
                throw new RuntimeException('Invalid parameters.');
            }

            // Check $_FILES['upfile']['error'] value.
            switch ($_FILES['group_list']['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }

            // You should also check filesize here. 
            if ($_FILES['group_list']['size'] > 1000000) {
                throw new RuntimeException('Exceeded filesize limit.');
            }

            // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
            // Check MIME Type by yourself.
            $listOfMIME = array(
                'text/comma-separated-values', 
                'text/csv', 
                'application/csv', 
                'application/excel', 
                'application/vnd.ms-excel', 
                'application/vnd.msexcel', 
                'text/anytext');
                
            // MIME type has to be within the listOfMIME and on top of that
            // File extension has to be .csv
            if(!in_array($_FILES['group_list']['type'], $listOfMIME) ||
                substr($_FILES['group_list']['name'], -4) !== '.csv')
            {
                throw new RuntimeException('Invalid file format.');
            }
            
            // Need to check if the file itself is in Group Name, Student ID, Student Name format
            $group_list = fopen($_FILES['group_list']['tmp_name'], 'rb');
            $line = fgetcsv($group_list);
            
            if($line != false){
                $item1 = remove_utf8_bom(trim($line[0]));
                $item2 = remove_utf8_bom(trim($line[1]));
                $item3 = remove_utf8_bom(trim($line[2]));
                
                if(!is_string($item1) || 
                   !ctype_digit($item2) || 
                   !ctype_alpha($item3))
                {
                    throw new RuntimeException('CSV entries need to be in the format, "Group Name, Student ID, Student Name."');
                }   
            } 

        } catch (RuntimeException $e) {
		$response = array();
		$response["message"] = $e->getMessage();
		$response["success"] = false;
		
		die(json_encode($response));
        }
    }

    // Now that the file passed all the checks, the data shall be added to database
	$eventid = trim($_POST['eventid']);
	
    require_once "utils/random_gen.php";
	require_once "utils/database.php";
	require_once "connectors/GroupConnector.php";
	
	$GroupConnector = new GroupConnector($conn);
	$GroupConnector->deleteEvent($eventid);
	
	$groups = array();

    // Put the group members in a nice format
	while ($line != false) {
	    $group_name = remove_utf8_bom(trim($line[0])); // remove bom characters in group name
	    $members = '';
	    foreach($line as $item){ // putting members in the format studentid | student name | ...
	        $item = remove_utf8_bom(trim($item));
	        if($item != $group_name and $item != ''){
	            $members .= $item . '|';
	        }
	    }
	    
	    $members = rtrim(trim($members), '|'); // remove the last | since it's extra
	    $group_password = random_str(); // generate random password
	    
	    while($GroupConnector->checkUniquePW($group_password) == false) // ensure password is not repeated within database
	    {
	        $group_password = random_str(); // if it is repeated, then generate a new one
	    }
	    
	    
	    $GroupConnector->createGroup($group_name, $members, $group_password, $eventid); // add group to database
	    $line = fgetcsv($group_list);
	}
	fclose($group_list);
	
	$response["success"] = true;
	
	echo(json_encode($response));
?>
