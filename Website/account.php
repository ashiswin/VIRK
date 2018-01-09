<?php
	// Begin PHP Session
	session_start();
	// Redirect to login page if not logged in yet
	if(!isset($_SESSION['adminid'])) {
		header("Location: index.php");
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>VIRK - Account</title>
	<!-- Bootstrap CSS CDN -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="css/common.css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
	<style>
		.row.main-content {
			height: 100%;
		}

		.scrollable {
			overflow-y: auto;
			height: 100%;
		}
		.center {
			margin: auto;
		}
	</style>
	<!-- Navigation container -->
	<div class="container">
		<?php require_once 'nav.php'; ?>
	</div>
	
	<!-- Main container -->
	<div class="container">
		<h1>Manage Account</h1>
		<hr>
		<div>
			<h3>Change Password</h3>
			<br>
			<form class="form-horizontal">
				<div class="form-group">
					<label for="currentPass" class="col-sm-2">Current password:</label>
					<div class="col-sm-4">
						<input class="form-control" type="password" id="currentPass">
					</div>
				</div>
				<div class="form-group">
					<label for="newPass" class="col-sm-2">New password:</label>
					<div class="col-sm-4">
						<input class="form-control" type="password" id="newPass">
					</div>
				</div>
				<div class="form-group">
					<label for="retypePass" class="col-sm-2">Retype password:</label>
					<div class="col-sm-4">
						<input class="form-control" type="password" id="retypePass">
					</div>
				</div>
				<button class="btn btn-primary" id="btnSubmit" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Loading...">Update</button>
			</form>
		</div>
		<hr>
		<div>
			<h3>Manage Admins</h3>
			<span>Your account will not appear on this list.</span>
			<br>
			<br>
			<div>
				<button class="btn btn-success" id="btnAdd"><i class="glyphicon glyphicon-plus"></i> Add admin</button>
			</div>
			<br>
			<table id="adminTable" class="table table-hover">
			</table>
		</div>
	</div>
	
	<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Add an Admin</h4>
				</div>
				<div class="modal-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label for="adminName" class="col-sm-2">Admin Name:</label>
							<div class="col-sm-10">
								<input class="form-control" type="text" id="adminName">
							</div>
						</div>
						<div class="form-group">
							<label for="adminUsername" class="col-sm-2">Username:</label>
							<div class="col-sm-10">
								<input class="form-control" type="text" id="adminUsername">
							</div>
						</div>
						<div class="form-group">
							<label for="adminPassword" class="col-sm-2">Password:</label>
							<div class="col-sm-4">
								<input class="form-control" type="password" id="adminPassword">
							</div>
							<label for="adminRetype" class="col-sm-2">Retype Password:</label>
							<div class="col-sm-4">
								<input class="form-control" type="password" id="adminRetype">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="btnAddSave" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Loading...">Add</button>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	
	<div class="modal fade" id="confirmDeleteAdminModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
			    <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Delete Admin</h4>
			    </div>
			    <div class="modal-body">
				<p>Would you like to delete the selected admin?</p>
			    </div>
			    <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a class="btn btn-danger btn-ok" id="btnConfirmDeleteAdmin">Delete</a>
			    </div>
			</div>
		</div>
	</div>
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

	<!-- Latest compiled Bootstrap JavaScript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
	
	<script type="text/javascript">
		var adminid = <?php echo $_SESSION['adminid']; ?>; 
		var deleting = -1;
		
		function loadAdmins() {
			$.get("scripts/GetAdmins.php?adminid=" + adminid, function(data) {
				var response = JSON.parse(data);
				if(response.success) {
					var adminTable = "<tr><th>#</th><th>Name</th><th>Username</th><th><i class=\"glyphicon glyphicon-remove\"></i></th></tr>";
					
					for(var i = 0; i < response.admins.length; i++) {
						adminTable += "<tr>";
						adminTable += "<td>" + (i + 1) + "</td>";
						adminTable += "<td>" + response.admins[i].name + "</td>";
						adminTable += "<td>" + response.admins[i].username + "</td>";
						adminTable += "<td><a href=\"#\" id=\"" + response.admins[i].id + "\" class=\"delete\"><i class=\"glyphicon glyphicon-remove\"></i></a></td>";
						adminTable += "</tr>";
					}
					
					$("#adminTable").html(adminTable);
					
					$(".delete").click(function(e) {
						e.preventDefault();
						deleting = $(this).attr('id');
						$("#confirmDeleteAdminModal").modal();
					});
					$("#btnConfirmDeleteAdmin").click(function(e) {
						e.preventDefault();
						$.post("scripts/DeleteAdmin.php", { id: deleting }, function(data) {
							response = JSON.parse(data);
							if(response.success) {
								$("#confirmDeleteAdminModal").modal('hide');
								loadAdmins();
							}
						});
					});
				}
				else {
					console.log(response.message);
				}
			});
		}
		
		$(document).ready(function() {
			$("#navAccount").addClass('active');
			loadAdmins();
			
			$("#btnAdd").click(function(e) {
				e.preventDefault();
				$("#addModal").modal();
			});
			
			$("#btnAddSave").click(function(e) {
				e.preventDefault();
				$(this).button('loading');

				var name = $("#adminName").val();
				var username = $("#adminUsername").val();
				var password = $("#adminPassword").val();
				var retype = $("#adminRetype").val();
				
				if(!name.trim()) {
					$("#btnAddSave").button('reset');
					$("#adminName")[0].setCustomValidity("Please enter the admin's name");
					$("#adminName")[0].reportValidity();
					return;
				}
				if(!username.trim()) {
					$("#btnAddSave").button('reset');
					$("#adminUsername")[0].setCustomValidity("Please enter the admin's username");
					$("#adminUsername")[0].reportValidity();
					return;
				}
				if(!password.trim()) {
					$("#btnAddSave").button('reset');
					$("#adminPassword")[0].setCustomValidity("Please enter the admin's password");
					$("#adminPassword")[0].reportValidity();
					return;
				}
				if(!retype.trim()) {
					$("#btnAddSave").button('reset');
					$("#adminRetype")[0].setCustomValidity("Please retype the admin's password");
					$("#adminRetype")[0].reportValidity();
					return;
				}
				if(password != retype) {
					$("#btnAddSave").button('reset');
					$("#adminPassword")[0].setCustomValidity("Entered password do not match");
					$("#adminPassword")[0].reportValidity();
					return;
				}
				
				$.post("scripts/AddAdmin.php", { name: name, username: username, password: password }, function(data) {
					response = JSON.parse(data);
					if(response.success) {
						$("#addModal").modal('hide');
						$("#btnAddSave").button('reset');
						$("#adminName").val("");
						$("#adminUsername").val("");
						$("#adminPassword").val("");
						$("#adminRetype").val("");
						loadAdmins();
					}
					else {
						$("#btnAddSave").button('reset');
						$("#adminName")[0].setCustomValidity(response.message);
						$("#adminName")[0].reportValidity();
						return;
					}
				});
			});
			
			$("#btnSubmit").click(function(e) {
				e.preventDefault();
				$(this).button('loading');

				var current = $("#currentPass").val();
				var newPass = $("#newPass").val();
				var retype = $("#retypePass").val();

				if(!current.trim()) {
					$("#btnSubmit").button('reset');
					$("#currentPass")[0].setCustomValidity("Please enter your current password");
					$("#currentPass")[0].reportValidity();
					return;
				}
				if(!newPass.trim()) {
					$("#btnSubmit").button('reset');
					$("#newPass")[0].setCustomValidity("Please enter your new password");
					$("#newPass")[0].reportValidity();
					return;
				}
				if(!retype.trim()) {
					$("#btnSubmit").button('reset');
					$("#retypePass")[0].setCustomValidity("Please retype your new password");
					$("#retypePass")[0].reportValidity();
					return;
				}
				if(newPass != retype) {
					$("#btnSubmit").button('reset');
					$("#newPass")[0].setCustomValidity("Entered passwords do not match");
					$("#newPass")[0].reportValidity();
					return;
				}
				
				$.post("scripts/UpdateAdminPassword.php", { adminid: adminid, current: current, newpass: newPass }, function(data) {
					response = JSON.parse(data);
					if(response.success) {
						$("#btnSubmit").button('reset');
						$("#currentPass").val("");
						$("#newPass").val("");
						$("#retypePass").val("");
						alert("Password updated successfully");
					}
					else {
						$("#btnSubmit").button('reset');
						if ( !HTMLFormElement.prototype.reportValidity ) {
							alert(response.message);
						}
						else {
							$("#currentPass")[0].setCustomValidity(response.message);
							$("#currentPass")[0].reportValidity();
						}
						return;
					}
				});
			});
		});
	</script>
</body>
</html>
