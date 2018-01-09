<?php
	require_once "scripts/database.php";
	require_once "scripts/EventConnector.php";

	header('Cache-Control: no cache'); //no cache
	session_cache_limiter('private_no_expire');

	session_start();
	if(isset($_POST['adminid'])) {
		$_SESSION['adminid'] = $_POST['adminid'];

	}
	if(!isset($_SESSION['adminid'])) {
		header("Location: login.php");
	}

	$EventConnector = new EventConnector($conn);
	$events = $EventConnector->selectAll();
?>

<!DOCTYPE html>
<html lang="en">
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.2/css/bootstrap.min.css" integrity="sha384-y3tfxAZXuh4HwSYylfB+J125MxIs6mR5FOHamPBG064zB+AFeWH94NdvaCBm8qnd" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.2/bootstrap-material-design.min.css">
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.2/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.2/bootstrap-material-design.umd.min.js"></script>

	<link rel="stylesheet" href="css/manageevent.css" type="text/css">
  	
  	<style>
        a:link {
            color: white; 
            opacity: 0.35;
            text-decoration: none;
        }

        a:visited {
            color: white;
            opacity: 0.35;
            text-decoration: none;
        }

        a:hover {
            color: white;
            opacity: 0.7;
            text-decoration: none;
        }

        a:active {
            color: white;
            opacity: 0.35;
            text-decoration: none;
        }
    </style>
  	
  	
  	<head>
  		<title>Managing an Event</title>
	    	<meta charset="utf-8">
	    	<meta name="viewport" content="width=device-width, initial-scale=1">
  	</head>

	<body>	
		<div class="container">
			<div class="col-md-offset-1 col-md-10">
				<div class="card">
					<img class="card-img img-responsive" src="img/container-cropped.png" width="100%">
	 				<div class="card-img-overlay">
						<div class="card-text">
							<a href="scripts/logout.php"><img id="logout" src="img/logout.png" width="5%"></a>
						</div>
	 					<p id="sidebar_text" class="card-text col-md-3">
	 					    <a href="account_settings.php">Account Settings</a>
	 					    <br style="display:block; content: ''; margin-top:5px">
							<a href="createevent-kd.php">Create an Event</a>
							<br style="display:block; content: ''; margin-top:5px">
							<b>Manage an Event</b>
							<br style="display:block; content: ''; margin-top:5px">
						</p>
						<div class="card-text col-md-offset-4" style="padding-left:2%">
		 					<img id="logo" src="img/logo.png" width="40%">
		 					<div class="row">
		 						<div id="form_title" class="col-md-offset-1 col-md-7">
		 							Event Selection
		 						</div>
								<div id="form" class="col-md-4" style="width:120%; padding-left:5%">
								    <div class="form-group has-warning">
								        <div class="btn-group">
								            <select id="events" class="form-control" required="events">
								                <?php 
								                    foreach($events as $event){
								                        echo "<option value=" . $event['id'] . ">" . $event['name'] . "</option>";
								                    }
								                ?>
								            </select>
								        </div>
								    </div>
								</div>
								<button id="next-button" class="btn btn-raised btn-danger col-md-4" style="margin-left:72%">Next</button>
								</p>
							</div>	
						</div>
	  				</div>
	  			</div>
			</div>
		</div>
	</body>
</html>
