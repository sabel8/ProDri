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
	$query = $connection->prepare("INSERT INTO abstract_processes (title,submitterPersonID,processGroupID) 
	VALUES (?,?,?)");

	confirm($query);
	$query->bind_param('sii',$_POST["title"],$_SESSION["userID"],$_POST["p"]);

	if ($query->execute()) {
		//returns the ID of the abstract process
		echo $connection->insert_id;
		
	} else {
	    echo "Error while updating record!";
	}



} else if ($q=="withdraw") {
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
} else if ($q=="newAbstractProcess") {
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

	echo "SUCCESSFUL CREATION";
} else if ($q=="updateRecElement") {
	$nodes = json_decode($_POST['nodes'],true);
	$edges = json_decode($_POST['edges'],true);
	$apID = $_POST['recomID'];

	$deleteEdgesSQL="DELETE FROM abstract_edges WHERE abstractProcessID=$apID";
	if (!mysqli_query($connection, $deleteEdgesSQL)) {
		echo "Error: " . $deleteEdgesSQL . "<br>" . mysqli_error($connection);
	}
	$deleteNodesSQL="DELETE FROM abstract_nodes WHERE abstractProcessID=$apID";
	if (!mysqli_query($connection, $deleteNodesSQL)) {
		echo "Error: " . $deleteNodesSQL . "<br>" . mysqli_error($connection);
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

	echo "SUCCESSFUL UPDATE";
} else if ($q=="addPerson") {
	//check input
	if ($_POST['personName']=="" or $_POST['profession']=="" or $_POST['seniority']=="" or $_POST['authority']=="") {
		echo '<div class="alert alert-warning alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		You shall fill <strong>all fields</strong>.
		</div>';
		return;
	}
	//get ID of profession
	$query = $connection->prepare("SELECT ID FROM professions WHERE
		professionName=? AND seniority=?");
	$query->bind_param("ss",$_POST['profession'],$_POST['seniority']);
	$query->execute();
	$query->bind_result($profID);
	$query->fetch();
	$query->close();
	//if not exists, insert and get ID
	if ( !is_numeric($profID)) {
		$query = $connection->prepare("INSERT INTO professions (professionName,seniority) VALUES (?,?)");
		$query->bind_param("ss",$_POST['profession'],$_POST['seniority']);
		$query->execute();
		$profID = $query->insert_id;
		$query->close();
	}
	$query = $connection->prepare("SELECT ID FROM persons WHERE personName=? AND professionID=?");
	$query->bind_param("si",$_POST['personName'],$profID);
	$query->execute();
	$query->bind_result($personID);
	$query->fetch();
	$query->close();
	//if this person already exists
	if ($personID != "") {
		echo '<div class="alert alert-warning alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>Warning!</strong> This person already exists.
		</div>';
		return;
	//else insert
	} else {
		$username = trim(str_replace(" ","",strtolower($_POST['personName'])));
		$query = $connection->prepare("INSERT INTO persons (personName,username,password,authority,professionID)
		VALUES (?,?,?,?,?)");
		//"prodri" is the default password
		$passwd = md5("prodri");
		$query->bind_param("sssii",$_POST['personName'],$username,$passwd,$_POST['authority'],$profID);
		$query->execute();
		$query->close();
		echo mysqli_error($connection).'<div class="alert alert-success alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>Success!</strong> The person has been added.
		</div>';
		return;
	}

} else if ($q=="removePerson") {
	//check input
	if ($_POST['selectedID']=="" or $_POST['personName']=="" or $_POST['profession']==""
	or $_POST['seniority']=="") {
		echo '<div class="alert alert-warning alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		You shall fill <strong>all fields</strong>.
		</div>';
		return;
	}
	$query = $connection->prepare("DELETE FROM persons WHERE ID=? AND personName=? AND
		professionID=(SELECT ID FROM professions WHERE professionName=? AND seniority=?)");
		echo mysqli_error($connection);
	$query->bind_param("isss",$_POST['selectedID'],$_POST['personName'],
		$_POST['profession'],$_POST['seniority']);
	$query->execute();
	if ($connection->affected_rows==1) {
		echo '<div class="alert alert-info alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>'.$_POST['personName'].'</strong> has been successfully deleted.
		</div>';
	} else {
		echo '<div class="alert alert-danger alert-dismissible fade in">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		Could not delete <strong>'.$_POST['personName'].'</strong>.
		</div>';
	}
}
?>