<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");

$curProcess=1; //todo --> dynamic

function getProjectManagerHTML(){
	//if post is set, update database then clear post
	//avoiding repetitive form submission
	if ($_POST) {
		print_r($_POST);
		// Execute code (such as database updates) here.
		foreach ($_POST as $key => $value) {
			global $connection;
			if (substr($key,0,9)=="personSel") {
				if($value==-1){
					$query = $connection->prepare("UPDATE nodes SET responsiblePersonID=NULL WHERE ID=?");
					$query->bind_param("i",intval(substr($key,9)));
				} else {
					$query = $connection->prepare("UPDATE nodes SET responsiblePersonID=? WHERE ID=?");
					$query->bind_param("ii",$value,intval(substr($key,9)));
				}
			} else if(substr($key,0,10)=="priorityOf") {
				$query=$connection->prepare("UPDATE nodes SET priority=? WHERE ID=?");
				$query->bind_param("ii",$value,intval(substr($key,10)));
			}
			
			if ($query->execute()) {
			} else {
				echo "Error while updating records!";
			}
		}
		// Redirect to this page.
		header("Location: " . $_SERVER['REQUEST_URI']);
		exit();
	}

	$innerhtml="<h2><b>Vacant tasks</b></h2>";
	$processes = getRowsOfQuery("SELECT processName,p.ID,projects.projectName FROM nodes n,processes p
			LEFT JOIN projects ON projects.ID=p.projectID
			WHERE n.processID=p.ID GROUP BY processName"); 
	
	
	//creating a table if there is any vacant task
	if(count($processes)>=2){
		$innerhtml .= "<form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'>";
		for ($j=0;$j<count($processes)-1;$j++){
			$curProcess=explode("|",$processes[$j]);
			$rows = getRowsOfQuery("SELECT n.nodeID,n.txt,concat(professionName,' (',seniority,')'),n.raci
			,n.responsiblePersonID,n.professionID,n.ID,n.priority
					FROM nodes n
					LEFT JOIN professions prof 
						ON n.professionID=prof.ID
					LEFT JOIN processes p
						ON n.processID=p.ID
					WHERE NOT (n.txt='START' OR n.txt='FINISH') AND n.processID=".$curProcess[1]);
			$innerhtml .= "<hr style='border-color:lightgrey'><h4><b>".$curProcess[0]."</b> (".$curProcess[2].")</h4><br>";
			$innerhtml .= getTableHeader(array("ID","Task name","Profession","RACI","Authorized person","Priority"),"editProcess".$curProcess[1]);
			for ($i=0; $i < count($rows)-1; $i++) {
				$innerhtml.="<tr>";
				$cells = explode("|",$rows[$i]);
				//-1 because prof.ID is for dropdown list
				for ($n=0; $n < count($cells)-4; $n++) {
					if ($n==3) {
						$txt=getRACItext($cells[3]);
						$innerhtml.="<td>".$txt."</td>";
					} else {
						$innerhtml.="<td>".$cells[$n]."</td>";
					}
				}

				$innerhtml.="<td>";
				$professionID=$cells[5];
				$ID = $cells[6];
				
				//getting and setting up the person(s selection) for the tasks
				$avaliablePersonRows=getRowsOfQuery("SELECT pe.ID,personName FROM persons pe,professions pr 
				WHERE pe.professionID=pr.ID AND pr.ID=".$professionID);
				$innerhtml.="<select name='personSel".$cells[6]."' style='width:100%'><option value=\"-1\"> </option>";
				for ($n=0; $n < count($avaliablePersonRows)-1; $n++) { 
					$values=explode("|",$avaliablePersonRows[$n]);
					$innerhtml.='<option value='.$values[0];
					if ($values[0]==$cells[4]){
						$innerhtml.=" selected";
					}
					$innerhtml.='>'.$values[1].'</option>';
				}
				$innerhtml.="</select></td>";

				$priorityValuesRows=getRowsOfQuery("SELECT n.priority FROM nodes n WHERE n.ID=".$cells[6]);
				$innerhtml.="<td><input style='width:50px' type='number' name='priorityOf".$cells[6]."'min='0'";
				if ($cells[7]!=""){
					$innerhtml.="value='".$cells[7]."'";
				}
				$innerhtml.="></tr>";
			}
			$innerhtml .= "</tbody></table>";
			$innerhtml .= "<input type='submit' style='float:right' class='btn btn-success' value='Confirm changes'>";
			$innerhtml .= "</div>";
		}
		$innerhtml .= "</form>";
	} else {
		$innerhtml.= '<div class="alert alert-success">There isn\'t any vacant task!</div>';
	}
	
	return $innerhtml;
}

function getProcessOwnerHTML(){
	//setting up recommendation management
	$innerhtml="<h3>Submitted recommendations</h3><br>";

	$recProcesses=getRowsOfQuery("SELECT projectName,processName,proc.ID FROM recommendations recs
		LEFT JOIN processes proc ON proc.ID=recs.forProcessID
		LEFT JOIN projects proj ON proj.ID=proc.projectID
		WHERE NOT recs.status=0
		GROUP BY processName");
	if(count($recProcesses)>=2){
		for($i=0;$i<count($recProcesses)-1;$i++) {
			$curProcess=explode("|",$recProcesses[$i]);
			$innerhtml.="<h4><b>".$curProcess[1]."</b> (".$curProcess[0].")</h4>";
			$recOfProc=getRowsOfQuery("SELECT r.ID,p.personName,r.status,r.isLive FROM recommendations r, persons p
				WHERE r.submitterPersonID=p.ID AND r.forProcessID=".$curProcess[2]." AND NOT r.status=0");
			$innerhtml .= getTableHeader(array("ID","Submitter person","Status","Judgement"),"recsTable".$curProcess[2]);
			for($n=0;$n<count($recOfProc)-1;$n++) {
				$curRec=explode("|",$recOfProc[$n]);
				$innerhtml.=getTableRecordRow($curRec,(string)("recsTable".$curProcess[2]));
				print_r((string)("recsTable".$curProcess[2]));
				$innerhtml.="<td class='text-center'>";
				//setting up the judgement buttons
				//set up the buttons on submitted recommendations
				if ($curRec[2]==1) {
					$innerhtml.="
					<button class='btn btn-success' type='button'
					onclick='event.stopPropagation();changeRecommendationStatus({$curRec[0]},2)'>Accept</button>
					<button class='btn btn-danger' type='button'
					onclick='event.stopPropagation();changeRecommendationStatus({$curRec[0]},3)'>Refuse</button>";
				//set up the withdraw button
				} else if ($curRec[2]==2 && $curRec[3]==1) {
					$innerhtml.="
					<button class='btn btn-primary' type='button' onclick='event.stopPropagation();withdraw({$curRec[0]});
						changeRecommendationStatus({$curRec[0]},1)'>Withdraw</button>";
				} else {
					$innerhtml.= "<i>This recommendation is ".getStatusName($curRec[2])."</i>";
				}
				$innerhtml.="</td></tr>";
			}
			$innerhtml .= "</tbody></table></div>";
		}
	}

	//TASK MANAGING TABLE
	//if post is set, update database then clear post
	//avoiding repetitive form submission
	if ($_POST) {
		print_r($_POST);
		// Execute code (such as database updates) here.
		foreach ($_POST as $key => $value) {
			global $connection;
			if (substr($key,0,4)=="raci") {
				if($value==-1){
					$query = $connection->prepare("UPDATE nodes SET raci=NULL WHERE ID=?");
					$query->bind_param("i",intval(substr($key,4)));
				} else {
					$query = $connection->prepare("UPDATE nodes SET raci=? WHERE ID=?");
					$query->bind_param("si",$value,intval(substr($key,4)));
				}
			} else if(substr($key,0,12)=="professionOf") {
				if ($value==-1) {
					$query=$connection->prepare("UPDATE nodes SET professionID=NULL,responsiblePersonID=NULL WHERE ID=?");
					$query->bind_param("i",intval(substr($key,12)));
				} else {
					$query=$connection->prepare("UPDATE nodes SET professionID=? WHERE ID=?");
					$query->bind_param("ii",$value,intval(substr($key,12)));
				}
			}
			
			if ($query->execute()) {
				echo mysqli_error($connection);
			} else {
				echo "Error while updating records!";
			}
		}
		// Redirect to this page.
		header("Location: " . $_SERVER['REQUEST_URI']);
		exit();
	}


	$innerhtml.="<hr style='border-color:lightgrey'><h3>Manage current tasks</h3>";
	$tasksRow = getRowsOfQuery("SELECT nodeID,txt,professionID,raci,processName,n.ID FROM nodes n, processes p WHERE n.processID=p.ID");
	if(count($tasksRow)>=2){
		$innerhtml .= "<form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'>";
		$innerhtml.=getTableHeader(array("ID","Task name","Profession","RACI","Process name"),"tasksTable");
		$professionRow = getRowsOFQuery("SELECT ID,concat(professionName,' (',seniority,')') from professions");
		for($i=0;$i<count($tasksRow)-1;$i++) {
			$cells=explode("|",$tasksRow[$i]);
			$innerhtml.="<tr>";
			//-1 because ID column is not needed
			for($n=0;$n<count($cells)-1;$n++){
				$innerhtml.="<td>";
				switch($n){
					//getting the profession and the seniority
					case 2:
						
						//$innerhtml .= explode(",",$professionRow[0])[0];
						if ($cells[1]!="START" and $cells[1]!="FINISH") {
							$innerhtml.="<select name='professionOf".$cells[5]."'>";
							$innerhtml.="<option value='-1'></option>";
							//getting all professions and putting into option elements
							for($j=0;$j<count($professionRow)-1;$j++){
								$prof=explode("|",$professionRow[$j]);
								$selected=$cells[2]==$prof[0]?" selected":"";
								$innerhtml.="<option value='".$prof[0]."'".$selected.">".$prof[1]."</option>";
							}
							$innerhtml.="</select>";
						}
						break;
					case 3:
						if ($cells[1]!="START" and $cells[1]!="FINISH") {
							$innerhtml.="<select name='raci".$cells[5]."'>";
							$innerhtml.="<option value='-1'></option>";
							$innerhtml.=getRACIoption($cells[3],"r");
							$innerhtml.=getRACIoption($cells[3],"a");
							$innerhtml.=getRACIoption($cells[3],"c");
							$innerhtml.=getRACIoption($cells[3],"i");
							$innerhtml.="</select>";
						}
						break;
					default:
						$innerhtml.=$cells[$n];
				}
				$innerhtml.="</td>";
			}
			$innerhtml.="</tr>";
		}
		$innerhtml .= "</tbody></table>";
		$innerhtml .= "<input type='submit' style='float:right' class='btn btn-success' value='Confirm'></div>";
		$innerhtml .= "</form>";
	} else {
		$innerhtml.= '<div class="alert alert-warning">There isn\'t any recommendations for you to review!</div>';
	}


	return $innerhtml;
}

function getUserHTML($username) {
	global $connection;
	$innerhtml="<div class='row'><div class='col-sm-4'><h3>Processes: (for $username)</h3><hr>";
	$processesInvolvedRows=getRowsOfQuery("SELECT p.ID,processName FROM processes p, nodes n
		WHERE n.responsiblePersonID=(SELECT pers.ID FROM persons pers WHERE pers.personName='$username')
		GROUP BY p.ID");
	$innerhtml.="<select style='width:100%' name='involvedProcesses' size='5'>";
	for ($i=0;$i<count($processesInvolvedRows)-1;$i++) {
		$cells=explode("|",$processesInvolvedRows[$i]);

		//setting up the stringified node array
		$nodesOfProc=getRowsOfQuery("SELECT nodeID,txt,xCord,yCord,raci,processID,description FROM nodes WHERE processID=".$cells[0]);
		$nodeString="";
		for($n=0;$n<count($nodesOfProc)-1;$n++){
			$curNode = explode("|",$nodesOfProc[$n]);
			$nodeString.="[";
			for($e=0;$e<count($curNode);$e++){
				$nodeString.='\''.$curNode[$e].'\',';
			}
			//cut down last colon
			$nodeString = substr($nodeString,0,-1)."],";
		}
		//cut down last colon
		$nodeString= substr($nodeString,0,-1);

		//setting up the stringified edge array
		$edgesOfProc=getRowsOfQuery("SELECT ID,fromNodeID,toNodeID FROM edges WHERE processID=".$cells[0]);
		$edgeString="";
		for($n=0;$n<count($edgesOfProc)-1;$n++){
			$curEdge = explode("|",$edgesOfProc[$n]);
			$edgeString.="[";
			for($e=0;$e<count($curEdge);$e++){
				$edgeString.='\''.$curEdge[$e].'\',';
			}
			//cut down last colon
			$edgeString = substr($edgeString,0,-1)."],";
		}
		//cut down last colon
		$edgeString= substr($edgeString,0,-1);

		$innerhtml.="<option onclick=\"createRecommendation2([$nodeString],[$edgeString],".$cells[0].",true,false)\" value='".$cells[0]."'>".$cells[1]."</option>";
	}
	$innerhtml.= "</select>";
	$innerhtml.="<button class='btn btn-default' onclick='d3.select(\"#recommendationSelectModalTrigger\").node().click()'><span class='glyphicon glyphicon-open'></span> Load recommendation</button>";
	$innerhtml.="</div><div class='col-sm-8'><h3 style='color:red'><b>Info:</b></h3><hr>";
	$recLogRows=getRowsOfQuery("SELECT text,timestamp FROM system_message_log log 
		WHERE typeID=16 or typeID=17 or typeID=18 ORDER BY timestamp DESC LIMIT 5");
	if(count($recLogRows)>1){
		$innerhtml.="<ul class='list-group'>";
		for($i=0;$i<count($recLogRows)-1;$i++) {
			$curLog=explode("|",$recLogRows[$i]);
			$innerhtml.="<li class='list-group-item'>".$curLog[0]."<span class='badge'>".$curLog[1]."</span></li>";
		}
		$innerhtml.="</ul>";
	}


	$innerhtml.="Kiírja azokat a log-okat aminek az ID-ja 16. 17 vagy 18 (és a userre vonatkoznak...)<br><br>";
	$innerhtml.="<b>FEJLESZTŐI KOMMENT : lehetne inkább:</b> SELECT ID, text FROM system_message_log AS log 
		WHERE receiverID=(SELECT ID from persons where personName=$username) AND (typeID=17 OR typeID=18)";


	$innerhtml.="</div></div><div id='manageEditorBody'></div>";

	//setting up the modal for recommendation selection
	$innerhtml.='<a id="recommendationSelectModalTrigger" data-toggle="modal" href="#recommendationSelectModal" style="display:none"></a>';
	$options="";
	$recs=getRowsOfQuery("SELECT r.ID,processName,r.status FROM recommendations r,processes p 
		WHERE r.forProcessID=p.ID");
	for ($i=0;$i<count($recs)-1;$i++) {
		$curRec=explode("|",$recs[$i]);
		switch($curRec[2]){
			case 0:$status="Not yet submitted";break;
			case 1:$status="Submitted, but not reviewed";break;
			case 2:$status="Accepted";break;
			case 3:$status="Refused";break;
			default:$status="error";
		}
		$nodeString=getNodesOfRec($curRec[0]);
		$edgeString=getEdgesOfRec($curRec[0]);
		$recomID=$curRec[0];
		$options.="<option data-dismiss='modal' onclick=\"viewRec2([$nodeString],[$edgeString],$recomID,".$curRec[2].",false,true)\" value='".$curRec[0]."'>".$curRec[1]." (".$status.")</option>";
	}
	$numOfRows=count($recs);
	$actionParam=htmlspecialchars($_SERVER["PHP_SELF"]);
	$innerhtml.=<<<DEL
<div id="recommendationSelectModal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 id="objectName" class="modal-title">Your recommendations</h4>
			</div>
			<div class="modal-body">
					<select style="width:100%" name="recommendationSelect" size="$numOfRows">
						$options
					</select>				
			</div>
		</div>

	</div>
</div>
DEL;
	echo $innerhtml;
	
}

function getNodesOfRec($recID){
	//setting up the stringified node array
	$nodesOfProc=getRowsOfQuery("SELECT nodeID,name,xCord,yCord,raci,r.forProcessID,description 
		FROM recommended_nodes n,recommendations r WHERE n.recommendationID=r.ID AND n.recommendationID=".$recID);
	$nodeString="";
	for($n=0;$n<count($nodesOfProc)-1;$n++){
		$curNode = explode("|",$nodesOfProc[$n]);
		$nodeString.="[";
		for($e=0;$e<count($curNode);$e++){
			$nodeString.='\''.$curNode[$e].'\',';
		}
		//cut down last colon
		$nodeString = substr($nodeString,0,-1)."],";
	}
	//cut down last colon
	return substr($nodeString,0,-1);
}

function getEdgesOfRec($recID){
	//setting up the stringified edge array
	$edgesOfProc=getRowsOfQuery("SELECT ID,fromNodeID,toNodeID FROM recommended_edges e
		WHERE recommendationID=".$recID);
	$edgeString="";
	for($n=0;$n<count($edgesOfProc)-1;$n++){
		$curEdge = explode("|",$edgesOfProc[$n]);
		$edgeString.="[";
		for($e=0;$e<count($curEdge);$e++){
			$edgeString.='\''.$curEdge[$e].'\',';
		}
		//cut down last colon
		$edgeString = substr($edgeString,0,-1)."],";
	}
	//cut down last colon
	return substr($edgeString,0,-1);

}

function getStatusName($status) {
	$res="";
	switch($status) {
		case 0:
			$res.="Not yet submitted.";
			break;
		case 1:
			$res.="Under review...";
			break;
		case 2:
			$res.="Accepted! Thank you.";
			break;
		case 3:
			$res.="Refused. Sorry.";
			break;
	}
	return $res;
}


//returns the color class of the row
//according to its status
function getColorClass($status) {
	switch ($status) {
		case '0':
			$colorClass="warning";
			break;
		case '1':
			$colorClass="info";
			break;
		case '2':
			$colorClass="success";
			break;
		case '3':
			$colorClass="danger";
			break;
	}
	return $colorClass;
}

//returns the html of a recommendation row
//on click the preview shows up
//param $cells = array of the values in table cells
function getTableRecordRow($cells,$tableID) {
	$innerhtml="";
	global $connection;
	//query for getting the nodes of the recommendation
	$query = $connection->prepare("
		SELECT r.nodeID,r.name,r.xCord,r.yCord,r.status,r.professionID,\"\",r.duration,r.raci,re.forProcessID,re.forProcessID 
		FROM recommended_nodes r, recommendations re WHERE r.recommendationID=?");
	$query->bind_param('i',$cells[0]);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$nodes="";
	while ($row = $result->fetch_assoc()){
		$nodes .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$nodes=rtrim($nodes,",");

	//query for getting the edges of the recommendation
	$query = $connection->prepare("
		SELECT r.ID,r.fromNodeID,r.toNodeID
		FROM recommended_edges r WHERE r.recommendationID=?");
	$query->bind_param('i',$cells[0]);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$edges="";
	while ($row = $result->fetch_assoc()){
		$edges .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$edges=rtrim($edges,",");

	$colorClass=getColorClass($cells[2]);

	$innerhtml.="<tr class='{$colorClass}' style='cursor:pointer' 
		onclick=\"viewRecommendation({$cells[0]},[{$nodes}],[{$edges}],'$tableID')\">";
		//viewRec2([$nodes],[$edges],{$cells[0]},{$cells[2]},false,true,'".$tableID."')
	for ($n=0;$n < count($cells)-1;$n++){
		$innerhtml.='<td class="text-center">';
		switch($n){
			case 2:
				$innerhtml.=getStatusName($cells[2]);
				break;
			default:
				$innerhtml.=$cells[$n];
		}
		$innerhtml.="</td>";
	}
	return $innerhtml;
}

//return an option element with the raci character in value
//and the full raci word in title
function getRACIoption($cell,$raci){
	$selected = strtolower($cell)==$raci?" selected":"";
	return "<option value='".$raci."'".$selected.">".getRACItext($raci)."</option>";
}

?>

<script type="text/javascript" src="scripts/manage.js"></script>

<div class="container">
	<div id="manageBody" class="well">
		<?php
		$username = "Slay Lewis"; //to be changed to dinamic
		$innerhtml="";
		switch ($_SESSION["auth"]) {
			//PROJECT MANAGER
			case "pm":
				$innerhtml=getProjectManagerHTML();
				break;

			//PROCESS OWNER
			case 'po':
				$innerhtml=getProcessOwnerHTML();
				break;
			//USER
			case "u":
				$innerhtml=getUserHTML($username);
				break;

			//ERROR : NONE OF THE ABOVE
			default:
				$innerhtml = '<div class="alert alert-danger"><strong>ERROR!</strong> Choose an authority!</div>';
				break;
		}
		echo $innerhtml;
		?>
	</div>

	<a id="objectInfoModalTrigger" style="display: none" data-toggle="modal" href="#objectInfoModal"></a>

	<!-- Object Info Modal -->
	<div id="objectInfoModal" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 id="objectName" class="modal-title">Object name</h4>
				</div>
				<div class="modal-body">
					<p id="objectInfo">Object info....</p>
					<select id="statusSelect" onchange="/*selectStatus()*/">
						<option value="notStartedOption">Not started</option>
						<option value="inProgressOption">In progress</option>
						<option value="doneOption">Done</option>
					</select>
				</div>
				<div class="modal-footer">
					<button style="float:left;" type="button" class="btn btn-danger" data-dismiss="modal"  onclick="deleteSelected()">Delete</button>
					<button style="float:right;" type="button" class="btn btn-default" data-dismiss="modal" onclick='d3.select("#statusSelect").style("display","none");selectedNode=null;redraw();'>Close</button>
				</div>
			</div>

		</div>
	</div>

	<a id="newNodeModalTrigger" style="display: none" data-toggle="modal" href="#newNodeModal" onclick="/*setupModal()*/"></a>

	<!-- Node Creating Query Modal -->
	<div id="newNodeModal" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 id="nodeName" class="modal-title">New node incoming! Define it's properties, please...</h4>
				</div>
				<div class="modal-body">
					<form>
						Task name:<br>
						<input type="text" id="nodeTitle" value="Example task 1">
						<br><br>

						RACI:<br>
						<div id="nodeRaci">
							<input name="nodeRaci" id="raciR" type="radio" value="R" checked>
							<label for="raciR">Responsible</label><br>
							<input name="nodeRaci" id="raciA" type="radio" value="A">
							<label for="raciA">Accountable</label><br>
							<input name="nodeRaci" id="raciC" type="radio" value="C">
							<label for="raciC">Consultant</label><br>
							<input name="nodeRaci" id="raciI" type="radio" value="I">
							<label for="raciI">Informed</label>
						</div>
						<br><br>
						Description: (beta)<br>
						<textarea name="nodeDescription" rows="5" cols="50">Example...</textarea>
					</form> 
				</div>
				<div class="modal-footer">
					<button style="float:left;" type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button id="createNodeButton" style="float:right;" type="button" class="btn btn-primary"
					 onclick="addNewRecNode(<?php echo $curProcess?>)" data-dismiss="modal">Create</button>
				</div>
			</div>

		</div>
	</div>
<?php include(TEMPLATE.DS."footer.php")?>