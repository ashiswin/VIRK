<?php
    // Use this script whenever you want to add a new admin
    // $_POST needs to have username, password and name
    require_once 'utils/random_gen.php';
	require_once 'utils/database.php';
	require_once 'connectors/AdminConnector.php';
	
	$AdminConnector = new AdminConnector($conn); // AdminConnector has a whole bunch of prepare statements for queries 
	if(count($AdminConnector->select($_POST['username'])) > 0) { // If there is another of this username in Database
		$response["message"] = "This username has been taken";
		$response["success"] = false;
		
		echo(json_encode($response)); // Return error message
		return;
	}
	
	$username = $_POST['username'];
	$salt = random_str(10); // create random str of length 10
	$password = hash('sha512', ($_POST['password'] . $salt)); // hash the password
	$name = $_POST['name'];
	$result = $AdminConnector->create($username, $password, $salt, $name); // create name Admin using username, hashed password, salt and name
	
	if(!$result) { // If it didn't work for some reason
		$response["message"] = "Invalid username or password!";
		$response["success"] = false;
	}
	else {	
		$response["success"] = true;
	}
	
	echo(json_encode($response)); // Return error message
?>
