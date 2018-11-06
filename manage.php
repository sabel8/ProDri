<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");

$curProcess=1; //todo --> dynamic

function getProjectManagerHTML(){
	//if post is set, update database then clear post
	//avoiding repetitive form submission
	if ($_POST) {
		// Execute code (such as database updates) here.
		foreach ($_POST as $key => $value) {
			global $connection;
			if($value==-1){
				$query = $connection->prepare("UPDATE nodes SET responsiblePersonID=NULL WHERE ID=?");
				$query->bind_param("i",intval(substr($key,9)));
			} else {
				$query = $connection->prepare("UPDATE nodes SET responsiblePersonID=? WHERE ID=?");
				$query->bind_param("ii",$value,intval(substr($key,9)));
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
			$curProcess=explode(",",$processes[$j]);
			$rows = getRowsOfQuery("SELECT n.nodeID,n.txt,concat(professionName,' (',seniority,')'),n.raci,n.responsiblePersonID,n.professionID,n.ID
					FROM nodes n
					LEFT JOIN professions prof 
						ON n.professionID=prof.ID
					LEFT JOIN processes p
						ON n.processID=p.ID
					WHERE NOT (n.txt='START' OR n.txt='FINISH') AND n.processID=".$curProcess[1]);
			$innerhtml .= "<hr style='border-color:lightgrey'><h4><b>".$curProcess[0]."</b> (".$curProcess[2].")</h4><br>";
			$innerhtml .= getTableHeader(array("ID","Task name","Profession","RACI","Authorized person"));
			for ($i=0; $i < count($rows)-1; $i++) {
				$innerhtml.="<tr>";
				$cells = explode(",",$rows[$i]);
				//-1 because prof.ID is for dropdown list
				for ($n=0; $n < count($cells)-3; $n++) {
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
				
				$avaliablePersonRows=getRowsOfQuery("SELECT pe.ID,personName FROM persons pe,professions pr 
				WHERE pe.professionID=pr.ID AND pr.ID=".$professionID);
				$innerhtml.="<select name='personSel".$cells[6]."' style='width:100%'><option value=\"-1\"> </option>";
				for ($n=0; $n < count($avaliablePersonRows)-1; $n++) { 
					$values=explode(",",$avaliablePersonRows[$n]);
					$innerhtml.='<option value='.$values[0];
					if ($values[0]==$cells[4]){
						$innerhtml.=" selected";
					}
					$innerhtml.='>'.$values[1].'</option>';
				}
				$innerhtml.="</select>";

				$innerhtml.="</td></tr>";
			}
			$innerhtml .= "</tbody></table>";
			$innerhtml .= "<div style='float:right'><input type='submit' class='btn btn-success' value='Assign persons'></div>";
			$innerhtml .= "</div>";
		}
		$innerhtml .= "</form>";
	} else {
		$innerhtml.= '<div class="alert alert-success">There isn\'t any vacant task!</div>';
	}
	
	return $innerhtml;
}

function getProcessOwnerHTML(){
	$innerhtml="<h3>Submitted recommendations</h3>";

	$rows = getRowsOfQuery("SELECT r.ID,p.personName,r.status,pr.processName,r.isLive
		FROM recommendations r, persons p, processes pr
		WHERE r.submitterPersonID=p.ID AND r.forProcessID=pr.ID AND NOT r.status=0");

	//creating a table if the user has any recommendation
	if(count($rows)>=2){
		$innerhtml .= getTableHeader(array("ID","Submitter person","Status","Process name","Judgement"));
		for ($i=0; $i < count($rows)-1; $i++) {

			$cells = explode(",",$rows[$i]);

			$innerhtml.=getTableRecordRow($cells);
			$innerhtml.="<td class='text-center'>";
			if ($cells[2]==1) {
				$innerhtml.="
				<button class='btn btn-success' type='button'
				 onclick='event.stopPropagation();changeRecommendationStatus({$cells[0]},2)'>Accept</button>
				<button class='btn btn-danger' type='button'
				 onclick='event.stopPropagation();changeRecommendationStatus({$cells[0]},3)'>Refuse</button>";
			} else if ($cells[2]==2 && $cells[4]==1) {
				$innerhtml.="
				<button class='btn btn-primary' type='button' onclick='event.stopPropagation();withdraw({$cells[0]});changeRecommendationStatus({$cells[0]},1)'>Withdraw</button>";
			} else {
				$innerhtml.= "<i>This recommendation is ".getStatusName($cells[2])."</i>";
			}
			$innerhtml.="</td></tr>";
		}
		$innerhtml .= "</tbody></table></div>";
	} else {
		$innerhtml.= '<div class="alert alert-warning">There isn\'t any recommendations for you to review!</div>';
	}
	return $innerhtml;
}

function getUserHTML($username){
	global $connection;

	$innerhtml = '<h3>My recommendations (user: '.$username.')</h3>
		<button type="button" class="btn btn-default" onclick="createRecommendation()">
		<span class="glyphicon glyphicon-plus"></span> Add new recommendation
		</button><br><br>';

	//getting the recommendations of the user
	$query = $connection->prepare("SELECT r.ID,pr.processName,r.status,r.isLive
		FROM recommendations r, processes pr 
		WHERE r.submitterPersonID=(SELECT ID FROM persons WHERE personName=?) AND pr.ID=r.forProcessID");
	$query->bind_param('s',$username);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$res="";
	while ($row = $result->fetch_assoc()){
		$res = $res . implode(",",$row) .";";
	}
	$rows = explode(";", $res);
	//creating a table if the user has any recommendation
	if(count($rows)>=2){
		$innerhtml.=getTableHeader(array("ID","For process","Status","Submit"));

		//getting the nodes and edges for every recommendation
		for ($i=0; $i < count($rows)-1; $i++) {

			$cells = explode(",",$rows[$i]);
			//query for getting the nodes of the recommendation

			$innerhtml.=getTableRecordRow($cells);

			//defining the buttons
			if ($cells[2]==0) {
				$innerhtml.="<td class='text-center'><button class='btn btn-primary' type='button' onclick='changeRecommendationStatus({$cells[0]},1)'>Submit</button>";
			} else {
				$innerhtml.="<td class='text-center'><i>You have already submitted this recommendation.</i>";
			}
			$innerhtml.=" <button class='btn btn-danger' type='button' onclick='removeRecommendation({$cells[0]})'>Remove</button></td></tr>";
		}

		$innerhtml .= "</tbody></table></div>";

	} else {
		$innerhtml.= '<div class="alert alert-danger">You have not created any recommendations yet!</div>';
	}
	return $innerhtml;
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
//param $cells = array of the values intable cells
function getTableRecordRow($cells) {
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

	$innerhtml.="<tr class='{$colorClass}' style='cursor:pointer' onclick=\"viewRecommendation({$cells[0]},[{$nodes}],[{$edges}])\">";
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

		<div id="manageEditorBody"></div>
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