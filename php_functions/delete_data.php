<?php 
require_once("../config.php");

//get parameters from POST
$q = $_POST["q"];
switch ($q) {
	case "deleteObjectsOfProcess":
		$p=$_POST["p"];

		//deleting all nodes of this process
		global $connection;
		$query=$connection->prepare("DELETE FROM nodes WHERE processID=?");
		confirm($query);
		$query->bind_param("i",$p);
		if ($query->execute()) {
		    echo "Records from nodes with process ID \"{$p}\" was successfully removed!";
		} else {
		    echo "Error while deleting records!";

		}

		//deleting all edges of this process
		global $connection;
		$query=$connection->prepare("DELETE FROM edges WHERE processID=?");
		confirm($query);
		$query->bind_param("i",$p);
		if ($query->execute()) {
		    echo "Records from edges with process ID \"{$p}\" was successfully removed!";
		} else {
		    echo "Error while deleting records!";
		}
		break;
	
	default:
		# code...
		break;
}


?>