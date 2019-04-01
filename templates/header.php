<?php
if (isset($_POST["logout"])){
	session_unset();
	setcookie('loginMessage',"Successfully logged out!");
}
//initialize auth session variable
if (!isset($_SESSION["auth"]) or !isset($_SESSION['userID']) or !isset($_SESSION['personName'])){
	setcookie('redirectedFrom',$_SERVER['PHP_SELF']);
	setcookie('loginMessage',"You have to log in first!");
	header('Location: '.$GLOBALS['HTTP_HOST'].DS.'prodri'.DS.'login.php');
	exit;
} else {
	unset($_COOKIE['redirectedFrom']);
	unset($_COOKIE['loginMessage']);
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
	<?php
	//connecting the right javascript files
	//for the corresponding page
	$uri=explode('?', $_SERVER['REQUEST_URI'], 2)[0];
	$root="prodri";
	switch($uri){
		case "/$root/manage.php":
			echo '<script src="scripts/manage.js"></script>';break;
		case "/$root/newProcess.php":
			echo '<script src="scripts/newProcess.js"></script>
			<script src="scripts/editdb.js"></script>';break;
		case "/$root/editdatabase.php":
			echo '<script src="scripts/editdb.js"></script>';break;
		case "/$root/schedule.php":
			echo '<script src="scripts/schedule.js"></script>';break;
		case "/$root/linemanager.php":
			echo '<script src="scripts/linemanager.js"></script>';break;
	}
	?>
	<script src="scripts/classes.js"></script>
	<script src="scripts/graphFunctions.js"></script>
	<script src="scripts/externalDataFunctions.js"></script>
	<script src="scripts/fetchGraphData.js"></script>
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

		//params[0] = 'table name'
		//rest is the inserted values
		function runInsert(params) {
			var response;
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					console.log("Parameters: "+params.toString());
					response = this.responseText;
				}
			};
			xmlhttp.open("GET", "php_functions/setdatas.php?q=insert&p="+params.toString(), false);
			xmlhttp.send();
			return response;
		}

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
				<span class="navbar-brand"><a href="tasklist.php"><b><i>ProDri</i></b></a></span>
			</div>
			<div class="collapse navbar-collapse" id="myNavbar">
				<ul class="nav navbar-nav">
					<!-- <li><a href="/prodri/index.php">Home</a></li> -->
					<li><a href="/prodri/tasklist.php">My tasks</a></li>
					<li><a href='/prodri/schedule.php'>My schedule</a></li>
					<!-- <?php if($_SESSION["auth"]!="u"){
						echo '<li><a href="/prodri/editdatabase.php">Edit database</a></li>';
					} ?> -->
					<?php if($_SESSION["auth"]=="pm" or $_SESSION["auth"]=="po"){
						echo "<li><a href='/prodri/manage.php'>";
						if($_SESSION["auth"]=="pm"){
							echo "Assignments";
						} else {
							echo "Manage recommendations";
						}
						echo "</a></li>";
					} ?>
					
					<?php if($_SESSION["auth"]=="lm"){
						echo "<li><a href='/prodri/linemanager.php'>Line manager</a></li>";
					} ?>
			    </ul>
					<form method="post" id="logoutForm">
						<ul class="nav navbar-nav navbar-right">
							<li>
								<a>
									Welcome <?php /* print_r($_POST); */ echo $_SESSION['personName']?>!
								</a>
							</li>
							<li>
								<a href="login.php?p=logout">
									<span class="glyphicon glyphicon-log-out"></span> Logout
								</a>
							</li>
						</ul>
					</form>
			</div>
		</div>
	</nav>