<?php
	session_start();
	if(isset($_SESSION['adminid'])) {
		header("Location: manageevent.php");
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

	<link rel="stylesheet" href="css/login.css" type="text/css">
  	<head>
  		<title>Login Page</title>
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
							<img id="logout" src="img/logout.png" width="5%">
						</div>
						<div class="card-text col-md-offset-4" style="padding-left:2%">
		 					<img id="logo" src="img/logo.png" width="40%">
		 					
							<div id="form" class="col-md-offset-2 col-md-8">
								<div class="form-group has-warning">
									<input type="text" id="username" class="form-control" placeholder="Username" autofocus>
								</div>
								<div class="form-group has-warning">
									<input type="password" id="password" class="form-control" placeholder="Password" style="display:block; content:''; margin-top: 10px; margin-bottom: 10px">
								</div>
								<br>
								<button id="login-button" class="btn btn-raised btn-danger col-md-offset-3 col-md-6">Login</button>
							</div>
						</div>
						<form action="manageevent.php" method="POST" id="loginform">
							<input type="hidden" name="adminid" id="adminid" value="0" />
						</form>
	  				</div>
	  			</div>
			</div>
		</div>
	</body>
	<script type="text/javascript">
		$("#login-button").click(function(e) {
			e.preventDefault();
			$.post("scripts/AdminLogin.php", { username: $("#username").val(), password: $("#password").val() }, function(data) {
				response = JSON.parse(data);
				if(!response.success) {
					if(response.error) {
						$("#username")[0].setCustomValidity(response.message);
						$("#username")[0].reportValidity();
						$("#submit").button('reset');
					}
				}
				else {
					$("#adminid").val(response.adminid)
					document.getElementById("loginform").submit();
				}
			});
		});
        
	</script>
</html>
