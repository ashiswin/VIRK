<?php
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
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="libs/bootstrap-datetimepicker-master/build/css/bootstrap-datetimepicker.min.css">
	
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.2/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.2/bootstrap-material-design.umd.min.js"></script>
	<script src="scripts/moment.js"></script>
	<script type="text/javascript" src="libs/bootstrap-datetimepicker-master/build/js/bootstrap-datetimepicker.min.js"></script>
	<link rel="stylesheet" href="css/createevent.css" type="text/css">
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
  		<title>Creating an Event</title>
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
							<b>Create an Event</b>
							<br style="display:block; content: ''; margin-top:5px">
							<a href="manageevent.php">Manage an Event</a>
							<br style="display:block; content: ''; margin-top:5px">
						</p>
						<div class="card-text col-md-offset-4" style="padding-left:2%">
		 					<img id="logo" src="img/logo.png" width="40%">
                                <form enctype="multipart/form-data" action="" method="post" id="createform">
                                    <div class="row col-md-12">
                                        <table style="border: none; margin-top: 12%">
                                            <tr>
                                                <td class="form-title" style="width: 30%">Event Title</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning" style="width:120%;">
                                                        <input type="text" name="event_name" id="event_name" class="form-control" placeholder="Enter event name"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="form-title" style="width: 30%">Date</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning" style="width:120%;">
                                                        <input type="date" name="date" id="date" class="form-control" placeholder="Enter date of event" data-provide="datepicker"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr style="width:120%">
                                                <td class="form-title" style="width: 30%">Start</td>
                                                <td>
                                                    <div class="form-group has-warning in-group date" style="width:120%">
                                                        <input type="time" name="start" id="start" class="form-control" placeholder="Select start time"/>
                                                    </div>
                                                </td>
                                                <td class="form-title" style="padding-left:10%">End</td>
                                                <td>
                                                    <div class="form-group has-warning in-group date" style="padding-left:30%;width:147%;">
                                                        <input type="time" name="end" id="end" class="form-control" placeholder="Select end time"/>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="form-title" style="width: %">Group List</td>
                                                <td colspan=3>
                                                    <div class="form-group has-warning" style="width:120%;">
                                                        <!-- MAX_FILE_SIZE must precede the file input field -->
                                                        <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                                                        <input accept=".csv" type="file" name="group_list" id="group_list" placeholder="Upload CSV file" class="form-control">
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <button id="confirm-button" class="btn btn-raised btn-danger col-md-offset-4 col-md-4" style="margin-top: 5%">Confirm
                                        </button>
                                    </div>
                                </form>	
						    </div>
	  				</div>
	  			</div>
			</div>
		</div>
	</body>
	<script type="text/javascript">
		var adminid = <?php echo $_SESSION['adminid']; ?>;
		
		$("#confirm-button").click(function(e) {            
		    e.preventDefault();
            
		    var file_data = $('#group_list').prop('files')[0];   
		    var form_data = new FormData();                  
		    form_data.append('group_list', file_data);
		    form_data.append('event_name', $("#event_name").val());
		    form_data.append('date', $("#date").val());
		    form_data.append('start', $("#start").val());
		    form_data.append('end', $("#end").val());
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
		                        document.getElementById("createform").reset();
		                        window.location.href = "manageevent.php?id=" + response.eventid;
		                        
		                    } else{
		                        $("#event_name")[0].setCustomValidity(response.message);
		                        $("#event_name")[0].reportValidity();
		                    }
		                }
		     });
		});
    </script>
</html>
