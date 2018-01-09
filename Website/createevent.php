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
	<title>VIRK - Create Event</title>
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
		#eventGroupUpload {
			position:absolute;
			top:-10000px;
		}
	</style>
	<!-- Navigation container -->
	<div class="container">
		<?php require_once 'nav.php'; ?>
	</div>
	
	<!-- Main container -->
	<div class="container">
		<h1>Create Event</h1>
		<hr>
		<br>
		<div class="form-horizontal">
			<div class="form-group">
				<label for="eventName" class="col-sm-2">Event name:</label>
				<div class="col-sm-4">
					<input class="form-control" type="text" id="eventName">
				</div>
			</div>
			<div class="form-group">
				<label for="eventDate" class="col-sm-2">Date:</label>
				<div class="col-sm-4">
					<input class="form-control" type="date" id="eventDate">
				</div>
			</div>
			<div class="form-group">
				<label for="eventStart" class="col-sm-2">Start:</label>
				<div class="col-sm-4">
					<input class="form-control" type="time" id="eventStart">
				</div>
				<label for="eventEnd" class="col-sm-2">End:</label>
				<div class="col-sm-4">
					<input class="form-control" type="time" id="eventEnd">
				</div>
			</div>
			<div class="form-group">
				<label for="eventMin" class="col-sm-2">Votes for reward:</label>
				<div class="col-sm-4">
					<input class="form-control" type="number" id="eventMin">
				</div>
				<label for="eventMax" class="col-sm-2">Maximum votes:</label>
				<div class="col-sm-4">
					<input class="form-control" type="number" id="eventMax">
				</div>
			</div>
			<div class="form-group">
				<label for="eventReward" class="col-sm-2">Reward:</label>
				<div class="col-sm-4">
					<input class="form-control" type="text" id="eventReward">
				</div>
			</div>
			<input type="file" name="eventGroupUpload" id="eventGroupUpload" accept=".csv">
			<button class="btn btn-primary" id="btnGroupUpload">Upload group list CSV</button>
			<button class="btn btn-success pull-right" id="btnSubmit" data-loading-text="<i class='glyphicon glyphicon-refresh spinning'></i> Loading...">Submit</button>
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
		
		$(document).ready(function() {
			$("#navCreate").addClass('active');
			$("#btnGroupUpload").click(function() {
				$("#eventGroupUpload").click();
			});
			$('#eventGroupUpload').change(function() {
				$('#btnGroupUpload').html($('#eventGroupUpload').prop('files')[0].name);
			});
		
		
			$("#btnSubmit").click(function(e) {
			    e.preventDefault();
			    $(this).button('loading');
			    
			    var name = $("#eventName").val();
			    var date = $("#eventDate").val();
			    var start = $("#eventStart").val();
			    var end = $("#eventEnd").val();
			    var mini = $("#eventMin").val();
			    var maxi = $("#eventMax").val();
			    var reward = $("#eventReward").val();
			    
			    if(!name.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventName")[0].setCustomValidity("Please enter a name for this event");
		                $("#eventName")[0].reportValidity();
				return;
			    }
			    if(!date.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventDate")[0].setCustomValidity("Please enter a date");
		                $("#eventDate")[0].reportValidity();
				return;
			    }
			    if(!start.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventStart")[0].setCustomValidity("Please enter a start time");
		                $("#eventStart")[0].reportValidity();
				return;
			    }
			    if(!end.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventEnd")[0].setCustomValidity("Please enter an end time");
		                $("#eventEnd")[0].reportValidity();
				return;
			    }
			    if(!mini.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventMin")[0].setCustomValidity("Please enter the minimum votes required for a reward");
		                $("#eventMin")[0].reportValidity();
				return;
			    }
			    if(!maxi.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventMax")[0].setCustomValidity("Please enter the maximum votes a user can place");
		                $("#eventMax")[0].reportValidity();
				return;
			    }
			    if(!reward.trim()) {
				$("#btnSubmit").button('reset');
		                $("#eventReward")[0].setCustomValidity("Please enter a reward for the event");
		                $("#eventReward")[0].reportValidity();
				return;
			    }
			    var now = moment();
			    var dateMoment = moment(date);
			    var dateStartMoment = moment(date + " " + start);
			    var dateEndMoment = moment(date + " " + end);
			    
			    if(dateMoment.isBefore(now, 'day')) {
				$("#btnSubmit").button('reset');
		                $("#eventDate")[0].setCustomValidity("Date needs to be in the future");
		                $("#eventDate")[0].reportValidity();
				return;
			    }
			    else if(dateStartMoment.isBefore(now)) {
				$("#btnSubmit").button('reset');
		                $("#eventStart")[0].setCustomValidity("Start time needs to be in the future");
		                $("#eventStart")[0].reportValidity();
				return;
			    }
			    else if(dateEndMoment.isBefore(dateStartMoment)) {
				$("#btnSubmit").button('reset');
		                $("#eventEnd")[0].setCustomValidity("End time needs to be after start time");
		                $("#eventEnd")[0].reportValidity();
				return;
			    }
			    
			    var file_data = $('#eventGroupUpload').prop('files')[0];   
			    var form_data = new FormData();                  
			    form_data.append('group_list', file_data);
			    form_data.append('event_name', name);
			    form_data.append('date', date);
			    form_data.append('start', start);
			    form_data.append('end', end);
			    form_data.append('min', mini);
			    form_data.append('max', maxi);
			    form_data.append('reward', reward);
			    form_data.append('adminid', adminid);
			    $.ajax({
			        url: 'scripts/EventCreate.php', // point to server-side PHP script 
			        dataType: 'text',  // what to expect back from the PHP script, if anything
			        cache: false,
			        contentType: false,
			        processData: false,
			        data: form_data,
			        type: 'post',
			        success: function(data){
			            response = JSON.parse(data); // gets response from the PHP script, if any
			            
			            if(response.success){
			                window.location.href = "manageevents.php?id=" + response.eventid;
			                
			            } else{
					$("#btnSubmit").button('reset');
			                $("#eventName")[0].setCustomValidity(response.message);
			                $("#eventName")[0].reportValidity();
			            }
			        }
			    });
			});
		});
	</script>
</body>
</html>
