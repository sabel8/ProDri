<?php
require_once("config.php");
$_SESSION['userID']=1; //this is for developing only
$userID=$_SESSION['userID'];
$devmode=false;

//database manipulation according to POST
if($_POST) {
	global $connection;
	if(isset($_POST['deleteEvent'])) {
		$query = $connection->prepare("DELETE FROM timeslot_exceptions WHERE ID=? AND personID=$userID");
		$query->bind_param("is",$_POST["deleteEvent"],$userID);
		if (!$query->execute()) {
			die("Error while deleting a timeslot exception event!");
		}

	} else if (isset($_POST['saveEvent'])) {
		if (!isset($_POST['fromDateTimeEdit'])) {
			$avalibility=isset($_POST["avalibilityEdit"]);
			$title=$_POST["eventType"]==""?"NO TITLE GIVEN":$_POST["eventType"];
			$toDT=$_POST["toDateTimeEdit"];
			$query = $connection->prepare("UPDATE timeslot_exceptions SET title=?,avaliable=?,endTime=? WHERE ID=? AND personID=$userID");
			$query->bind_param("sisi",$title,$avalibility,$toDT,$_POST["saveEvent"]);
			if (!$query->execute()) {
				print_r($_POST);
				die("Error while updating a timeslot exception event happening now! ".mysqli_error($connection)."<br>");
			}
		} else {
			$avalibility=isset($_POST["avalibilityEdit"]);
			$title=$_POST["eventType"]==""?"NO TITLE GIVEN":$_POST["eventType"];
			$fromDT=$_POST["fromDateTimeEdit"];
			$toDT=$_POST["toDateTimeEdit"];
			$query = $connection->prepare("UPDATE timeslot_exceptions SET title=?,avaliable=?,startTime=?,endTime=? WHERE ID=? AND personID=$userID");
			$query->bind_param("sissi",$title,$avalibility,$fromDT,$toDT,$_POST["saveEvent"]);
			if (!$query->execute()) {
				print_r($_POST);
				die("Error while updating a timeslot exception event! ".mysqli_error($connection)."<br>");
			}
		}
	} else {
		$query = $connection->prepare("INSERT INTO timeslot_exceptions (personID,avaliable,title,startTime,endTime)
		VALUES (?,?,?,?,?)");/*todo personID -> dynamic*/
		$avalibility=isset($_POST["avalibility"]);
		$title=$_POST["eventType"]==""?"NO TITLE GIVEN":$_POST["eventType"];
		$fromDT=$_POST["fromDateTime"];
		$toDT=$_POST["toDateTime"];
		$query->bind_param("sisss",$userID,$avalibility,$title,$fromDT,$toDT);
		if (!$query->execute()) {
			die("Error while adding a new timeslot exception event!");
		}
	}

	if ($devmode==true) {
		print_r($_POST);
	} else {
		// Redirect to this page.
		header("Location: " . $_SERVER['REQUEST_URI']);
		exit();
	}
}


include(TEMPLATE.DS."header.php");


//include("php_functions/loadEvents.php");
?>

<div class="container">
	<div class="well">
		<!-- Trigger the modal with a button -->
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newEventModal">New event</button>
		<?php
		if ($devmode==true) {
			echo '<button type="button" class="btn btn-default" onclick="see()">Events</button>';
		}
		?>		
		<h2>My schedule (<?php echo getRowsOfQuery("SELECT personName FROM persons WHERE ID=$userID")[0];?>)</h2>
		<div id="calendar"></div>
	</div>
	

	<!-- New Event Modal -->
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
							<input autocomplete="off" type='text' name="fromDateTime" class="form-control" id="fromDateTime" onchange="setMinDate()" />
							<span class="input-group-addon">
								<span class="glyphicon glyphicon-calendar"></span>
							</span>
						</div>
					</div>
					<div class="form-group">
							<label for="toDateTime">Event end:</label>
						<div class='input-group date' id='datetimepicker2'>
							<input autocomplete="off" type='text' name="toDateTime" class="form-control" id="toDateTime" />
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

	<!-- Edit Event Modal -->
	<div id="eventEditModal" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><b>Edit event</b></h4>
			</div>
			<form id="newEventForm" method="post">
				<div class="modal-body">
					<div class="form-group">
						<label for="type">Type (name):</label>
						<input id="eventNameInput" type='text' name="eventType" class="form-control" id="type">
					</div>
					<label class="checkbox-inline"><input id="avaliableEvent" name="avalibilityEdit" type="checkbox">Avaliable for work</label>
					<div class="form-group">
							<label for="fromDateTime">Event start:</label>
						<div class='input-group date' id='datetimepicker3'>
							<input autocomplete="off" type='text' name="fromDateTimeEdit" class="form-control" id="fromDateTimeEdit"
								onchange="setMinDate()" />
							<span class="input-group-addon">
								<span class="glyphicon glyphicon-calendar"></span>
							</span>
						</div>
					</div>
					<div class="form-group">
							<label for="toDateTime">Event end:</label>
						<div class='input-group date' id='datetimepicker4'>
							<input autocomplete="off" type='text' name="toDateTimeEdit" class="form-control" id="toDateTimeEdit" />
							<span class="input-group-addon">
								<span class="glyphicon glyphicon-calendar"></span>
							</span>
						</div>
					</div>
				</div>
				<div class="modal-footer">
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<button id="saveEventButton" type="submit" name="saveEvent" 
						class="btn btn-success">Save</button>
				</form>
				</div>
			</form>
			</div>

		</div>
	</div>


	<!-- Event Details Modal -->
	<div id="eventDetailsModal" class="modal fade" role="dialog">
		<div class="modal-dialog">

			<!-- Modal content-->
			<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 id="modalEventTitle" class="modal-title">ERROR</h4>
			</div>
			<div class="modal-body">
				<p id="modalEventBody">ERROR</p>
			</div>
			<div class="modal-footer">
				<button id="editEventButton" style="float:left;margin-right: 10px;" data-dismiss="modal" onclick="openEditModal();" class="btn btn-warning">Edit</button>
				<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
					<button id="deleteEventButton" style="float:left" type="submit" name="deleteEvent"
						class="btn btn-danger">Delete</button>
				</form>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
			</div>

		</div>
	</div>

<?php include(TEMPLATE.DS."footer.php")?>