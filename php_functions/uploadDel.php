<?php
require_once("../config.php");
global $connection;
if((file_exists($_FILES['fileToUpload']['tmp_name']) AND is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) 
	AND isset($_POST['nodeID'])) {
	$nodeID = $_POST['nodeID'];
	//check file size
	if ($_FILES["fileToUpload"]["size"] > 50000000) { //50 MB
		echo '<div class="alert alert-danger alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		Sorry, there was an error uploading your file.</div>';
		return;
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
	echo '<div class="alert alert-warning alert-dismissible fade in">
	<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
	Please choose a file to upload!</div>';
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
	echo '<div class="alert alert-success alert-dismissible fade in">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    The file '. basename( $_FILES["fileToUpload"]["name"]). ' has been uploaded. Please refresh the page.</div>';
} else {
	echo '<div class="alert alert-danger alert-dismissible fade in">
	<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
	Sorry, there was an error uploading your file.</div>';
}
?>