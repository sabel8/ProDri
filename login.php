<?php
require_once("config.php");
if (isset($_GET["p"])){
	if ($_GET["p"]=="logout") {
		session_unset();
		unset($_COOKIE['redirectedFrom']);
		unset($_COOKIE['loginMessage']);
	}
}

unset($_SESSION['userID']);
unset($_SESSION['auth']);
$wrongCredentials = false;
if (isset($_POST['username']) && isset($_POST['password'])) {
	global $connection;
	$query = $connection->prepare("SELECT ID,personName,authority FROM persons WHERE username=? AND password=md5(?)");
	$query->bind_param("ss",$_POST['username'],$_POST['password']);
	$query->execute();
	$query->store_result();
	if($query->num_rows == 0) {
		$wrongCredentials=true;
	} else {
		$query->bind_result($id,$name,$auth);
		$query->fetch();
		$_SESSION['userID'] = $id;
		$_SESSION['personName'] = $name;
		switch ($auth) {
			case 0:
				$_SESSION['auth'] = 'u';break;
			case 1:
				$_SESSION['auth'] = 'pm';break;
			case 2:
				$_SESSION['auth'] = 'po';break;
			case 3:
				$_SESSION['auth'] = 'lm';break;
			default: die("Wrong authority!");
		}
		if (isset($_COOKIE['redirectedFrom'])) {
			header("Location: ".$GLOBALS['HTTP_HOST'].urldecode($_COOKIE['redirectedFrom']));
			exit;
		} else {
			header("Location: ".$GLOBALS['HTTP_HOST'].DS."prodri".DS."tasklist.php");
			exit;
		}
	}
}/* 
if ($wrongCredentials===true) {
	$_COOKIE['loginMessage']=""
} */
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
		
		<!-- Latest compiled JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<!-- Own css stylesheet -->	
		<link rel="stylesheet" href="app.css">
	</head>
	<body>
		<div class="container">
		<h1 style='color:white;text-align:center'><i>ProDri</i></h1>
		
			<div class="col-lg-4"></div>
			<div class="col-lg-4">
				<?php
				if (isset($_COOKIE['loginMessage']) && $wrongCredentials!=true) {
					echo '<div class="alert alert-warning alert-dismissible fade in">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.
					$_COOKIE['loginMessage'].
					'</div>';
				} else if ($wrongCredentials) {
					echo '<div class="alert alert-danger alert-dismissible fade in">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					Wrong username or password!</div>';
				}
				?>
				<div class="panel panel-default ">
					<div class="panel-heading">
						<h2>Sign in</h2>
					</div>
					<div class="panel-body">
						<form method="post">
							<div class="form-group">
								<label>Username</label>
								<input name="username" class="form-control" type="text" required>
							</div> <!-- form-group// -->
							<div class="form-group">
								<label>Your password</label>
								<input class="form-control" type="password" name="password" required>
							</div> <!-- form-group// --> 
							<div class="form-group"> 
								<div class="checkbox">
									<label> <input type="checkbox" disabled> Save password (beta)</label>
								</div> <!-- checkbox .// -->
							</div> <!-- form-group// -->  
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-block"> Login  </button>
							</div> <!-- form-group// -->                                                           
						</form>
					</div>
					Néhány példaadat:<br><br>
					user | passwd | auth <hr>
					john | prodri | user <br>
					slay | prodri | process owner<br>
					michael | prodri |  project manager <br>
					big  | prodri | line manager <br>
				</div>
			</div>
			<div class="col-lg-4"></div>
			
		</div>
	</body>
</html>