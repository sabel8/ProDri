<?php
require_once("../config.php");
global $connection;
echo "<a href='../tasklist.php'>GO BACK TO TASKLIST</a><br><br>";
if(isset($_POST['submit'])) {
	$nodeID = $_POST['submit'];
	$processID = getRowsOfQuery("SELECT processID FROM nodes WHERE ID=$nodeID
	AND responsiblePersonID=".intval($_SESSION['userID']))[0];
	if($processID==""){
		exit();//not matching userID and nodeID
	}
	$target_dir = "../deliverables/$processID/$nodeID";
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}
	if ($_FILES["fileToUpload"]["size"] > 5000000) { //5 MB
		echo "Sorry, your file is too large.";
		$uploadOk = 0;
	}
} else {
	exit();//false call
}
$fileName = basename($_FILES["fileToUpload"]["name"]);
$target_file = $target_dir . "/" . $fileName;
$uploadOk = 1;

if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
	if (! mysqli_query($connection,"INSERT INTO deliverables (name,nodeID) VALUES ('$fileName',$nodeID)")) {
		echo "SQL ERROR: ".mysqli_error($connection);
	}
	echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
} else {
	echo "Sorry, there was an error uploading your file.";
}
?>