<?php 
require_once("config.php");

// get the parameters from URL
$q = $_REQUEST["q"];
$p = $_REQUEST["p"];
$p = explode(",",$p);
if ($q=="insert") {
	
	global $connection;
	switch ($p[0]) {
		case "processes":
			$query = $connection->prepare("INSERT INTO processes (ID, processName, projectID) VALUES (NULL, ?, ?)");
			$query->bind_param('ss',$p[1],$p[2]);
			break;
		case "projects":
			$query = $connection->prepare("INSERT INTO projects (ID, projectName) VALUES (NULL,?)");
			$query->bind_param('s',$p[1]);
			break;
		case "professions":
			$query = $connection->prepare("INSERT INTO professions (ID, professionName,seniority) VALUES (NULL,?,?)");
			$query->bind_param('ss',$p[1],$p[2]);
			break;
		case "persons":
			$query = $connection->prepare("INSERT INTO persons (ID, personName,professionID) VALUES (NULL,?,?)");
			$query->bind_param('ss',$p[1],$p[2]);
			break;
		case "deliverables":
			$query = $connection->prepare("INSERT INTO deliverables (ID, deliverableName,typeID) VALUES (NULL,?,?)");
			$query->bind_param('ss',$p[1],$p[2]);
			break;
		case "deliverable_types":
			$query = $connection->prepare("INSERT INTO deliverable_types (ID, deliverableTypeName) VALUES (NULL,?)");
			$query->bind_param('s',$p[1]);
			break;
	}
	confirm($query);
	if ($query->execute()) {
	    echo "New record created successfully";
	} else {
	    echo "Error";
	}
} else if ($q=="delete") {

	global $connection;
	$query = $connection->prepare("DELETE FROM ".$p[0]." WHERE ID=?");

	confirm($query);
	$query->bind_param('s',$p[1]);

	if ($query->execute()) {
	    echo "Record with ID \"{$p[1]}\" was successfully removed!";
	} else {
	    echo "Error while deleting record!";
	}
}
?>