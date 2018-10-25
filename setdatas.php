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
		case "nodes":
			$query=$connection->prepare("INSERT INTO nodes (ID,txt,xCord,yCord,status,professionID,responsiblePersonID,duration,RACI,processID)
				VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ID=?,txt=?,xCord=?,yCord=?,status=?,professionID=?,responsiblePersonID=?,duration=?,RACI=?,processID=?");
			$query->bind_param("isiiiiiisiisiiiiiisi",$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10]);
			break;
		case "edges":
			$query = $connection->prepare("INSERT INTO edges (ID,fromNodeID,toNodeID) VALUES (?,?,?) ON DUPLICATE KEY UPDATE ID=?,fromNodeID=?,toNodeID=?");
			$query->bind_param("iiiiii",$p[1],$p[2],$p[3],$p[1],$p[2],$p[3]);
			break;
		case "edgeDel":
			$query=$connection->prepare("DELETE FROM edges WHERE ID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "nodeDel":
			$query=$connection->prepare("DELETE FROM nodes WHERE ID=?");
			$query->bind_param("i",$p[1]);
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