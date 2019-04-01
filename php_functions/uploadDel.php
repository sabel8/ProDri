<?php
require_once("../config.php");
global $connection;
if((file_exists($_FILES['fileToUpload']['tmp_name']) AND is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) 
	AND isset($_POST['nodeID'])) {
	$nodeID = $_POST['nodeID'];
	//check file size
	if ($_FILES["fileToUpload"]["size"] > 50000000) { //50 MB
		echo "Sorry, your file is too large.";
		$uploadOk = 0;
	}
	//checks if person is valid and fetch ID of the process
	$processID = getRowsOfQuery("SELECT processID FROM nodes WHERE ID=$nodeID
	AND responsiblePersonID=".intval($_SESSION['userID']))[0];
	if($processID==""){
		exit();//not matching userID and nodeID
	}
	$target_dir = "../deliverables/$processID/$nodeID";
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}
	
} else {
	echo "Please, choose a file to upload!";
	exit();//false call
}
$fileName = basename($_FILES["fileToUpload"]["name"]);
$target_file = $target_dir . "/" . $fileName;
$uploadOk = 1;
//upload file
if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
	//create record for the file
	if (! mysqli_query($connection,"INSERT INTO deliverables (name,nodeID) VALUES ('$fileName',$nodeID)")) {
		echo "SQL ERROR: ".mysqli_error($connection);
	}
	echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
} else {
	echo "Sorry, there was an error uploading your file.";
}
?>