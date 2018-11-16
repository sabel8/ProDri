<?php 
require_once("../config.php");

// get the parameters from URL
$q = $_REQUEST["q"];
$p = $_REQUEST["p"];
if ($q=="insert") {
	$p = explode(",",$p);
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
			if ($p[7]==0){$p[7]=NULL;}
			$query=$connection->prepare("INSERT INTO nodes (nodeID,txt,xCord,yCord,status,professionID,responsiblePersonID,duration,RACI,processID)
				VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ID=?,txt=?,xCord=?,yCord=?,status=?,professionID=?,responsiblePersonID=?,duration=?,RACI=?,processID=?");
			$query->bind_param("isiiiiiisiisiiiiiisi",$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10]);
			break;
		case "edges":
			$query = $connection->prepare("INSERT INTO edges (fromNodeID,toNodeID,processID) VALUES (?,?,?)");
			$query->bind_param("iii",$p[1],$p[2],$p[3]);
			break;
		case "edgeDel":
			$query=$connection->prepare("DELETE FROM edges WHERE ID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "nodeDel":
			$query=$connection->prepare("DELETE FROM nodes WHERE ID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "recNodes":
			$query = $connection->prepare("INSERT INTO recommended_nodes (nodeID,name,xCord,yCord,status,professionID,raci,duration,deliverableID,recommendationID) VALUES (?,?,?,?,?,?,?,?,?,?)");
			$query->bind_param("isiiiisiii",$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10]);
			break;
		case "recEdges":
			$query = $connection->prepare("INSERT INTO recommended_edges (ID,fromNodeID,toNodeID,recommendationID) VALUES (NULL,?,?,?)");
			$query->bind_param("iii",$p[1],$p[2],$p[3]);
			break;
		case "recNodeDel":
			$query = $connection->prepare("DELETE FROM recommended_nodes WHERE recommendationID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "recEdgeDel":
			$query = $connection->prepare("DELETE FROM recommended_edges WHERE recommendationID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "recDel":
			$query = $connection->prepare("DELETE FROM recommendations WHERE ID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "nodeProcDel":
			//  p[1] = recomID
			$query = $connection->prepare("DELETE FROM nodes WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?)");
			$query->bind_param("i",$p[1]);
			break;
		case "nodeRecToLive":
			//  p[1] = recomID
			$query = $connection->prepare("INSERT INTO nodes (nodeID,txt,xCord,yCord,status,professionID,duration,RACI,processID)
				SELECT rn.nodeID,rn.name,rn.xCord,rn.yCord,rn.status,rn.professionID,rn.duration,rn.raci,re.forProcessID
				FROM recommended_nodes rn 
				LEFT JOIN recommendations re ON rn.recommendationID=re.ID
				WHERE rn.recommendationID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "edgeProcDel":
			//  p[1] = recomID
			$query = $connection->prepare("DELETE FROM edges WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?)");
			$query->bind_param("i",$p[1]);
			break;
		case "edgeRecToLive":
			//  p[1] = recomID
			$query = $connection->prepare("INSERT INTO edges (fromNodeID,toNodeID,processID)
				SELECT r.fromNodeID,r.toNodeID,re.forProcessID
				FROM recommended_edges r 
				LEFT JOIN recommendations re ON r.recommendationID=re.ID
				WHERE r.recommendationID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "wipeWithdrawNodes":
			$query = $connection->prepare("DELETE FROM withdraw_nodes WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?)");
			$query->bind_param("i",$p[1]);
			break;
		case "wipeWithdrawEdges":
			$query = $connection->prepare("DELETE FROM withdraw_edges WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?)");
			$query->bind_param("i",$p[1]);
			break;
		case "copyNodesToWithdraw":
			//p[1] = recomID
			$query = $connection->prepare("INSERT withdraw_nodes (SELECT * FROM nodes 
				WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?))");
			$query->bind_param("i",$p[1]);
			break;
		case "copyEdgesToWithdraw":
			//p[1] = recomID
			$query = $connection->prepare("INSERT withdraw_edges (SELECT * FROM edges 
				WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?))");
			$query->bind_param("i",$p[1]);
			break;
		case "allRecNotLive":
			$query = $connection->prepare("UPDATE recommendations r SET isLive=0");
			break;
		case "makeRecLive":
			//p[1] = recomID
			$query = $connection->prepare("UPDATE recommendations r SET isLive=1 WHERE ID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "log":
			//p[1] = typeID
			//p[2] = recomID
			if ($p[1]==16){
				$query=$connection->prepare("INSERT INTO system_message_log (typeID, receiverTypeID, text, processID)
				VALUES (16, 2, (SELECT concat(personName,' recommended a new process, please review it.') 
				FROM persons p,recommendations r WHERE p.ID=r.submitterPersonID AND r.ID=?), 
				(SELECT p.ID FROM processes p, recommendations r WHERE r.forProcessID=p.ID AND r.ID=?));");
				$query->bind_param("ii",$p[2],$p[2]);
			} else if($p[1]==17) {
				$query=$connection->prepare("INSERT INTO system_message_log (typeID, receiverTypeID, text, processID)
				VALUES (17,1,(SELECT concat('\"',processName,'\" process modification by \"',personName,'\" was approved by PO.') 
				FROM processes p, recommendations r,persons per 
				WHERE p.ID=r.forProcessID AND r.ID=? AND per.ID=r.submitterPersonID),
				(SELECT p.ID FROM processes p, recommendations r WHERE r.forProcessID=p.ID AND r.ID=?))");
				$query->bind_param("ii",$p[2],$p[2]);
			} else if($p[1]==18){
				$query=$connection->prepare("INSERT INTO system_message_log (typeID, receiverTypeID, text, processID)
				VALUES (18,1,(SELECT concat('\"',processName,'\" process modification by \"',personName,'\" was declined by PO.') 
				FROM processes p, recommendations r,persons per 
				WHERE p.ID=r.forProcessID AND r.ID=? AND per.ID=r.submitterPersonID),
				(SELECT p.ID FROM processes p, recommendations r WHERE r.forProcessID=p.ID AND r.ID=?))");
				$query->bind_param("ii",$p[2],$p[2]);
			}
	}
	confirm($query);
	if ($query->execute()) {
		echo "New record created successfully";
	} else {
	    echo "Error: ".mysqli_error($connection);
	}


} else if ($q=="delete") {

	$p = explode(",",$p);
	global $connection;
	$query = $connection->prepare("DELETE FROM ".$p[0]." WHERE ID=?");

	confirm($query);
	$query->bind_param('s',$p[1]);

	if ($query->execute()) {
	    echo "Record with ID \"{$p[1]}\" was successfully removed!";
	} else {
	    echo "Error while deleting record!";
	}


} else if ($q=="recomStatusChange"){
	global $connection;
	$query = $connection->prepare("UPDATE recommendations SET status=?,isLive=0 WHERE ID=?");

	confirm($query);
	$query->bind_param('ii',$_POST["to"],$_POST["p"]);

	if ($query->execute()) {
	    echo "Recommendation submitted successfully!";
	} else {
	    echo "Error while updating record!";
	}


} else if ($_POST["q"]=="newRecom"){
	global $connection;
	$query = $connection->prepare("INSERT INTO recommendations (ID,submitterPersonID,forProcessID,status) VALUES (NULL,?,?,0)");

	confirm($query);
	$query->bind_param('ii',$_POST["from"],$_POST["p"]);

	if ($query->execute()) {
		$query=$connection->prepare("SELECT ID FROM recommendations WHERE submitterPersonID=? AND forProcessID=?  AND status=0 ORDER BY ID DESC");
		confirm($query);
		$query->bind_param('ii',$_POST["from"],$_POST["p"]);
		$query->execute();
		$result = $query->get_result();
		while ($row = $result->fetch_assoc()){
			echo implode(",", $row);
			return;
		}
		
	} else {
	    echo "Error while updating record!";
	}



} else if ($_POST["q"]=="withdraw") {
	global $connection;
	$recomID = $_POST["p"];
	$query = $connection->prepare("DELETE FROM nodes WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?)");
	$query->bind_param("i",$recomID);
	if ($query->execute()) {
		echo "Recommendation nodes deleted successfully!";
		$query = $connection->prepare("INSERT INTO nodes (SELECT * FROM withdraw_nodes 
		WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?))");
		$query->bind_param("i",$recomID);
		if ($query->execute()) {
			$query = $connection->prepare("DELETE FROM edges 
			WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?)");
			$query->bind_param("i",$recomID);
			if ($query->execute()) {
				$query = $connection->prepare("INSERT INTO edges (SELECT * FROM withdraw_edges 
				WHERE processID=(SELECT forProcessID FROM recommendations r WHERE ID=?))");
				$query->bind_param("i",$recomID);
				if ($query->execute()) {
					echo "full success";
				} else {
					echo "last error";
				}

			} else {
				echo "error2";
			}
		} else {
			echo "Error1";
		}
	} else {
	    echo "Error while recommendation nodes deletion!";
	}
}
?>