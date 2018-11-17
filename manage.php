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
						$txt=getRACItext($cells[$n]);
						$innerhtml.="<td>".$txt."</td>";
					} else {
						$innerhtml.="<td>".$cells[$n]."</td>";
					}
				}

				
				$professionID=$cells[5];
				$ID = $cells[6];
				$innerhtml.="<td>";
				if (is_numeric($professionID)){
					//getting and setting up the person(s selection) for the tasks
					$avaliablePersonRows=getRowsOfQuery("SELECT pe.ID,personName FROM persons pe,professions pr 
					WHERE pe.professionID=pr.ID AND pr.ID=".$professionID);
					//checks if there is any avaliable persons for the task
					if (count($avaliablePersonRows)==1) {
						$innerhtml.="<i>There is no person with this profession!</i>";
					} else {
						$innerhtml.="<select name='personSel".$cells[6]."' style='width:100%'><option value='-1'> </option>";
						for ($n=0; $n < count($avaliablePersonRows)-1; $n++) { 
							$values=explode("|",$avaliablePersonRows[$n]);
							$innerhtml.='<option value='.$values[0];
							if ($values[0]==$cells[4]){
								$innerhtml.=" selected";
							}
							$innerhtml.='>'.$values[1].'</option>';
						}
						$innerhtml.="</select>";
					}
				} else {
					$innerhtml.="<i>There is no profession assigned to this task!</i>";
				}
				$innerhtml.="</td>";
				
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
			$recOfProc=getRowsOfQuery("SELECT r.ID,r.title,p.personName,r.status,r.isLive FROM recommendations r, persons p
				WHERE r.submitterPersonID=p.ID AND r.forProcessID=".$curProcess[2]." AND NOT r.status=0");
			$innerhtml .= getTableHeader(array("ID","Title","Submitter person","Status","Judgement"),"recsTable".$curProcess[2]);
			for($n=0;$n<count($recOfProc)-1;$n++) {
				$curRec=explode("|",$recOfProc[$n]);
				$innerhtml.=getTableRecordRow($curRec,(string)("recsTable".$curProcess[2]));
				$innerhtml.="<td class='text-center'>";
				//setting up the judgement buttons
				//set up the buttons on submitted recommendations
				if ($curRec[3]==1) {
					$innerhtml.="
					<button class='btn btn-success' type='button'
					onclick='event.stopPropagation();changeRecommendationStatus({$curRec[0]},2)'>Accept</button>
					<button class='btn btn-danger' type='button'
					onclick='event.stopPropagation();changeRecommendationStatus({$curRec[0]},3)'>Refuse</button>";
				//set up the withdraw button
				} else if ($curRec[3]==2 && $curRec[4]==1) {
					$innerhtml.="
					<button class='btn btn-primary' type='button' onclick='event.stopPropagation();withdraw({$curRec[0]});
						changeRecommendationStatus({$curRec[0]},1)'>Withdraw</button>";
				} else {
					$innerhtml.= "<i>This recommendation is ".getStatusName($curRec[3])."</i>";
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
		//print_r($_POST);
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
				//$innerhtml=getUserHTML($username);
				$innerhtml="<div class='alert alert-success'><strong>Congratulations!</strong>
				 You found this easter egg, now please report this to the developer, he'll know whats up! :)</div>";
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