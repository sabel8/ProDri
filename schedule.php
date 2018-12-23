<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
$username="John Smith";
$devmode=false;
($devmode==true?print_r(count($_POST)!=0?$_POST:"No post set"):"");

//makes a new record of the exception in the database
if($_POST) {
	global $connection;
	$query = $connection->prepare("INSERT INTO timeslot_exceptions (personID,avaliable,title,startTime,endTime)
	VALUES (1,?,?,?,?)");/*todo personID -> dynamic*/
	$avalibility=isset($_POST["avalibility"]);
	$title=$_POST["eventType"];
	$fromDT=$_POST["fromDateTime"];
	$toDT=$_POST["toDateTime"];
	$query->bind_param("isss",$avalibility,$title,$fromDT,$toDT);
	if ($query->execute()) {
	} else {
	  die("Error while adding a new timeslot exception event!");
	}
}

//include("php_functions/loadEvents.php");
?>

<div class="container">
	<div class="well">
		<!-- Trigger the modal with a button -->
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newEventModal">New event</button>
		<h2>My schedule (<?php echo $username;?>)</h2>
		<div id="calendar"></div>
	</div>
	

	<!-- Modal -->
	<div id="newEventModal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal">&times;</button>
			<h4 class="modal-title">New event</h4>
		</div>
		<form id="newEventForm" method="post">
			<div class="modal-body">
				<div class="form-group">
					<label for="type">Type (name):</label>
					<input type='text' name="eventType" class="form-control" id="type">
				</div>
				<label class="checkbox-inline"><input name="avalibility" type="checkbox" value="">Avaliable for work</label>
				<div class="form-group">
						<label for="fromDateTime">Event start:</label>
					<div class='input-group date' id='datetimepicker1'>
						<input type='text' name="fromDateTime" class="form-control" id="fromDateTime" onchange="setMinDate()" />
						<span class="input-group-addon">
							<span class="glyphicon glyphicon-calendar"></span>
						</span>
					</div>
				</div>
				<div class="form-group">
						<label for="toDateTime">Event end:</label>
					<div class='input-group date' id='datetimepicker2'>
						<input type='text' name="toDateTime" class="form-control" id="toDateTime" />
						<span class="input-group-addon">
							<span class="glyphicon glyphicon-calendar"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<input type="submit" class="btn btn-primary" data-dismiss="modal" onclick="submitNewEvent()" value="Save"/>
				<button type="button" class="btn btn-default" style="float:left" data-dismiss="modal">Close</button>
			</div>
		</form>
		</div>

	</div>
	</div>

<?php include(TEMPLATE.DS."footer.php")?>