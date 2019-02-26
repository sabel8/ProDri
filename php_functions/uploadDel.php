<?php
require_once("../config.php");
if(isset($_POST['submit'])) {
	$nodeID = $_POST['submit'];
	$processID = getRowsOfQuery("SELECT processID FROM nodes WHERE ID=$nodeID
	AND responsiblePersonID=".intval($_SESSION['userID']))[0];
	if($processID==""){
		exit();//HACK
	}
	$target_dir = "../deliverables/$processID/$nodeID";
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}
} else {
	exit();//false call
}

$target_file = $target_dir . "/" . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;

if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
	echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
} else {
	echo "Sorry, there was an error uploading your file.";
}
?>