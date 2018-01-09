<?php
	// Load current PHP session
	session_start();
	
	// Ensure an admin is logged in, otherwise redirect to login page
	if(isset($_POST['adminid'])) {
		$_SESSION['adminid'] = $_POST['adminid'];
	}
	if(!isset($_SESSION['adminid'])) {
		header("Location: index.php");
	}

	// Load necessary classes for interfacing with database
	require_once "scripts/utils/database.php";
	require_once "scripts/connectors/EventConnector.php";
	
	// Connect to Event table and load all current events
	$EventConnector = new EventConnector($conn);
	$events = $EventConnector->selectAll();
	
	// Redirect user to create an event if no events are currently in existence
	if(count($events) == 0) {
		header("Location: createevent.php");
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>VIRK - Manage Events</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="css/common.css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
	<style>
		html, body {
			margin: 0;
			height: 100%;
			overflow: hidden
		}
		.row.main-content {
			height: 100%;
		}

		.scrollable {
			overflow-y: auto !important;
			overflow-x: auto;
			height: 90vh;
		}
		.center {
			margin: auto;
		}
		#eventGroupUpload {
			position:absolute;
			top:-100px;
		}
	</style>
	<!-- Static navbar -->
	<div class="container">
		<?php require_once 'nav.php'; ?>
	</div>
	<div class="container"><!-- To get it to take up the whole width -->
		<div class="row main-content">
			<!-- Create side pane with events -->
			<div class="col-md-4 col-xs-4">
				<div class="scrollable">
					<table class="table table-hover">
						<?php
							// Separate events into categories
							$activeEvents = array();
							$completedEvents = array();
							
							date_default_timezone_set('Asia/Singapore');
							$currentDatetime = date('Y-m-d H:i:s', time());
							for($i = count($events) - 1; $i >= 0; $i--) {
								if(strtotime($events[$i]["date"] . " " . $events[$i]["end"]) < strtotime($currentDatetime)) {
									array_push($completedEvents, $events[$i]);
								}
								else {
									array_push($activeEvents, $events[$i]);
								}
							}
							
							// Display loaded events from server
							if(count($activeEvents) > 0) {
								echo "<tr id=\"-1\"><th><b>Active Events</b></th></tr>";
							}
							for($i = 0; $i < count($activeEvents); $i++) {
								echo "<tr id=\"" . $activeEvents[$i]["id"] . "\"><td><h4>" . $activeEvents[$i]["name"] . "</h4><div style=\"font-size: 12px; color: #AAAAAA;\">Created on " . $activeEvents[$i]["created"] . "</div></td></tr>";
							}
							
							if(count($completedEvents) > 0) {
								echo "<tr id=\"-1\"><th><b>Completed Events</b></th></tr>";
							}
							for($i = 0; $i < count($completedEvents); $i++) {
								echo "<tr id=\"" . $completedEvents[$i]["id"] . "\" class=\"edit-disabled\"><td><h4>" . $completedEvents[$i]["name"] . "</h4><div style=\"font-size: 12px; color: #AAAAAA;\">Created on " . $completedEvents[$i]["created"] . "</div></td></tr>";
							}
						?>
					</table>
				</div>
			</div>
			<!-- Begin main detail view -->
			<div class="col-md-8 col-xs-8 scrollable" style="border-left: 1px solid #CCCCCC;">
				<!-- UI element for top selector (Details, Stats, Attendees) -->
				<ul class="nav nav-pills center">
					<li class="active" id="detailsPaneItem"><a href="#" id="detailsPaneButton">Details</a></li>
					<li id="statsPaneItem"><a href="#" id="statsPaneButton">Statistics</a></li>
					<li id="attendeesPaneItem"><a href="#" id="attendeesPaneButton">Attendees</a></li>
				</ul>
				<!-- Begin Details panel -->
				<div id="detailsPane">
					<!-- Display main event data -->
					<div class="row">
						<div class="col-sm-6">
							<h2 id="eventName"></h2>
						</div>
						<div class="col-sm-6">
							<div class="pull-right">
								<button class="btn btn-primary" id="btnSave" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Saving...">Save</button>
								<button class="btn btn-danger" id="btnDelete">Delete</button>
							</div>
						</div>
					</div>
					<br>
					<div class="form-horizontal">
						<div class="form-group">
							<label for="eventDate" class="col-sm-2">Date:</label>
							<div class="col-sm-4">
								<input class="form-control change-sensitive" type="date" id="eventDate">
							</div>
						</div>
						<div class="form-group">
							<label for="eventStart" class="col-sm-2">Start:</label>
							<div class="col-sm-4">
								<input class="form-control change-sensitive" type="time" id="eventStart">
							</div>
							<label for="eventEnd" class="col-sm-2">End:</label>
							<div class="col-sm-4">
								<input class="form-control change-sensitive" type="time" id="eventEnd">
							</div>
						</div>
						<div class="form-group">
							<label for="eventMin" class="col-sm-2">Votes for reward:</label>
							<div class="col-sm-4">
								<input class="form-control change-sensitive" type="number" id="eventMin">
							</div>
							<label for="eventMax" class="col-sm-2">Maximum votes:</label>
							<div class="col-sm-4">
								<input class="form-control change-sensitive" type="number" id="eventMax">
							</div>
						</div>
						<div class="form-group">
							<label for="eventReward" class="col-sm-2">Reward:</label>
							<div class="col-sm-4">
								<input class="form-control change-sensitive" type="text" id="eventReward">
							</div>
						</div>
						<div class="form-group">
							<label for="eventLogout" class="col-sm-2">Logout password:</label>
							<div class="col-sm-4">
								<input class="form-control" type="text" id="eventLogout">
							</div>
							<div class="col-sm-2">
								<button class="btn btn-primary" id="btnGenerateLogout" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Generating...">Generate</button>
							</div>
						</div>
					</div>
					<br>
					<!-- Display groups associated with the event -->
					<h3>Groups</h3>
					<div>
						<input type="file" name="eventGroupUpload" id="eventGroupUpload" accept=".csv">
						<button class="btn btn-primary" id="btnGroupUpload" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Uploading..."><i class="glyphicon glyphicon-upload"></i> Upload new list</button>
						<button class="btn btn-success" id="btnAdd"><i class="glyphicon glyphicon-plus"></i> Add group</button>
						<button class="btn btn-warning" id="btnDownloadPasswords"><i class="glyphicon glyphicon-download"></i> Download passwords</button>
					</div>
					<br>
					<table id="eventGroups" class="table table-hover">
					</table>
						
				</div>
				<!-- Begin Statistics panel -->
				<div id="statsPane">
					<h2 id="eventNameStats"></h2>
					<!-- Display a message if there have been no votes yet -->
					<h4 id="noStats">There are no statistics for this event yet.</h4>
					<br>
					<!-- Else display statistics UI elements -->
					<button class="btn btn-primary" id="btnDownloadStats">Download Results</button>
					
					<!-- div for Google Charts -->
					<div id="eventGroupChart" style="width: 100%; margin-top: 5%; margin-left: 0%">
					</div>
					
					<!-- Table to hold voting results -->
					<label style="margin-top: 5%">Group scores</label>
					<table id="eventGroupsStats" class="table table-hover">
					</table>
					<table id="eventGroupsSummary" class="table">
					</table>
				</div>
				<!-- Begin Attendees panel -->
				<div id="attendeesPane">
					<h2 id="eventNameAttendees"></h2>
					<!-- Display a message if there have been no votes yet -->
					<h4 id="noVoters">There are no voters for this event yet.</h4>
					<br>
					<!-- Table to hold list of attendees -->
					<table id="eventAttendees" class="table table-hover">
					</table>
				</div>
			</div>
		</div>
	</div><!-- container -->
	
	<!-- Begin modal to add a group into the current event -->
	<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Add a group</h4>
				</div>
				<div class="modal-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label for="groupName" class="col-sm-2">Group name:</label>
							<div class="col-sm-10">
								<input class="form-control" type="text" id="groupName">
							</div>
						</div>
						<h3>Members</h3>
						<button id="btnAddMember" class="btn btn-success">Add member</button>
						<br>
						<table class="table table-borderless" id="membersTable">
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="btnAddSave">Add</button>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	
	<!-- Begin modal to confirm whether to delete the selected event -->
	<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
			    <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Delete event</h4>
			    </div>
			    <div class="modal-body">
				<p>Would you like to delete the selected event? This will destroy all groups and statistics associated with it as well.</p>
			    </div>
			    <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a class="btn btn-danger btn-ok" id="btnConfirmDelete">Delete</a>
			    </div>
			</div>
		</div>
	</div>
	
	<!-- Begin modal to confirm whether to delete a group -->
	<div class="modal fade" id="confirmDeleteGroupModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
			    <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Delete Group</h4>
			    </div>
			    <div class="modal-body">
				<p>Would you like to delete the selected group? This will destroy all votes and statistics associated with it as well.</p>
			    </div>
			    <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<a class="btn btn-danger btn-ok" id="btnConfirmDeleteGroup">Delete</a>
			    </div>
			</div>
		</div>
	</div>
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
	<script type="text/javascript">
		// If an event was selected, load it from the URL
		var selected = <?php
			if(!isset($_GET['eventid'])) echo $events[count($events) - 1]["id"];
			else echo $_GET['eventid']; ?>;
        
        var completedEvents = <?php echo json_encode($completedEvents) ?>;
        var editable = "editable"; // Default state of event is editable
		var memberCount = 3; // Default number of boxes to show for adding members when adding a group
		var socket; // Handle to websocket that will be created later
		
        // If the event is already over, editable will be set to disabled
        for (i = 0; i < completedEvents.length; i++) {
            if (completedEvents[i]["id"] == selected){
                editable = "edit-disabled";  
            }
        }
        
		// Function that loads the appropriate event when selected and updates the UI to reflect it
		function select(eventid, editableClass) {
			if(editableClass == undefined) {
				// If the event is already over, editable will be set to disabled
				for (i = 0; i < completedEvents.length; i++) {
					if (completedEvents[i]["id"] == eventid){
						editable = "edit-disabled";  
					}
				}
			}
			
			if(eventid == -1) return;
			$("#" + selected).removeClass('bg-info'); // "De-select" the row of the previously selected event
			selected = eventid; // Set the selected event to the event that was clicked
			$("#" + eventid).addClass('bg-info'); // "Select" the row of the clicked event
			// Load the event data into the page
			loadEvent(eventid, (editableClass == undefined) ? editable : editableClass.indexOf("edit-disabled") == -1);
		}
		
		// Function that loads a given event's data from the server
		function loadEvent(eventid, editable) {
			// Perform GET request to server for data
			$.get("scripts/GetEvent.php?eventid=" + eventid, function(data) {
				var response = JSON.parse(data); // Parse the response from JSON
				
				// Check for a successful server response
				if(response.success) {
					// Load data into UI elements
					$("#eventName").html(response.event.name);
					$("#eventNameStats").html(response.event.name);
					$("#eventNameAttendees").html(response.event.name);
					$("#eventDate").val(response.event.date);
					$("#eventStart").val(response.event.start);
					$("#eventEnd").val(response.event.end);
					$("#eventMin").val(response.event.min);
					$("#eventMax").val(response.event.max);
					$("#eventReward").val(response.event.reward);
					$("#eventLogout").val(response.event.logoutpass);
					
					// Check for statistical data (voters and groups)
					if(response.event.groups.length == 0) {
						// If no groups, display an appropriate message and hide all group-related UI elements
						$("#noStats").show();
						$("#eventGroups").hide();
						$("#eventGroupsStats").hide();
						$("#eventGroupsSummary").hide();
						$("#eventGroupChart").hide();
					}
					else {
						// Else, display group-related UI elements and hide the message
						$("#noStats").hide();
						$("#eventGroups").show();
						$("#eventGroupsStats").show();
						$("#eventGroupsSummary").show();
						$("#eventGroupChart").show();
					}
					
					// Set up variables for table data and set them to the table header lines
					var table = "<tr><th>#</th><th>Group Name</th><th>Members</th><th>Password</th><th><i class=\"glyphicon glyphicon-remove\"></i></th></tr>";
					var statsTable = "<tr><th>#</th><th>Group Name</th><th>1 Star</th><th>2 Stars</th><th>3 Stars</th><th>Score</th></tr>";
					var scoresArray = [["Group", "Score"]];
					
					// Loop through groups and populate table variables with associated data
					for(var i = 0; i < response.event.groups.length; i++) {
						// Populate main group table with group's name
						table += "<tr>";
						table += "<td>" + (i + 1) + "</td>";	
						table += "<td>" + response.event.groups[i].name + "</td>";
						
						// Populate statistics table with group's name
						statsTable += "<tr>";
						statsTable += "<td>" + (i + 1) + "</td>";	
						statsTable += "<td>" + response.event.groups[i].name + "</td>";
						
						var password = response.event.groups[i].password;
						var memberArray = response.event.groups[i].members.split("|");
						var members = "";
						
						// Loop through members and concatenate their data into a neater form
						for(var j = 0; j < memberArray.length; j += 2) {
							members += memberArray[j + 1];
							members += " (" + memberArray[j] + ")";
						
							if(j != memberArray.length - 2) {
								members += ", ";
							}
						}
						
						// Initialize variables to calculate the scores for each group
						var score = 0;
						var votes = (response.event.groups[i].votes == null) ? [] : response.event.groups[i].votes.split("|");
						var starVotes = [0, 0, 0];
						
						// Loop through votes, calculate the overall group score and count each vote
						for(var j = 0; j < votes.length; j++) {
							if(votes[j] == "" || votes[j].length > 1) continue;
							if(parseInt(votes[j]) == 0) continue;
							
							score += parseInt(votes[j]);
							starVotes[parseInt(votes[j]) - 1]++;
						}
						
						// Populate the remaining data into the main table
						table += "<td>" + members + "</td>";
						table += "<td>" + password + "</td>";
						table += "<td><a href=\"#\" style=\"text-decoration: none; color: inherit;\" class=\"deleteGroup\" id=\"" + response.event.groups[i].groupid + "\"><i class=\"glyphicon glyphicon-remove\"></i></a></td>";
						table += "</tr>";
						
						// Populate statistics table count of each type of vote
						for(var j = 0; j < starVotes.length; j++) {
							statsTable += "<td>" + starVotes[j] + "</td>";
						}
						statsTable += "<td>" + score + "</td>";
						statsTable += "</tr>";
						
						// Add scores to array for Google Charts display
						scoresArray.push([(i + 1).toString(), score]);
					}
					
					// Check if there were any attendees for the event
					if(response.event.attendees != null && response.event.attendees.length == 0) {
						// If no attendees, display an appropriate message and hide all attendees related UI elements
						$("#noVoters").show();
						$("#eventAttendees").hide();
					}
					else {
						// Else, display the attendees in a table and hide the message
						$("#noVoters").hide();
						$("#eventAttendees").show();
						
						// Initialise the header for the attendees table
						var attendeesTable = "<tr><th>#</th><th>Student ID</th><th>Votes</th></tr>";
						
						// Loop through and populate the table with student IDs and how many votes were cast
						//for(var i = 0; i < response.event.attendees.length; i++) {
						var attendeesCount = 0;
						for(var key in response.event.attendees) {
							attendeesTable += "<tr><td>" + (attendeesCount + 1) + "</td>";
							attendeesTable += "<td>" + key + "</td>"
							attendeesTable += "<td>" + response.event.attendees[key] + "</td></tr>";
                            				attendeesCount++;
						}
					}
					
					if(response.event.groups.length > 0 && response.event.attendees != null) {
						// Create variables for containing the data of the summary table
						var summaryTable = "<tr><th>Highest score</th><th>Lowest score</th><th>Mean score</th><th>Total attendees</th></th>Total votes cast</th><th>Total reward redemptions</th></tr>";
						summaryTable += "<tr>";
						var mini = 1000000;
						var miniGroup = -1;
						var maxi = -1;
						var maxiGroup = -1;
						var totalScores = 0;

						// Loop through and find the highest and lowest scoring group
						for(var i = 1; i < scoresArray.length; i++) {
							if(scoresArray[i][1] < mini) {
								mini = scoresArray[i][1];
								miniGroup = parseInt(scoresArray[i][0]) - 1;
							}
							if(scoresArray[i][1] > maxi) {
								maxi = scoresArray[i][1];
								maxiGroup = parseInt(scoresArray[i][0]) - 1;
							}
							totalScores += scoresArray[i][1];
						}			
						var miniArray = [];
						var maxiArray = [];		
						for(var i = 1; i < scoresArray.length; i++) {
							if(scoresArray[i][1] == mini) {
								miniArray.push(parseInt(scoresArray[i][0]) - 1);
							}
							if(scoresArray[i][1] == maxi) {
								maxiArray.push(parseInt(scoresArray[i][0]) - 1);
							}
						}
					
						var miniString = "";
						var maxiString = "";
						for(var i = 0; i < miniArray.length; i++) {
							miniString += response.event.groups[miniArray[i]].name + " (" + mini + ")";
							if(i != miniArray.length - 1) {
								miniString += ", ";
							}
						}
						for(var i = 0; i < maxiArray.length; i++) {
							maxiString += response.event.groups[maxiArray[i]].name + " (" + maxi + ")";
							if(i != maxiArray.length - 1) {
								maxiString += ", ";
							}
						}
						// Populate table with appropriate data
						if(!(mini == 0 && maxi == 0)) {
							summaryTable += "<td>" + maxiString + "</td>";
							summaryTable += "<td>" + miniString + "</td>";
						}
						else {
							summaryTable += "<td>NA</td>";
							summaryTable += "<td>NA</td>";
						}
						summaryTable += "<td>" + (totalScores / (scoresArray.length - 1)).toFixed(2) + "</td>";
						summaryTable += "<td>" + attendeesCount + "</td>";
						summaryTable += "<td>" + response.event.redemptions + "</td>";
					}
					else {
						summaryTable = "";
					}
					
					// Update tables with each table's generated data
					$("#eventGroups").html(table);
					$("#eventGroupsStats").html(statsTable);
					$("#eventGroupsSummary").html(summaryTable);
					$("#eventAttendees").html(attendeesTable);
					
					// Set up event listener for deleting a group
					$(".deleteGroup").click(function(e) {
						e.preventDefault();
						$("#confirmDeleteGroupModal").modal(); // Display a modal for confirming the deletion
						$("#btnConfirmDeleteGroup").val($(this).attr('id'));
					});
					// Set up an event listener for confirming the deleting of a group
					$("#btnConfirmDeleteGroup").click(function() {
						$("#confirmDeleteGroupModal").modal('hide');
						deleteGroup($(this).val()); // Perform actual deletion
					});
					
					// Create Google Chart to display voting stats
					google.charts.load("current", {packages:['corechart']});
					google.charts.setOnLoadCallback(drawChart);
					
					// Google Chart callback function to display the chart
					function drawChart() {
						// Load data from score array created above
						var data = google.visualization.arrayToDataTable(scoresArray);

						// Set display parameters
						var view = new google.visualization.DataView(data);
						view.setColumns([0, 1,
							{ calc: "stringify",
							sourceColumn: 1,
							type: "string",
							role: "annotation" }]);
						
						// Set view options
						var options = {
							title: "Results of voting",
							width: $('#detailsPane').width(),
							bar: {groupWidth: "95%"},
							chartArea: {'width': "94%", 'height': '80%'},
							legend: { position: "none" },
						};
						
						// Get a handle to the UI div that will contain the chart and draw the chart
						var chart = new google.visualization.ColumnChart(document.getElementById("eventGroupChart"));
						chart.draw(view, options);
					}
					// Disable buttons if current event is over
					if(!editable) {
						$("#btnSave").addClass("disabled");
						$("#btnGenerateLogout").addClass("disabled");
						$("#btnGroupUpload").addClass("disabled");
						$("#btnAdd").addClass("disabled");
						$("#btnDownloadPasswords").addClass("disabled");
						
						$("#btnSave").attr('disabled', 'disabled');
						$("#btnGenerateLogout").attr('disabled', 'disabled');
						$("#btnGroupUpload").attr('disabled', 'disabled');
						$("#btnAdd").attr('disabled', 'disabled');
						$("#btnDownloadPasswords").attr('disabled', 'disabled');
					}
					else {
						$("#btnSave").removeClass("disabled");
						$("#btnGenerateLogout").removeClass("disabled");
						$("#btnGroupUpload").removeClass("disabled");
						$("#btnAdd").removeClass("disabled");
						$("#btnDownloadPasswords").removeClass("disabled");
						
						$("#btnSave").removeAttr('disabled');
						$("#btnGenerateLogout").removeAttr('disabled');
						$("#btnGroupUpload").removeAttr('disabled');
						$("#btnAdd").removeAttr('disabled');
						$("#btnDownloadPasswords").removeAttr('disabled');
					}
				}
			});
		}
		
		// Function to delete a given group
		function deleteGroup(id) {
			// Perform a post request to the server to delete a group
			$.post("scripts/DeleteGroup.php", { eventid: selected, groupid: id }, function(data) {
				response = JSON.parse(data);
				if(response.success) {
					// Reload the current event to reflect the changes
					select(selected);
				}
			});
		}
		
		// Function to generate a logout password when needed
		function generateLogoutPassword() {
			// Initialize variables
			var password = "";
			var possible = "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"; // Set the possible key space for the password

			// Choose 6 characters at random for the password
			for(var i = 0; i < 6; i++) {
				password += possible.charAt(Math.floor(Math.random() * possible.length));
			}
			
			// Update UI element with the password
			$("#eventLogout").val(password);
			$("#btnGenerateLogout").button('reset');
			
			// Save the password to the database
			$("#btnSave").html('<i class=\'glyphicon glyphicon-refresh spinning\'></i> Saving...');
			$("#btnSave").addClass('disabled');
			$.post("scripts/UpdateLogoutPass.php", { logoutpass: password, eventid: selected }, function(data) {
				response = JSON.parse(data);
				if(response.success) {
					// Display success message if the saving worked
					$("#btnSave").removeClass('btn-primary').removeClass('disabled');
					$("#btnSave").addClass('btn-success');
					$("#btnSave").html('<i class=\'glyphicon glyphicon-ok\'></i> Saved');
				}
			});
			
		}
		
		// Function to save any changes made to an event.
		function saveEvent() {
			// Get current values in the text boxes
			var date = $("#eventDate").val();
			var start = $("#eventStart").val();
			var end = $("#eventEnd").val();
			var mini = $("#eventMin").val();
			var maxi = $("#eventMax").val();
			var reward = $("#eventReward").val();
			
			// Check for valid input and display error messages accordingly
			if(!date.trim()) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventDate")[0].setCustomValidity("Please enter a date");
				$("#eventDate")[0].reportValidity();
				return;
			}
			if(!start.trim()) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventStart")[0].setCustomValidity("Please enter a start time");
				$("#eventStart")[0].reportValidity();
				return;
			}
			if(!end.trim()) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventEnd")[0].setCustomValidity("Please enter an end time");
				$("#eventEnd")[0].reportValidity();
				return;
			}
			if(!mini.trim()) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventMin")[0].setCustomValidity("Please enter the minimum votes required for a reward");
				$("#eventMin")[0].reportValidity();
				return;
			}
			if(!maxi.trim()) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventMax")[0].setCustomValidity("Please enter the maximum votes a user can place");
				$("#eventMax")[0].reportValidity();
				return;
			}
			if(!reward.trim()) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventReward")[0].setCustomValidity("Please enter a reward for the event");
				$("#eventReward")[0].reportValidity();
				return;
			}
			
			// Ensure the start date is before the end date
			var now = moment();
			var dateMoment = moment(date);
			var dateStartMoment = moment(date + " " + start);
			var dateEndMoment = moment(date + " " + end);

			if(dateEndMoment.isBefore(dateStartMoment)) {
				$("#btnSave").removeClass('btn-primary').removeClass('disabled');
				$("#btnSave").addClass('btn-danger');
				$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				$("#eventEnd")[0].setCustomValidity("End time needs to be after start time");
				$("#eventEnd")[0].reportValidity();
				return;
			}
			
			// Create form data with data in the text boxes
			var form_data = new FormData();
			form_data.append('date', date);
			form_data.append('start', start);
			form_data.append('end', end);
			form_data.append('min', mini);
			form_data.append('max', maxi);
			form_data.append('reward', reward);
			form_data.append('eventid', selected);
			
			// Perform POST request to server with the updated data
			$.ajax({
				url: 'scripts/EventUpdate.php', // point to server-side PHP script 
				dataType: 'text',  // what to expect back from the PHP script, if anything
				cache: false,
				contentType: false,
				processData: false,
				data: form_data,
				type: 'post',
				success: function(data){
				    response = JSON.parse(data); // Gets response from the PHP script, if any
				    
				    // Display success/error message accordingly
				    if(response.success){
					$("#btnSave").removeClass('btn-primary').removeClass('disabled').removeClass('btn-danger');
					$("#btnSave").addClass('btn-success');
					$("#btnSave").html('<i class=\'glyphicon glyphicon-ok\'></i> Saved');
		
				    } else{
					$("#btnSave").removeClass('btn-primary').removeClass('disabled');
					$("#btnSave").addClass('btn-danger');
					$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save failed');
				    }
				}
			});
		}
		
		// Function to save a group that was added via the modal
		function saveGroup() {
			// Initialize variables to provided data
			var name = $("#groupName").val();
			if(!name.trim()) {
				$("#btnAddSave").button('reset');
				$("#groupName")[0].setCustomValidity("Please enter a name for the group");
				$("#groupName")[0].reportValidity();
				return;
			}
			var members = "";
			
			// Loop through member text boxes and splice together data into a string for storage
			for(var i = 0; i < memberCount; i++) {
				if(!$("#name" + i).val().trim()) continue;
				if(i != 0) {
					members += "|";
				}
				members += $("#studentid" + i).val() + "|" + $("#name" + i).val();
			}
			
			// Perform POST request to server to add the group to the current event
			$.post("scripts/AddGroup.php", { eventid: selected, name: name, members: members }, function(data) {
				response = JSON.parse(data);
				if(response.success) {
					// Reload the current event to reflect the changes
					$("#addModal").modal('hide');
					select(selected);
					$("#btnSave").removeClass('btn-primary').removeClass('disabled').removeClass('btn-danger');
					$("#btnSave").addClass('btn-success');
					$("#btnSave").html('<i class=\'glyphicon glyphicon-ok\'></i> Saved');
				}
			});
		}
		
		// Set up event listener for when the DOM is fully loaded
		$(document).ready(function() {
			// Select the provided event, or the first event as appropriate
			select(selected, editable);
			
			// Hide all information panes besides the details pane
			$("#statsPane").hide();
			$("#attendeesPane").hide();
			
			// Display the correct active item in the navbar
			$("#navManage").addClass('active');
			
			// Set up an event listener for when an event is selected
			$("tr").click(function() {
				socket.send("unlisten:" + selected); // Inform websocket server to stop updating this page about old event
				select($(this).attr('id'), $(this).attr('class')); // Load the correct event
				socket.send("listen:" + selected); // Inform websocket server to start updating the page about new event
			});
			
			// Set up an event listener for when the details pane toggle is clicked
			$("#detailsPaneButton").click(function(e) {
				e.preventDefault();
				// Select the appropriate element and deselect the others
				$("#detailsPaneItem").addClass("active");
				$("#statsPaneItem").removeClass("active");
				$("#attendeesPaneItem").removeClass("active");
				
				// Display the details pane and hide all others
				$("#detailsPane").show();
				$("#statsPane").hide();
				$("#attendeesPane").hide();
			});
			
			// Set up an event listener for when the stats pane toggle is clicked
			$("#statsPaneButton").click(function(e) {
				e.preventDefault();
				// Select the appropriate element and deselect the others
				$("#detailsPaneItem").removeClass("active");
				$("#statsPaneItem").addClass("active");
				$("#attendeesPaneItem").removeClass("active");
				
				// Display the stats pane and hide all others
				$("#detailsPane").hide();
				$("#statsPane").show();
				$("#attendeesPane").hide();
			});
			
			//Set up an event listener for when the attendees pane toggle is clicked
			$("#attendeesPaneButton").click(function(e) {
				e.preventDefault();
				// Select the appropriate element and deselect the others
				$("#detailsPaneItem").removeClass("active");
				$("#statsPaneItem").removeClass("active");
				$("#attendeesPaneItem").addClass("active");
				
				// Display the attendees pane and hide all others
				$("#detailsPane").hide();
				$("#statsPane").hide();
				$("#attendeesPane").show();
			});
			
			// Set up an event listener to generate the logout password when clicked
			$("#btnGenerateLogout").click(function(e) {
				e.preventDefault();
				$(this).button('loading');
				generateLogoutPassword();
			});
			
			// Set up an event listener to save the event details when button is clicked
			$("#btnSave").click(function(e) {
				e.preventDefault();
				// Display loading sign on button and disable to prevent multiple clicks
				$("#btnSave").html('<i class=\'glyphicon glyphicon-refresh spinning\'></i> Saving...');
				$("#btnSave").removeClass('btn-success');
				$("#btnSave").addClass('btn-primary');
				$("#btnSave").addClass('disabled');
				
				saveEvent();
			});
			
			// Set up an event listener to display the modal to allow the user to add a group to the current event
			$("#btnAdd").click(function(e) {
				e.preventDefault();
				$("#addModal").modal();
				memberCount = 3;
				
				// Create the table to enter member details
				var memberTableString = "<tr><th>#</th><th>Member name</th><th>Student ID</th></tr>";
				for(var i = 0; i < memberCount; i++) {
					memberTableString += "<tr><td><label>" + (i + 1) + "</label></td><td><input type=\"text\" class=\"form-control\" id=\"name" + i + "\"/></td><td><input type=\"text\" class=\"form-control\" id=\"studentid" + i + "\"/></td></tr>";
				}
				
				// Update the table with the member text boxes
				$("#membersTable").html(memberTableString);
			});
			
			// Set up an event listener to add extra textboxes with the button is clicked
			$("#btnAddMember").click(function(e) {
				e.preventDefault();
				memberCount++; // Update number of members in group
				var memberTableString = "<tr><td><label>" + (memberCount) + "</label></td><td><input type=\"text\" class=\"form-control\" id=\"name" + (memberCount - 1) + "\"/></td><td><input type=\"text\" class=\"form-control\" id=\"studentid" + (memberCount - 1) + "\"/></td></tr>";
				$("#membersTable").append(memberTableString);
			});
			
			// Set up an event listener to save the group when the save button is clicked in the modal
			$("#btnAddSave").click(function(e) {
				e.preventDefault();
				saveGroup();
			});
			
			// Set up an event listener to download the group passwords when button clicked
			$("#btnDownloadPasswords").click(function(e) {
				e.preventDefault();
				window.location = "scripts/DownloadPasswords.php?id=" + selected;
			});
			
			// Set up an event listener to call the file upload dialog box with the button is clicked
			$("#btnGroupUpload").click(function() {
				$("#eventGroupUpload").click();
			});
			
			// Set up an event listener to upload the selected group list to the server after the dialog box has closed
			$('#eventGroupUpload').change(function() {
				$("#btnGroupUpload").button('loading');
				
				// Prepare data for POST request
				var file_data = $('#eventGroupUpload').prop('files')[0];
				var form_data = new FormData();                  
				
				form_data.append('group_list', file_data);
				form_data.append('eventid', selected);
				
				// Perform POST request to send file to server
				$.ajax({
				        url: 'scripts/UploadGroupList.php', // point to server-side PHP script 
				        dataType: 'text',  // what to expect back from the PHP script, if anything
				        cache: false,
				        contentType: false,
				        processData: false,
				        data: form_data,
				        type: 'post',
				        success: function(data){
						response = JSON.parse(data); // gets response from the PHP script, if any

						if(response.success){
							select(selected);
							$("#btnGroupUpload").button('reset');
							$("#eventGroupUpload").val("");
							$("#btnSave").removeClass('btn-primary').removeClass('disabled').removeClass('btn-danger');
							$("#btnSave").addClass('btn-success');
							$("#btnSave").html('<i class=\'glyphicon glyphicon-ok\'></i> Saved');
						}
						else {
							alert(response.message);
							$("#btnGroupUpload").button('reset');
							$("#eventGroupUpload").val("");
							$("#btnSave").removeClass('btn-primary').removeClass('disabled').removeClass('btn-danger');
							$("#btnSave").addClass('btn-danger');
							$("#btnSave").html('<i class=\'glyphicon glyphicon-remove\'></i> Save Failed');
						}
				        }
				});
			});
			
			// Set up event listener to bring up delete confirmation modal
			$("#btnDelete").click(function() {
				$("#confirmDeleteModal").modal()
			});
			
			// Set up event listener to perform deletion of an event when modal is confirmed
			$("#btnConfirmDelete").click(function() {
				// Perform POST request to server to delete current event
				$.post("scripts/DeleteEvent.php", { eventid: selected }, function(data) {
					response = JSON.parse(data);
					if(response.success) {
						window.location.href="manageevents.php"; // Reload page to reflect deletion
					}
				});
			});
			
			// Set up an event listener to download the statistics of the event when button clicked
			$("#btnDownloadStats").click(function() {
				window.location = "scripts/DownloadStats.php?id=" + selected;
			});
			
			// Set up an event listener on all UI elements that should trigger the Save button to switch from the green "Saved" to the blue "Save"
			$(".change-sensitive").keyup(function(e) {
				$("#btnSave").html('Save');
				$("#btnSave").removeClass('btn-success').removeClass('btn-danger');
				$("#btnSave").addClass('btn-primary');
			});
			
			// Connect to Websocket for live statistics
			if(!("WebSocket" in window)) {
				alert('<p>WebSockets is not present in this browser. Dynamic statistics updates will not be enabled.</p>');
			}
			else {
				//The user has WebSockets
				connect();
			}
			
			// Function to perform the actual connection
			function connect(){
				// Open WebSocket connection to server
		    		socket = new WebSocket("ws://devostrum.no-ip.info:8080");
				
				socket.onopen = function(){
					socket.send("listen:" + selected); // Listen for updates on current event
				}
				socket.onmessage = function(msg){
					// When data is received from the server, reload the current event
					console.log(msg.data);
					select(selected, );
				}
			}
		});
	</script>
</body>
</html>
