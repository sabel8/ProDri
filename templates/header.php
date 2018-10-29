<?php 
if (!isset($_COOKIE["auth"])){
	setcookie("auth","u");
	$_COOKIE["auth"] = "u";
}
if (isset($_GET["auth"])){
	setcookie("auth",$_GET["auth"]);
	$_COOKIE["auth"] = $_GET["auth"];
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

	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<!-- D3 library -->
	<script src="https://d3js.org/d3.v5.min.js"></script>

	<!-- Own js libraries -->
	<script src="scripts/classes.js"></script>
	<script src="scripts/editdb.js"></script>
	<script src="scripts/graphFunctions.js"></script>
	<script src="scripts/externalDataFunctions.js"></script>
	<script src="scripts/taskLists.js"></script>
	<script src="scripts/fetchGraphData.js"></script>
	<script type="text/javascript">
		//desing values for svg elements
		//speaks for themselves...
		const diffFromCursor = 0.98,
		circleRadius = 40,
		edgeWidth = 3,
		selectedNodeColor = "gold",
		rectRoundness = 20,
		rectWidth = 150,
		rectHeight = 75;
		
		//defining the arrays which holds the data
		//of the nodes and the edges
		var nodes = new Array(),
		edges = new Array();
		$(document).ready(function() {
			// get current URL path and assign 'active' class
			var pathname = window.location.pathname;
			pathname += pathname=="/prodri/" ? "index.php":"";
			$('.nav > li > a[href="'+pathname+'"]').parent().addClass('active');
		});

		function switchAuth() {
			var selected = $("#authSelect input[name=authority]:checked").val();
			var url = window.location.pathname;
			url += "?auth="+selected;
			window.location.href = url;
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
				<span class="navbar-brand"><a href="index.php">ProDri</a></span>
			</div>
			<div class="collapse navbar-collapse" id="myNavbar">
				<ul class="nav navbar-nav">
					<li><a href="/prodri/index.php">Home</a></li>
					<li><a href="/prodri/tasklist.php">My tasks</a></li>
					<li><a href="/prodri/editdatabase.php">Edit database</a></li>
					<li><a href='/prodri/manage.php'>Manage recommendations</a></li>
			    </ul>
			    <ul class="nav navbar-nav navbar-right">
			    	<li>
			    		<a>
			    			<form id="authSelect">
			    				<label>
			    					<input id="projectManagerAuth" type="radio" name="authority" value="pm" onchange="switchAuth()"
			    					<?php if($_COOKIE["auth"]=="pm") echo "checked" ?>>
			    					Project manager
			    				</label>
								<label>
									<input id="processOwnerAuth" type="radio" name="authority" value="po" onchange="switchAuth()" 
									<?php if($_COOKIE["auth"]=="po") echo "checked" ?>>
									Process owner
								</label>
								<label>
									<input id="userAuth" type="radio" name="authority" value="u" onchange="switchAuth()"
									<?php if($_COOKIE["auth"]=="u") echo "checked" ?>>
									User
								</label>
							</form>
						</a>
					</li>
			    </ul>
			</div>
		</div>
	</nav>