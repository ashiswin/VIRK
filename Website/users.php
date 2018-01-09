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
	<title>VIRK - Voters</title>
	<!-- Bootstrap CSS CDN -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="css/common.css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
	<!-- Navigation container -->
	<div class="container">
		<?php require_once 'nav.php'; ?>
	</div>
	
	<!-- Main container -->
	<div class="container">
		<h1>Manage Voters</h1>
		<br>
		<table class="table table-hover" id="tagTable">
		</table>
	</div>
	
	<!-- Begin modal to confirm if selected user should be deleted -->
	<div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
			    <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Delete Voter</h4>
			    </div>
			    <div class="modal-body">
				<p>Would you like to delete the selected voter?</p>
			    </div>
			    <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a class="btn btn-danger btn-ok" id="btnConfirmDeleteUser">Delete</a>
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
		// Initialize variables
		var adminid = <?php echo $_SESSION['adminid']; ?>; // Get current admin's ID from session
		var deleting = "";
		
		// Function to load all users from server
		function loadUsers() {
			// Perform GET request to get all registered voters
			$.get("scripts/GetTags.php", function(data) {
				var response = JSON.parse(data);
				
				// Check for successful response
				if(response.success) {
					// Set up header line of voters table
					var tagTable = "<tr><th>#</th><th>Student ID</th><th>Tag ID</th><th>Last Update</th><th><i class=\"glyphicon glyphicon-remove\"></i></th></tr>";
					// Loop through provided data and populate table with information
					for(var i = 0; i < response.tags.length; i++) {
						tagTable += "<tr>";
						tagTable += "<td>" + (i + 1) + "</td>";
						tagTable += "<td>" + response.tags[i].studentid + "</td>";
						tagTable += "<td>" + response.tags[i].tagid + "</td>";
						tagTable += "<td>" + moment(response.tags[i].lastupdate).format('MMMM Do YYYY, h:mm A') + "</td>";
						tagTable += "<td><a href=\"#\" class=\"remove-tag\" id=\"" + response.tags[i].tagid + "\"><i class=\"glyphicon glyphicon-remove\"></i></a></td>";
						tagTable += "</tr>";
					}
					
					// Update table with generated data
					$("#tagTable").html(tagTable);
					
					// Set up listener to respond when a voter should be removed
					$(".remove-tag").click(function(e) {
						e.preventDefault();
						
						// Get the ID of the voter that should be removed and display the confirmation dialog
						deleting = $(this).attr('id');
						$("#confirmDeleteUserModal").modal();
					});
					
					// Set up listener to respond when deletion is confirmed
					$("#btnConfirmDeleteUser").click(function(e) {
						e.preventDefault();
						
						// Perform POST request to delete the voter
						$.post("scripts/DeleteTag.php", { tagid: deleting }, function(data) {
							response = JSON.parse(data);
							if(response.success) {
								// If successful, close the confirmation dialog and reload the user list to reflect changes
								$("#confirmDeleteUserModal").modal('hide');
								loadUsers();
							}
						});
					});
				}
			});
		}
		
		// Set up listener for when DOM is fully loaded
		$(document).ready(function() {
			// Update nav bar to display the correct item as active
			$("#navUsers").addClass('active');
			
			// Load voters from server
			loadUsers();
		});
	</script>
</body>
</html>
