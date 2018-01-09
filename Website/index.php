<?php
	// Begin PHP session
	session_start();
	// Auto-redirect to management page if already logged in
	if(isset($_SESSION['adminid'])) {
		header("Location: manageevents.php");
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>VIRK - Login</title>
	<!-- Bootstrap CSS CDN -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="css/common.css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
	<!-- Navigation container -->
	<div class="container">
		<!-- Begin navigation bar-->
		<nav class="navbar navbar-default">
			<!-- Shrunken navbar for mobile phones (not used) -->
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="."><span><img height="32px" style="padding-right: 10px" src="img/virk.png"></span>VIRK</a>
			</div>
			<!-- Actual navbar elements -->
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar-nav navbar-right">
					<li><a href=".">Login</a></li>
				</ul>
			</div>
		</nav>
	</div>
	<!-- Main container -->
	<div class="container">
	    <div class="row"> <!-- Place all elements on same row -->
	    	<div class="col-md-4 col-md-offset-4"> <!-- Use middle third of screen -->
	    		<div class="panel panel-default"> <!-- Create a panel -->
				  	<div class="panel-heading"> <!-- Set up panel heading -->
				    		<h3 class="panel-title">Please sign in to VIRK Manager</h3>
				 	</div>
				  	<div class="panel-body"> <!-- Create panel content -->
						<!-- Create login form -->
					    	<form accept-charset="UTF-8" role="form" id="loginform" method="post" action="manageevents.php">
							<!-- Create form elements -->
				    			<fieldset>
						    	  	<div class="form-group">
						    		    <input class="form-control" placeholder="Username" name="username" id="username" type="text">
						    		</div>
						    		<div class="form-group">
						    			<input class="form-control" placeholder="Password" name="password" id="password" type="password" value="">
						    		</div>
								<input type="hidden" id="adminid" name="adminid" /> <!-- Hidden input is used to transmit admin ID to the next page to be stored in session -->
						    		<button class="btn btn-lg btn-danger btn-block" type="submit" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Loading..." id="btnLogin">Login</button>
					    		</fieldset>
					      	</form>
				    	</div>
				</div>
			</div>
		</div>
		<a href="bin/voting-app.apk">Download VIRK Voting App</a>
		<br>
		<a href="bin/registration-app.apk">Download VIRK Registration App</a>
		<br>
		<a href="bin/redemption-app.apk">Download VIRK Redemption App</a>
	</div>
	
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

	<!-- Latest compiled Bootstrap JavaScript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	
	<script type="text/javascript">
		$("#btnLogin").click(function(e) {
			e.preventDefault(); // Override default form submission
			$(this).button('loading'); // Start loading animation on login button
			
			// Manually post data to login script
			$.post("scripts/AdminLogin.php", { username: $("#username").val(), password: $("#password").val() }, function(data) {
				response = JSON.parse(data); // Convert to JSON
				if(!response.success) { // Check if authentication succeeded
					if(response.error) {
						if ( !HTMLFormElement.prototype.reportValidity ) {
							alert(response.message);
						}
						else {
							$("#username")[0].setCustomValidity(response.message);
							$("#username")[0].reportValidity();
						}
						$("#btnLogin").button('reset');
					}
				}
				else {
					$("#adminid").val(response.adminid) // Set hidden element with admin ID
					document.getElementById("loginform").submit(); // Submit the HTML form manually
				}
			});
		});
	</script>
</body>
</html>
