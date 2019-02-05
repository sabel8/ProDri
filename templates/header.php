<?php 
//initialize auth session variable
if (!isset($_SESSION["auth"])){
	$_SESSION["auth"] = "u";
}

//sets the auth session variable if get is detected
if (isset($_GET["auth"])){
	$_SESSION["auth"] = $_GET["auth"];
}
?>


<!DOCTYPE html>
<html>
<head>
	<title>ProDri</title>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
	

	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
	
	<!-- APIs for the calendar -->
	<link rel='stylesheet' href='fullcalendar/fullcalendar.css' />
	<script src='scripts/moment.min.js'></script>
	<script src='fullcalendar/fullcalendar.js'></script>
	<script src='fullcalendar/locale/en-gb.js'></script>
	<script src="datetimeselector/build/js/bootstrap-datetimepicker.min.js"></script>
	<link href="datetimeselector/build/css/bootstrap-datetimepicker.css" rel="stylesheet">

	<!-- Latest compiled JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<!-- D3 library -->
	<script src="https://d3js.org/d3.v5.min.js"></script>

	<!-- Own js libraries -->
	<script src="scripts/classes.js"></script>
	<script src="scripts/editdb.js"></script>
	<script src="scripts/graphFunctions.js"></script>
	<script src="scripts/externalDataFunctions.js"></script>
	<script src="scripts/fetchGraphData.js"></script>
	<script src="scripts/schedule.js"></script>
	<script src="scripts/newProcess.js"></script>
	<script type="text/javascript">
		//desing values for svg elements
		//speaks for themselves...
		const diffFromCursor = 0.98,
		edgeWidth = 3,
		selectedNodeColor = "gold",
		rectRoundness = 20,
		rectWidth = 150,
		rectHeight = 75;
		
		//defining the arrays which holds the data
		//of the nodes and the edges
		var nodes = new Array(),
		edges = new Array();

		//sets the background for the active menu item
		$(document).ready(function() {
			// get current URL path and assign 'active' class
			var pathname = window.location.pathname;
			pathname += pathname=="/prodri/" ? "index.php":"";
			$('.nav > li > a[href="'+pathname+'"]').parent().addClass('active');
		});

		//sets the php session variable for the authorization
		function switchAuth() {
			var selected = $("#authSelect input[name=authority]:checked").val();
			var url = window.location.pathname;
			url += "?auth="+selected;
			window.location.href = url;
		}

		//sets the tooltip effect
		$(document).ready(function(){
    		$('[data-toggle="tooltip"]').tooltip();   
		});

	</script>
	

	<!-- Own css stylesheet -->	
	<link rel="stylesheet" href="app.css">
</head>
<body>

	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		      </button>
				<span class="navbar-brand"><a href="index.php">ProDri</a></span>
			</div>
			<div class="collapse navbar-collapse" id="myNavbar">
				<ul class="nav navbar-nav">
					<li><a href="/prodri/index.php">Home</a></li>
					<li><a href="/prodri/tasklist.php">My tasks</a></li>
					<?php if($_SESSION["auth"]!="u"){
						echo '<li><a href="/prodri/editdatabase.php">Edit database</a></li>';
					} ?>
					<?php if($_SESSION["auth"]!="u"){
						echo "<li><a href='/prodri/manage.php'>";
						if($_SESSION["auth"]=="pm"){
							echo "Assignments";
						} else {
							echo "Manage recommendations";
						}
					} else {
						echo "<li><a href='/prodri/schedule.php'>My schedule</a>";
					} ?>
					
					</a></li>
			    </ul>
			    <ul class="nav navbar-nav navbar-right">
			    	<li>
			    		<a>
			    			<form id="authSelect">
			    				<label>
			    					<input id="projectManagerAuth" type="radio" name="authority" value="pm" onchange="switchAuth()"
			    					<?php if($_SESSION["auth"]=="pm") echo "checked" ?>>
			    					Project manager
			    				</label>
								<label>
									<input id="processOwnerAuth" type="radio" name="authority" value="po" onchange="switchAuth()" 
									<?php if($_SESSION["auth"]=="po") echo "checked" ?>>
									Process owner
								</label>
								<label>
									<input id="userAuth" type="radio" name="authority" value="u" onchange="switchAuth()"
									<?php if($_SESSION["auth"]=="u") echo "checked" ?>>
									User
								</label>
							</form>
						</a>
					</li>
			    </ul>
			</div>
		</div>
	</nav>