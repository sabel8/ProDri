<!DOCTYPE html>
<html>
<head>
	<title>Graphs</title>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
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
			pathname += pathname=="/graphs/" ? "index.php":"";
			$('.nav > li > a[href="'+pathname+'"]').parent().addClass('active');
		});
		getNodesAndEdges();
		reviseInAndOutputs();
	</script>

	<!-- Own css stylesheet -->	
	<link rel="stylesheet" href="app.css">
</head>
<body id="body">

	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<span class="navbar-brand"><a href="index.php">Graphs</a></span>
			</div>
			<ul class="nav navbar-nav">
		      <li><a href="/graphs/index.php">Home</a></li>
		      <li><a href="/graphs/tasklist.php">My tasks</a></li>
		    </ul>
		</div>
	</nav>