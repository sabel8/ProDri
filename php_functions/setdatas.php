<?php 
require_once("../config.php");
global $connection;
// get the parameters from URL
$q = $_REQUEST["q"];
$p = isset($_REQUEST["p"])?$_REQUEST["p"]:null;
if ($q=="insert") {
	$p = explode(",",$p);
	switch ($p[0]) {
		case "processes":
			//checks if there is assigned project or not
			if($p[2]!="-1"){
				$query = $connection->prepare("INSERT INTO processes (ID, processName, projectID) VALUES (NULL, ?, ?)");
				$query->bind_param('ss',$p[1],$p[2]);
			} else {
				$query = $connection->prepare("INSERT INTO processes (ID, processName) VALUES (NULL, ?)");
				$query->bind_param('s',$p[1]);
			}
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
			$query = $connection->prepare("INSERT INTO abstract_nodes (nodeID,name,xCord,yCord,professionID,raci,abstractProcessID,description)
			 VALUES (?,?,?,?,?,?,?,?)");
			$query->bind_param("isiiisis",$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8]);
			break;
		case "recEdges":
			$query = $connection->prepare("INSERT INTO abstract_edges (fromNodeID,toNodeID,abstractProcessID) VALUES (?,?,?)");
			$query->bind_param("iii",$p[1],$p[2],$p[3]);
			break;
		case "absProcNodeDel":
			$query = $connection->prepare("DELETE FROM abstract_nodes WHERE abstractProcessID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "absProcEdgeDel":
			$query = $connection->prepare("DELETE FROM abstract_edges WHERE abstractProcessID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "recDel":
			$query = $connection->prepare("DELETE FROM abstract_processes WHERE ID=?");
			$query->bind_param("i",$p[1]);
			break;
		case "makeRecLive":
			//p[1] = recomID
			$query = $connection->prepare("UPDATE process_groups SET latestVerProcID=?
				WHERE ID=(SELECT processGroupID FROM abstract_processes WHERE ID=?)");
			$query->bind_param("ii",$p[1],$p[1]);
			break;
		case "log":
			//p[1] = typeID
			//p[2] = recomID
			if ($p[1]==16){
				$query=$connection->prepare("INSERT INTO system_message_log (typeID, receiverTypeID, text, processID)
				VALUES (16, 2, (SELECT concat(personName,' recommended a new process, please review it. Title: ',pr.title) 
				FROM persons p,abstract_processes pr WHERE p.ID=pr.submitterPersonID AND pr.ID=?), 
				(SELECT processGroupID FROM abstract_processes pr WHERE pr.ID=?));");
				$query->bind_param("ii",$p[2],$p[2]);
			} else if($p[1]==17) {
				$query=$connection->prepare("INSERT INTO system_message_log (typeID, receiverTypeID, text, processID)
				VALUES (17,1,(SELECT concat('Process modification by ',personName,' was approved by PO. Title: ',pr.title) 
				FROM persons p,abstract_processes pr WHERE p.ID=pr.submitterPersonID AND pr.ID=?), 
				(SELECT processGroupID FROM abstract_processes pr WHERE pr.ID=?))");
				$query->bind_param("ii",$p[2],$p[2]);
			} else if($p[1]==18){
				$query=$connection->prepare("INSERT INTO system_message_log (typeID, receiverTypeID, text, processID)
				VALUES (18,1,(SELECT concat('Process modification by ',personName,' was declined by PO. Title: ',pr.title) 
				FROM persons p,abstract_processes pr WHERE p.ID=pr.submitterPersonID AND pr.ID=?), 
				(SELECT processGroupID FROM abstract_processes pr WHERE pr.ID=?))");
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
	$query = $connection->prepare("DELETE FROM ".$p[0]." WHERE ID=?");

	confirm($query);
	$query->bind_param('s',$p[1]);

	if ($query->execute()) {
	    echo "Record with ID \"{$p[1]}\" was successfully removed!";
	} else {
	    echo "Error while deleting record!";
	}


} else if ($q=="recomStatusChange"){
	$query = $connection->prepare("UPDATE abstract_processes SET status=? WHERE ID=?");

	confirm($query);
	$query->bind_param('ii',$_POST["to"],$_POST["p"]);

	if ($query->execute()) {
	    echo "Recommendation submitted successfully!";
	} else {
	    echo "Error while updating record!";
	}


} else if ($q=="newProcess"){
	$query = $connection->prepare("INSERT INTO abstract_processes (ID,title,submitterPersonID,processGroupID) 
	VALUES (NULL,?,?,?)");

	confirm($query);
	$query->bind_param('sii',$_POST["title"],$_POST["from"],$_POST["p"]);

	if ($query->execute()) {
		//returns the ID of the abstract process
		$query=$connection->prepare("SELECT ID FROM abstract_processes WHERE submitterPersonID=? AND processGroupID=? ORDER BY ID DESC");
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
} else if ($_POST["q"]=="newAbstractProcess") {
	$nodes = json_decode($_POST['nodes'],true);
	$edges = json_decode($_POST['edges'],true);
	/* print_r($_POST);echo "<br><br>";
	print_r($nodes);echo "<br><br>".count($nodes)."<br><br>";
	print_r($edges);  */
	$desc = $_POST['desc'];
	$title = $_POST['title'];
	//return;
	$pgID;$apID;

	//creating the new process group
	$query=$connection->prepare("INSERT INTO process_groups (name,description) VALUES (?,?)");
	$query->bind_param("ss",$title,$desc);
	if($query->execute()) {
		$pgID = $query->insert_id;
	} else {echo "ERROR creating the new process group! ".$query->error;return;}

	//creating the new abstract process
	$query=$connection->prepare("INSERT INTO abstract_processes (title,submitterPersonID,processGroupID,
	status,description) VALUES (?,?,?,?,?)");
	$sub = "LEGACY PROCESS: ".$title;
	$personID=0;/*change this to dynamic*/
	$status = 2; //make live instantly
	$query->bind_param("siiis",$sub,$personID,$pgID,$status,$desc);
	if($query->execute()) {
		$apID = $query->insert_id;
	} else {echo "ERROR creating the new abstract process! ".$query->error;return;}

	//assigning the abstract process' ID to the process group
	$query=$connection->prepare("UPDATE process_groups SET latestVerProcID=? WHERE ID=?");
	$query->bind_param("ii",$apID,$pgID);
	if (!$query->execute()) {
		echo "ERROR connecting the ap with the pg! ".$query->error;return;
	}

	//creating the nodes of the abstract process
	for ($i=0; $i < count($nodes); $i++) {
		$curNode=$nodes[$i];
		$query=$connection->prepare("INSERT INTO abstract_nodes (nodeID,name,xCord,yCord,professionID,
		raci,abstractProcessID,description) VALUES (?,?,?,?,?,?,?,?)");
		$profession=($curNode['knowledgeArea']=="-1"?null:$curNode['knowledgeArea']);
		$query->bind_param("isiiisis",$curNode['ID'],$curNode['txt'],$curNode['x'],$curNode['y'],$profession,
		$curNode['RACI'],$apID,$curNode['desc']);
		if(!$query->execute()) {
			echo "ERROR creating a node to the new process. ".$query->error;return;
		}
	}

	//creating the edges of the abstarct process
	for ($i=0; $i < count($edges); $i++) { 
		$curEdge = $edges[$i];
		$query = $connection->prepare("INSERT INTO abstract_edges (fromNodeID,toNodeID,abstractProcessID) VALUES (?,?,?)");
		$query->bind_param("iii",$curEdge['fromNodeID'],$curEdge['toNodeID'],$apID);
		if(!$query->execute()) {
			echo "ERROR creating an edge to the new process. ".$query->error;return;
		}
	}

	echo "<b>SUCCESSFUL CREATION</b>";
}
?>