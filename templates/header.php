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
	<script src="d3.min.js"></script>

	<!-- Own js libraries -->
	<script src="classes.js"></script>
	<script src="editdb.js"></script>
	<script src="graphFunctions.js"></script>
	<script src="externalDataFunctions.js"></script>
	<script src="taskLists.js"></script>
	<script src="fetchGraphData.js"></script>
	<script type="text/javascript">
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
		getNodesAndEdges();
		reviseInAndOutputs();
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
			    </ul>
			</div>
		</div>
	</nav>