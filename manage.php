<?php
require_once("config.php");
if (count($_POST)>0) {
	switch ($_SESSION["auth"]) {
		//PROJECT MANAGER
		case "pm":
			//if post is set, update database then clear post
			//avoiding repetitive form submission
			//print_r($_POST);
			global $connection;
			$estimationFound = false;
			$deadlineFound = false;
			$startDateFound = false;
			//check and accepts estimation approval/denials
			foreach ($_POST as $key => $value) {
				if(substr($key,0,10)=="estimation") {
					$estimationFound=true;
					$decision = $value=="Accept"?true:false;
					$query=$connection->prepare("UPDATE nodes SET durationStatus=? WHERE ID=?");
					$query->bind_param("ii",$decision,intval(substr($key,10)));
					if ($query->execute()) {
					} else {
						die("Error while updating records!");
					}
					break;
				}
			}
			//check and accepts estimation deadline
			foreach ($_POST as $key => $value) {
				if(substr($key,0,18)=="estimationDeadline") {
					if($value=="" or !isset($_POST["estDeadLineSave".intval(substr($key,18))])){
						continue;
					}
					$deadlineFound=true;
					$query = $connection->prepare("UPDATE processes SET estimationDeadline=? WHERE ID=?");
					$query->bind_param("si",$value,intval(substr($key,18)));
					if ($query->execute()) {
					} else {
						die("Error while updating estimationDeadline!");
					}
					break;
				}
			}
			foreach ($_POST as $key => $value) {
				if(substr($key,0,9)=="startDate") {
					if($value=="" or !isset($_POST["startDateSave".intval(substr($key,9))])){
						continue;
					}
					$startDateFound=true;
					$query = $connection->prepare("UPDATE processes SET startTime=? WHERE ID=?");
					$query->bind_param("si",$value,intval(substr($key,9)));
					if ($query->execute()) {
					} else {
						die("Error while updating estimationDeadline!");
					}
					break;
				}
			}
			// Execute code (such as database updates) here.
			if ($estimationFound==false && $deadlineFound==false && $startDateFound==false) {
				foreach ($_POST as $key => $value) {
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
						die("Error while updating records!");
					}
				}
			}
			break;
		//PROCESS OWNER
		case 'po':
			//if post is set, update database then clear post
			//avoiding repetitive form submission
			// Execute code (such as database updates) here.
			foreach ($_POST as $key => $value) {
				global $connection;
				if (substr($key,0,4)=="raci") {
					if($value==-1){
						$query = $connection->prepare("UPDATE abstract_nodes SET raci=NULL WHERE ID=?");
						$query->bind_param("i",intval(substr($key,4)));
					} else {
						$query = $connection->prepare("UPDATE abstract_nodes SET raci=? WHERE ID=?");
						$query->bind_param("si",$value,intval(substr($key,4)));
					}
				} else if(substr($key,0,12)=="professionOf") {
					if ($value==-1) {
						$query=$connection->prepare("UPDATE abstract_nodes SET professionID=NULL WHERE ID=?");
						$query->bind_param("i",intval(substr($key,12)));
					} else {
						$query=$connection->prepare("UPDATE abstract_nodes SET professionID=? WHERE ID=?");
						$query->bind_param("ii",$value,intval(substr($key,12)));
					}
				}
				
				if ($query->execute()) {
					echo mysqli_error($connection);
				} else {
					echo "Error while updating records! ".mysqli_error($connection)."<br>";
				}
			}
			break;
	}
	// Redirect to this page with wiped _POST.
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}

include(TEMPLATE.DS."header.php");

function getProjectManagerHTML(){
	$innerhtml="<h2><b>All project and their processes</b></h2>";
	$procOfProjects=getRowsOfQuery("SELECT projectName,pg.name,ap.title,pr.ID,pr.abstractProcessID,pg.latestVerProcID
	FROM projects p, process_groups pg, processes pr,abstract_processes ap
	WHERE p.ID=pr.projectID AND pg.ID=pr.processGroupID AND pr.abstractProcessID=ap.ID ORDER BY projectName");
	if(count($procOfProjects)>=2){
		$innerhtml.=getTableHeader(["Project name","Process","Name","Is up to date?"],"projectOverview");
		for($i=0;$i<count($procOfProjects)-1;$i++) {
			$cells=explode("|",$procOfProjects[$i]);
			$innerhtml.=getProcessRowTag($cells[3],"projectOverview",$cells[2]==$cells[3]?"success":"warning");
			for($n=0;$n<count($cells)-3;$n++){
				$innerhtml.="<td>".$cells[$n]."</td>";
			}
			$innerhtml.="<td>".($cells[2]==$cells[3]?"Yes":"No. Newer version is avaliable!<i> Ide még kéne valami</i>")."</td></tr>";
		}
		$innerhtml .= "</tbody></table></div>";
	} else {
		$innerhtml.="<div class='alert alert-success'>You do not have any (process in your) project!</div>";
	}

	$innerhtml.="</div><div id='taskAss' class='well'><h2><b>Task assignment</b></h2>";
	$processes = getRowsOfQuery("SELECT pg.name,p.ID,pr.projectName FROM processes p
			LEFT JOIN projects pr ON pr.ID=p.projectID
			LEFT JOIN process_groups pg ON pg.ID=p.processGroupID
			GROUP BY pg.name");
	//creating a table
	if(count($processes)>1){
		$innerhtml .= "<form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'>";
		for ($j=0;$j<count($processes)-1;$j++){
			$curProcess=explode("|",$processes[$j]);
			$processID=$curProcess[1];
			$innerhtml .= "<hr style='border-color:lightgrey'><h4><b>".$curProcess[0]."</b> (".$curProcess[2].")</h4><br>";
			$innerhtml .= getTableHeader(array("ID","Task name","Profession","RACI","Authorized person","Priority",
				"Estimation received","Estimation","Estimation approval"),"editProcess".$curProcess[1]);
			$rows = getRowsOfQuery("SELECT n.ID,n.txt,concat(professionName,' (',seniority,')'),n.raci,n.responsiblePersonID as resPerID,
			n.priority,n.durationReceived as durRec,sec_to_time(n.duration) as dur,n.durationStatus as durStat,n.professionID as profID
					FROM nodes n
					LEFT JOIN professions prof 
						ON n.professionID=prof.ID
					LEFT JOIN processes p
						ON n.processID=p.ID
					WHERE NOT (n.txt='START' OR n.txt='FINISH') AND n.processID=".$curProcess[1]);
			//creating rows for each node
			for ($i=0; $i < count($rows)-1; $i++) {
				$innerhtml.="<tr>";
				$cells = explode("|",$rows[$i]);
				$professionID = isset($cells[9])?$cells[9]:"";
				$nodeRealID = $cells[0];
				$personID = $cells[4];
				$priority = $cells[5];
				$estRec=isset($cells[6])?$cells[6]:"";
				$est=isset($cells[7])?$cells[7]:"";
				//-1 because prof.ID is for dropdown list
				for ($n=0; $n < 9; $n++) {
					switch ($n) {
						case 2:
							$innerhtml.="<td>";
							if ($cells[$n]!="") {
								$innerhtml.=$cells[$n];
							} else {
								$innerhtml.="<i>There is no profession assigned to this task!</i>";
							}
							$innerhtml.="</td>";
							break;
						case 3:
							$txt=getRACItext($cells[$n]);
							$innerhtml.="<td>$txt</td>";
							break;
						case 4:
							$innerhtml.="<td>";
							if (is_numeric($professionID)) {
								//getting and setting up the person(s selection) for the tasks
								$avaliablePersonRows=getRowsOfQuery("SELECT ID,personName FROM persons WHERE professionID=$professionID");
								//checks if there is any avaliable persons for the task
									if (count($avaliablePersonRows)==1) {
									$innerhtml.="<i>There is no person with this profession!</i>";
								} else {
									$innerhtml.="<select name='personSel".$nodeRealID."' style='width:100%'><option value='-1'> </option>";
									for ($f=0; $f < count($avaliablePersonRows)-1; $f++) { 
										$values=explode("|",$avaliablePersonRows[$f]);
										$innerhtml.='<option value='.$values[0];
										$innerhtml.=$values[0]==$personID?" selected":"";
										$innerhtml.='>'.$values[1].'</option>';
									}
									$innerhtml.="</select>";
								}
							} else {
								$innerhtml.="<i>There is no profession assigned to this task!</i>";
							}
							
							$innerhtml.="</td>";
							break;
						case 5:
							$innerhtml.="<td><input style='width:50px' type='number' name='priorityOf".$nodeRealID."'min='0'";
							if ($priority!=""){
								$innerhtml.="value='$priority'";
							}
							$innerhtml.="></td>";
							break;
						//estimation received
						case 6:
							if ($cells[$n]=="") {
								$innerhtml.="<td>No estimation received yet.</td>";
							} else {
								$innerhtml.="<td>".$cells[$n]."</td>";
							}
							break;
						//estimation (secs)
						case 7:
							if ($est=="") {
								$innerhtml.="<td>No estimation received yet.</td>";
							} else {
								$innerhtml.="<td>".$est."</td>";
							}
							break;
						//estimation approval
						case 8:
							if ($estRec=="" or $est=="") {
								$innerhtml.="<td>There is no estimation to check.</td>";
								break;
							}
							if ($cells[$n]=="" or !isset($cells[$n])) {
								$innerhtml.="<td>
									<button type='submit' class='btn btn-danger' value='Reject' name='estimation$nodeRealID'>
										<span class='glyphicon glyphicon-remove'></span>
									</button>
									<button type='submit' class='btn btn-success' value='Accept' name='estimation$nodeRealID'>
										<span class='glyphicon glyphicon-ok'></span>
									</button>
									</td>";
							} else {
								$innerhtml.=$cells[$n]==1?"<td class='success'>Accepted":"<td class='danger'>Rejected";
								$innerhtml.="</td>";
							}
							break;
						default:
							$innerhtml.="<td>".$cells[$n]."</td>";
					}
				}
				
			}
			$innerhtml .= "</tbody></table>";
			$deadline=getRowsOfQuery("SELECT estimationDeadline FROM processes WHERE ID=$processID")[0];
			$startDate=getRowsOfQuery("SELECT startTime FROM processes WHERE ID=$processID")[0];
			$innerhtml .= "
				<div class='form-inline'>
					<div class='col-sm-5'>
						<input id='estimationDeadline' type='text' class='form-control' 
							value='$deadline' name='estimationDeadline$processID'>
						<button type='submit' class='btn btn-primary' name='estDeadlineSave$processID'>
							Save estimation deadline
						</button>
					</div>
					<div class='col-sm-5'>
						<input id='startDate' type='text' class='form-control'
							value='$startDate' name='startDate$processID'>
						<button type='submit' class='btn btn-primary' name='startDateSave$processID'>
							Save start date
						</button>
					</div>
					<div class='col-sm-2'>
						<button type='submit' style='float:right' class='btn btn-success'>
							Confirm assignments
						</button>
					</div>
				</div>";
			$innerhtml .= "</div>";
		}
		$innerhtml .= "</form>";
	} else {
		$innerhtml.= '<div class="alert alert-success">There isn\'t any processes waiting to start!</div>';
	}
	
	return $innerhtml;
}

function getProcessOwnerHTML(){
	$innerhtml="<a href='newProcess.php' class='btn btn-primary'>
					Create new Process Group
				</a><hr>";

	//setting up recommendation management
	$innerhtml.="<h2><b>Submitted recommendations</b></h2><br>";

	$recProcesses=getRowsOfQuery("SELECT proc.name,proc.ID FROM abstract_processes pr
	LEFT JOIN process_groups proc ON proc.ID=pr.processGroupID
	WHERE NOT pr.status=0
	GROUP BY proc.name");
	if(count($recProcesses)>=2){
		for($i=0;$i<count($recProcesses)-1;$i++) {
			$curProcess=explode("|",$recProcesses[$i]);
			$innerhtml.="<h4><b>".$curProcess[0]."</b></h4>";
			$recOfProc=getRowsOfQuery("SELECT pr.ID,pr.title,p.personName,pr.status,pr.description,pg.latestVerProcID
				FROM abstract_processes pr, persons p, process_groups pg
				WHERE pr.submitterPersonID=p.ID AND pr.processGroupID=".$curProcess[1]." AND NOT pr.status=0 AND pg.ID=pr.processGroupID");
			$innerhtml .= getTableHeader(array("ID","Title","Submitter person","Status","Description","Judgement"),"recsTable".$curProcess[1]);
			for($n=0;$n<count($recOfProc)-1;$n++) {
				$curRec=explode("|",$recOfProc[$n]);
				$innerhtml.=getTableRecordRowTag($curRec[0], "recsTable".$curProcess[1],getColorClass($curRec[3]));
				for ($j=0;$j < count($curRec)-1;$j++){
					$innerhtml.='<td class="text-center">';
					switch($j){
						case 1:
							$innerhtml.=($curRec[$j]==""?"<i>NO TITLE</i>":$curRec[$j]);break;
						case 3:
							//if this is the live version
							if($curRec[0]==$curRec[5]) {
								$innerhtml.="Live, latest version";
							} else {
								$innerhtml.=getStatusName($curRec[3]);
							}
							break;
						case 5:break;
						default:
							$innerhtml.=$curRec[$j];
					}
					$innerhtml.="</td>";
				}
				
				$innerhtml.="<td class='text-center'>";
				//setting up the judgement buttons
				//set up the buttons on submitted recommendations
				if ($curRec[3]==1) {
					$innerhtml.="
					<button class='btn btn-success' type='button'
					onclick='event.stopPropagation();changeRecommendationStatus({$curRec[0]},2)'>Accept</button>
					<button class='btn btn-danger' type='button'
					onclick='event.stopPropagation();changeRecommendationStatus({$curRec[0]},3)'>Refuse and delete</button>";
				//set up the withdraw button
				} else if ($curRec[3]==2 && $curRec[4]==1) {
					$innerhtml.="
					<button class='btn btn-primary' type='button' onclick='event.stopPropagation();withdraw({$curRec[0]});
						changeRecommendationStatus({$curRec[0]},1)'>Withdraw</button>";
				} else {
					$innerhtml.= "<i>This recommendation is ".($curRec[0]==$curRec[5]?"live!":getStatusName($curRec[3]))."</i>";
				}
				$innerhtml.="</td></tr>";
			}
			$innerhtml .= "</tbody></table></div>";
		}
	}


	//setting up profession assingment section
	$innerhtml.="<hr style='border-color:lightgrey'><h2><b>Profession assignment</b> (to the latest version)</h2>";
	$tasksRow = getRowsOfQuery("SELECT n.nodeID, n.name tasknev, n.professionID, n.raci, pg.name, n.ID
		FROM abstract_nodes n, process_groups pg, abstract_processes ap 
		WHERE ap.ID=pg.latestVerProcID AND n.abstractProcessID=ap.ID");
	if(count($tasksRow)>=2){
		$innerhtml .= "<form action='".htmlspecialchars($_SERVER["PHP_SELF"])."' method='post'>";
		$innerhtml.=getTableHeader(array("ID","Task name","Profession","RACI","Process group name"),"tasksTable");
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

//returns the html of a recommendation row opening tag
//on click the preview shows up
//abstractProcessID should be the processID what will be shown 
function getTableRecordRowTag($abstractProcessID,$tableID,$colorClass) {
	$returning="";
	global $connection;
	//query for getting the nodes of the recommendation
	$query = $connection->prepare("SELECT n.nodeID,n.name,n.xCord,n.yCord,n.professionID,n.raci,n.description,p.processGroupID
		FROM abstract_nodes n, abstract_processes p WHERE n.abstractProcessID=? GROUP BY n.ID");
	$query->bind_param('i',$abstractProcessID);
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
	$query = $connection->prepare("SELECT r.ID,r.fromNodeID,r.toNodeID
		FROM abstract_edges r WHERE r.abstractProcessID=?");
	$query->bind_param('i',$abstractProcessID);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$edges="";
	while ($row = $result->fetch_assoc()){
		$edges .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$edges=rtrim($edges,",");

	$returning.="<tr id='recom{$abstractProcessID}' class='{$colorClass}' style='cursor:pointer' 
		onclick=\"viewRecommendation({$abstractProcessID},[{$nodes}],[{$edges}],'$tableID')\">";
	return $returning;
}

//returns the html of a process row opening tag
//on click the preview shows up
function getProcessRowTag($processID,$tableID,$colorClass){
	$returning="";
	global $connection;
	//query for getting the nodes of the recommendation
	$query = $connection->prepare("SELECT n.nodeID,n.txt,n.xCord,n.yCord,n.status,n.professionID,n.responsiblePersonID,
		n.duration,n.raci,n.description,p.processGroupID
		FROM nodes n, processes p,process_groups pg WHERE n.processID=? AND pg.ID=p.processGroupID GROUP BY n.ID");
	$query->bind_param('i',$processID);
	$query->execute();
	confirm($query);
	$result = $query->get_result();
	$nodes="";
	while ($row = $result->fetch_assoc()){
		$nodes .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$nodes=rtrim($nodes,",");

	//query for getting the edges of the recommendation
	$query = $connection->prepare("SELECT e.ID,e.fromNodeID,e.toNodeID
		FROM edges e, processes p,process_groups pg WHERE e.processID=? AND pg.ID=p.processGroupID GROUP BY e.ID");
	$query->bind_param('i',$processID);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$edges="";
	while ($row = $result->fetch_assoc()){
		$edges .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$edges=rtrim($edges,",");

	$returning.="<tr id='process$processID' class='$colorClass' style='cursor:pointer' 
		onclick=\"viewProcess($processID,[$nodes],[$edges],'$tableID')\">";
	return $returning;
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
				$innerhtml="<div class='alert alert-success'>You shouldn't be here as a user...</div>";
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
<?php include(TEMPLATE.DS."footer.php")?>