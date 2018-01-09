<?php
	require_once "scripts/database.php";

	session_start();
	if(isset($_POST['adminid'])) {
		$_SESSION['adminid'] = $_POST['adminid'];

	}
	if(!isset($_SESSION['adminid'])) {
		header("Location: login.php");
	}

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

	<link rel="stylesheet" href="css/account_settings.css" type="text/css">
  	
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
  		<title>Account Settings</title>
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
	 					    <b>Account Settings</b>
	 					    <br style="display:block; content: ''; margin-top:5px">
							<a href="createevent-kd.php">Create an Event</a>
							<br style="display:block; content: ''; margin-top:5px">
							<a href="manageevent.php">Manage an Event</a>
							<br style="display:block; content: ''; margin-top:5px">
						</p>
						<div class="card-text col-md-offset-4" style="padding-left:2%">
		 					<img id="logo" src="img/logo.png" width="40%">
		 					<div class="row col-md-12">
                                        <table style="border: none; margin-top: 12%">
                                            <tr>
                                                <td class="form-title" style="width: 55%">Old Username</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning" style="width:180%;">
                                                        <input type="text" name="old_username" id="old_username" class="form-control" placeholder="Enter current username"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="form-title" style="width: 55%">New Username</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning" style="width:180%;">
                                                        <input type="text" name="new_username" id="new_username" class="form-control" placeholder="Enter the username you want"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="form-title" style="opacity: 0">\ </td>
                                            </tr>
                                            <tr style="width:120%">
                                                <td class="form-title" style="width: 55%">Old Password</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning in-group date" style="width:180%">
                                                        <input type="password" name="old_password" id="old_password" class="form-control" placeholder="Enter current password"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr style="width:120%">
                                                <td class="form-title" style="width: 55%">New Password</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning in-group date" style="width:180%;">
                                                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter the password you want"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr style="width:120%">
                                                <td class="form-title" style="width: 55%">Retype Password</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning in-group date" style="width:180%;">
                                                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Re-enter the new password"/>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <button id="update-button" class="btn btn-raised btn-danger col-md-offset-4 col-md-4" style="margin-top: 5%">Update
                                        </button>
                                    </div>
						</div>
	  				</div>
	  			</div>
			</div>
		</div>
	</body>
</html>
