<?php
require_once("config.php");
$userID=$_SESSION['userID'];

//if post is set, update database then clear post
//avoiding repetitive form submission
if ($_POST) {
  // Execute code (such as database updates) here.
  global $connection;
  if (isset($_POST['startTask'])) {
    $query = $connection->prepare("UPDATE nodes SET actualStart=NOW(),lastUpdateTime=NOW() WHERE ID=?");
    $query->bind_param("i",$_POST['startTask']);
    if ($query->execute()) {
    } else {
      echo "Error starting task!";
    }
  } else if (isset($_POST['finishTask'])) {
    $query = $connection->prepare("UPDATE nodes SET actualFinish=NOW(),lastUpdateTime=NOW(),status=9 WHERE ID=?");
    $query->bind_param("i",$_POST['finishTask']);
    if ($query->execute()) {
    } else {
      echo "Error finishing task!";
    }
  } else if (isset($_POST['deleteDeliverable'])) {
    $delPath = $connection->prepare("SELECT processID,ID FROM nodes WHERE ID=
        (SELECT nodeID FROM deliverables WHERE ID=?) AND responsiblePersonID=?");
    $delPath->bind_param("ii",$_POST['deleteDeliverable'],$_SESSION['userID']);
    if ($delPath->execute()) {
      $delPath->bind_result($delProcessID,$delNodeID);
      $delPath->fetch();
      $delPath->close();
      unset($delPath);
      $delFileName=getRowsOfQuery("SELECT name FROM deliverables WHERE ID=".$_POST['deleteDeliverable'])[0];
      unlink("deliverables/$delProcessID/$delNodeID/$delFileName");
    } else {
      echo "Hack or error: ".mysqli_error($connection);
      return;
    }
    $query = $connection->prepare("DELETE FROM deliverables WHERE ID=?");
    $query->bind_param("i",$_POST['deleteDeliverable']);
    if ( !($query->execute()) ) {
      echo "Error deleting deliverable record!";
    }
  } else if (isset($_POST['deliverableRefuse'])) {
    $query = $connection->prepare("UPDATE deliverables SET status=1 WHERE ID=?");
    $query->bind_param("i",$_POST['deliverableRefuse']);
    if ($query->execute()) {
      $query->close();
      unset($query);
      //reopening the predecessor task
      $query = $connection->prepare("UPDATE nodes SET actualFinish=null WHERE ID=(SELECT nodeID
        FROM deliverables WHERE ID=?)");
      $query->bind_param("i",$_POST['deliverableRefuse']);
      if (!$query->execute()) {
        echo "Error repoening the node!";
      }
    } else {
      echo "Error refusing deliverable!";
    }
  }

  foreach ($_POST as $key => $value) {
    if ($value!=-1){
      if (substr($key,0,11) == "taskDurSecs"){
        $nodeID=substr($key,11);
        $query = $connection->prepare("UPDATE nodes SET duration=?,lastUpdateTime=NOW(),durationReceived=NOW(),durationStatus=NULL WHERE ID=?");
        $query->bind_param("ii",$value,$nodeID);
        if ($query->execute()) {
        } else {
          echo "Error while updating records!";
        }
      }
    }
  }
  // Redirect to this page, clearing the POST array
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();
}

//updating the status here
$involvedProcesses=getRowsOfQuery("SELECT processID FROM nodes WHERE responsiblePersonID=$userID GROUP BY processID");
//delete all non-done calendar event which is in the processes of the user
$deleteSchedule=$connection->prepare("DELETE FROM unavaliable_timeslots WHERE nodeID IN 
	(SELECT ID FROM nodes WHERE processID IN (SELECT processID FROM nodes 
	WHERE responsiblePersonID=? GROUP BY processID) AND NOT status=9 AND actualFinish IS NULL)");
$deleteSchedule->bind_param("i",$userID);
if ($deleteSchedule->execute()) {
} else {
  echo "Error deleting events!";
}
for ($l=0; $l < count($involvedProcesses)-1; $l++) { 
  $_GET['processID']=$involvedProcesses[$l];
  require("php_functions/updateProcess.php");
}

include(TEMPLATE.DS."header.php");

function getUserHTML($userID) {
  global $connection;
  $username=getRowsOfQuery("SELECT personName FROM persons WHERE ID=$userID")[0];
  $innerhtml="<div class='row'><div class='col-sm-4'><h2><b>Processes:</b></h2><hr>";
  //getting the processes which the user is included in
	$processesInvolvedRows=getRowsOfQuery("SELECT pg.latestVerProcID,pg.name,pg.ID
    FROM abstract_processes ap
    LEFT JOIN process_groups pg ON pg.ID=ap.processGroupID
    LEFT JOIN processes p ON p.processGroupID=pg.ID
    LEFT JOIN nodes n ON n.processID = p.ID
    WHERE n.responsiblePersonID=$userID
    GROUP BY pg.ID ORDER BY pg.name, title");
  $innerhtml.="<div class='list-group' style='width:100%' name='involvedProcesses'>";
  if (count($processesInvolvedRows)==1) {
    $innerhtml.="<i>You are not involved in any processes!</i>";
  } else {
    for ($i=0;$i<count($processesInvolvedRows)-1;$i++) {
    $cells=explode("|",$processesInvolvedRows[$i]);
    $nodeJSON=getNodesOfRec($cells[0]);
    $edgeJSON=getEdgesOfRec($cells[0]);
    $innerhtml.="<a class='list-group-item' onclick='title=null;processGroupID=".$cells[2].";
        createRecommendation2($nodeJSON,$edgeJSON,{$cells[0]},true,false)'
        value='{$cells[0]}'>{$cells[1]}</a>";
    }
  }	
	$innerhtml.= "</div>";

  //setting up the log table
	$innerhtml.="</div><div class='col-sm-8'><h2 style='color:red'><b>Info:</b></h2><hr>";
	$recLogRows=getRowsOfQuery("SELECT p.name,timestamp,text FROM system_message_log log
      LEFT JOIN process_groups p ON log.processID=p.ID
      WHERE (typeID=16 or typeID=17 or typeID=18) 
      ORDER BY timestamp DESC");
  if(count($recLogRows)>1){
    $innerhtml.=getTableHeader(array("Process group","Date & Time","Log"),"logTable");
    for ($i=0;$i<count($recLogRows)-1;$i++) {
      $curLog=explode("|",$recLogRows[$i]);
      $innerhtml.="<tr>";
      for ($n=0;$n<count($curLog);$n++) {
        $innerhtml.="<td>";
        switch ($n) {
          case 0:$innerhtml.=($curLog[$n]==""?"<i>NO TITLE</i>":$curLog[$n]);break;
          default:
            $innerhtml.=htmlspecialchars($curLog[$n]);
        }
        $innerhtml.="</td>";
      }
      $innerhtml.="</tr>";
    }
    $innerhtml.="</tbody></table></div>";
  }



  $innerhtml.="</div></div><div id='manageEditorBody'>

  <button class='btn btn-default' onclick='d3.select(\"#recommendationSelectModalTrigger\").node().click()'>
  <span class='glyphicon glyphicon-open'></span> Load recommendation
  </button>

  <button id='createRecButton' type='button' class='btn btn-success' style='display:none' onclick='createRec(processGroupID,4,true)'>
  <span class='glyphicon glyphicon-save'></span> Create recommendation
  </button>
  
  <button id='saveRecButton' type='button' class='btn btn-success' style='display:none' onclick='updateElementsOfRec(absProcID,true)'>
  <span class='glyphicon glyphicon-save'></span> Save recommendation
  </button>

  <button id='submitRecButton' type='button' class='btn btn-primary' style='display:none' onclick='changeRecommendationStatus(absProcID,1)'>
  <span class='glyphicon glyphicon-ok'></span> Submit recommendation
  </button>

  </div>";

	//setting up the modal for recommendation selection
	$innerhtml.='<a id="recommendationSelectModalTrigger" data-toggle="modal" href="#recommendationSelectModal" style="display:none"></a>';
	$options="";
	$recs=getRowsOfQuery("SELECT p.ID,pg.name,p.status,p.title,p.processGroupID FROM abstract_processes p,process_groups pg 
    WHERE p.processGroupID=pg.ID AND p.submitterPersonID=$userID");
	for ($i=0;$i<count($recs)-1;$i++) {
    $curRec=explode("|",$recs[$i]);
    //setting the strings xss safe
    $curRec = array_map("htmlspecialchars", $curRec);
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
    $processID=$curRec[4];
    $title=$curRec[3];
    $options.="<a class='list-group-item' data-dismiss='modal' onclick='var title=\"$title\";processGroupID=$processID;
      absProcID=$recomID;viewRec2($nodeString,$edgeString,$recomID,{$curRec[2]},true,false)' value='{$curRec[0]}'>"
      .($title==""?"":$title." : ").$curRec[1]." <span class='badge'>".$status."</span></a>";
	}
	$numOfRows=count($recs);
	$actionParam=htmlspecialchars($_SERVER["PHP_SELF"]);
	$innerhtml.='<div id="recommendationSelectModal" class="modal fade" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 id="objectName" class="modal-title">Your recommendations</h4>
          </div>
          <div class="modal-body">
              <div class="list-group" id="recommendationSelect">
               '.($options==""?"<b>You do not have any recommendations!</b>":$options).'
              </div>
          </div>
        </div>
      </div>
    </div>';
	return $innerhtml;
	
}


?>

<div class="container well">
<script type="text/javascript">
//onload set talbes the data structure
$(document).ready( function () {
    $('#logTable').DataTable({
      "pageLength":5,
      "lengthMenu":[[5,10,-1],[5,10,"All"]],
      "order": [ 1, 'desc' ]
    });
} );
</script>
<script type="text/javascript" src="scripts/manage.js"></script>
  <?php echo getUserHTML($userID)?>
</div><div class="container well">
 
  <h2><b>Tasks</b></h2>
  <?php
  $innerhtml="<div id='infoBox'></div>";
  //getting and setting the tasks table
  $tasks = getRowsOfQuery("SELECT n.nodeID, n.txt, n.description, concat(pg.name,' (',proj.projectName,')'),
     concat(prof.professionName,' (',prof.seniority,')'),'inputs', n.status, n.RACI, n.duration,n.plannedStart,n.actualStart
	 ,n.plannedFinish,n.actualFinish,n.lastUpdateTime,n.priority,n.actualDuration,'sys log',n.processID,n.durationStatus,n.ID
		FROM nodes AS n
		LEFT JOIN persons rp
			ON n.responsiblePersonID=rp.ID 
		LEFT JOIN processes process
			ON n.processID=process.ID
		LEFT JOIN projects proj
			ON process.projectID=proj.ID
    LEFT JOIN professions prof
      ON prof.ID=n.professionID
    LEFT JOIN abstract_processes ap
      ON ap.ID=process.abstractProcessID
    LEFT JOIN process_groups pg
      ON process.processGroupID=pg.ID
		WHERE rp.ID=$userID
    ORDER BY n.status DESC, n.priority DESC");
  if (count($tasks)>1){
    $innerhtml.=getTableHeader(array("ID","Name","Description","Process (project)","Profession","Inputs",
	  "Status","RACI","Estimated duration","Planned start","Actual start","Planned finish","Actual finish",
	  "Last update at","Priority","Actual Duration","Sytem message","Deliverable(s)","Start / Finish"),"tasksTable");
    for ($i=0;$i<count($tasks)-1;$i++) {
      $curTask=explode("|",$tasks[$i]);

      $nodeAbstractID=$curTask[0];
      $status=$curTask[6];
      $plannedStart=$curTask[9];
      $actualStart=$curTask[10];
      $actualFinish=$curTask[12];
      $actualDur=$curTask[15];
      $processID=$curTask[count($curTask)-3];
      $durStat=$curTask[count($curTask)-2];
      $nodeRealID=$curTask[count($curTask)-1];
      $innerhtml.="<tr>";
      //-2 because n.ID,n.durationStatus will not be displayed
      for ($n=0;$n<19;$n++) {
        switch ($n) {
          //ID
          case 0:$innerhtml.="<td>$nodeRealID</td>";break;
          //input
          case 5:
            $predecessors=getRowsOfQuery("SELECT ID FROM nodes WHERE processID=$processID AND nodeID IN 
              (SELECT fromNodeID FROM edges WHERE toNodeID=$nodeAbstractID AND processID=$processID)");
            $innerhtml.="<td><form method='post'>";
            $delCount=0;
            for ($k=0; $k < count($predecessors)-1; $k++) {
              $predecessorID = $predecessors[$k];
              $dir = "deliverables/$processID/{$predecessorID}";
              if (!file_exists($dir) or !is_dir($dir)) {continue;} 
              if ($handle = opendir($dir)) {
                  while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != "..") {
                      $curDeliverable =  explode("|",getRowsOfQuery("SELECT ID,status FROM deliverables 
                        WHERE nodeID=$predecessorID AND name='$entry'")[0]);
                      $curDelID = $curDeliverable[0];
                      $curDelStatus = $curDeliverable[1];
                      if ($curDelStatus==0) {
                        $innerhtml .= "<button class='btn btn-danger' type='submit' name='deliverableRefuse'
                          value='$curDelID'>Refuse</button>";
                      } else if ($curDelStatus==1) {
                        $innerhtml .="This input is refused.";
                      }
                      $innerhtml .= "<br><a href='$dir/$entry'>$entry</a><hr><br>";
                      $delCount++;
                    }
                  }
                  closedir($handle);
              }
            }
            if ($delCount==0) {
              $innerhtml.="No input is avaliable.";
            }
            $innerhtml.="</form></td>";
            break;
          //status
          case 6:
            switch ($curTask[$n]) {
              case 4:
                $colorClass = "class='success'";break;
              case 5:
                $colorClass = "class='warning'";break;
              case 6:
                $colorClass = "class='danger'";break;
              default:
                $colorClass = "";
            }
            $innerhtml.="<td $colorClass>".getStatusText($curTask[$n])."</td>";break;
          //raci
          case 7:
            $innerhtml.="<td>".getRACItext($curTask[$n])."</td>";
            break;
          //duration
          case 8:
            $formHTML='<form action="" method="post">
            <input type="number" id="taskDurSecs'.$nodeRealID.'" name="taskDurSecs'.$nodeRealID.'"
              style="width:50px" min="0"> seconds<br>
            <input type="submit" class="btn btn-default" value="Submit"></form>';
            if ($durStat==1) {
              $innerhtml.="<td class='success'>".$curTask[$n]." seconds accepted</td>";
            } else if ($durStat==="0") {
              $innerhtml.="<td class='danger'><p>REFUSED</p>$formHTML</td>";
            } else {
              if ($curTask[$n]!="" && $durStat==""){
                $formHTML=$curTask[$n]." second is submitted.<hr>".$formHTML;
              }
              $innerhtml.="<td>$formHTML</td>";
            }
            break;
          //planned start
          case 9:
            $innerhtml .= "<td>". ($curTask[$n]==null ? "Not yet scheduled" : $curTask[$n]) ."</td>";
            break;
          //actual start
          case 10:
            $innerhtml .= "<td>". ($curTask[$n]==null ? "Not yet started" : $curTask[$n]) ."</td>";
            break;
          //planned finish
          case 11:
            $innerhtml .= "<td>". ($curTask[$n]==null ? "Not yet scheduled" : $curTask[$n]) ."</td>";
            break;
          //actual finish
          case 12:
            $innerhtml .= "<td>". ($curTask[$n]==null ? "Not yet finished" : $curTask[$n]) ."</td>";
            break;
          //Actual duration
          case 15:
            $innerhtml .= "<td>".($actualDur!=""?$actualDur." seconds":"Not set")."</td>"; break;
          //deliverables
          case 17:
            $innerhtml.="<td>";
            $foundDeliverable=false;
            $dir = "deliverables/$processID/$nodeRealID";
            if (file_exists($dir) or is_dir($dir)) {
              if ($handle = opendir($dir)) {
                while (false !== ($entry = readdir($handle))) {
                  if ($entry != "." && $entry != "..") {
                    $foundDeliverable=true;
                    $curDel=explode("|",getRowsOfQuery("SELECT ID,status FROM deliverables WHERE nodeID=$nodeRealID AND name='$entry'")[0]);
                    $curDelID=$curDel[0];
                    $curDelStatus=$curDel[1];
                    $innerhtml.=($curDelStatus==1?"Refused! ":"")."<form method='POST'><div style='white-space:nowrap'>
                      <a href='$dir/$entry'>$entry</a>
                      <button type='submit' name='deleteDeliverable' value='$curDelID' class='btn btn-danger'>
                      <span class='glyphicon glyphicon-trash'></span>
                      </button></div></form>
                      <hr>";
                    $delCount++;
                  }
                }
                $innerhtml = $foundDeliverable==true ? substr($innerhtml,0,-4): $innerhtml;
                closedir($handle);
              }
            }
            
            if ($status==4 or $status==5 or $status==6){
              $innerhtml.='<form id="deliverableUploadForm'.$nodeRealID.'" enctype="multipart/form-data">
                <label class="uploadLabel" for="fileToUpload'.$nodeRealID.'">Select...</label>
                <input type="file" name="fileToUpload" id="fileToUpload'.$nodeRealID.'" required>
                <button type="button" onclick="fileUpload('.$nodeRealID.')" class="btn btn-primary">
                  <span class="glyphicon glyphicon-upload"></span> Upload deliverable
                </button>
                
              </form></td>';/* <progress id="progress'.$nodeRealID.'"></progress> */
            } else if ($foundDeliverable==false){
              $innerhtml.="You cannot upload a deliverable.</td>";
            }
            
            break;
          //start/finish button
          case 18:
            $innerhtml.="<td>";
            $predecessorStatus=getRowsOfQuery("SELECT status FROM nodes WHERE nodeID IN (SELECT fromNodeID
            FROM edges WHERE processID=$processID AND toNodeID=$nodeAbstractID) AND processID=$processID");
            $canBeStarted=true;
            for ($j=0; $j < count($predecessorStatus)-1; $j++) { 
              //if a predecessor task is not done
              if ($predecessorStatus[$j]!="9") {$canBeStarted=false;break;}
            }
            if ($plannedStart=="") {
              $innerhtml.="The process have not started yet, please wait.</td>"; break;
            } else if (!$canBeStarted){
              $innerhtml.="Not all predecessors are done, please wait.</td>"; break;
            }
            $innerhtml.="<form action='' method='post'>";
            if($actualStart==null) {
              $innerhtml .= "<button class='btn btn-success' type='submit' value='$nodeRealID' name='startTask'>Start</button>";
            } else {
              if ($actualFinish==""){
              $innerhtml .= "<button class='btn btn-danger' type='submit' value='$nodeRealID' name='finishTask'>Finish</button>";
              }
            }
            $innerhtml.="</form></td>";
            break;

          default:
            $innerhtml.="<td>".htmlspecialchars($curTask[$n])."</td>";
        }
        $innerhtml.="</td>";
      }
      $innerhtml.="</tr>";
    }
    $innerhtml.="</tbody></table></div>";
  } else {
    $innerhtml.="<i>There isn't any task for you at the moment.</i>";
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

<a id="newNodeModalTrigger" style="display: none" data-toggle="modal" href="#newNodeModal" onclick="setupNewNodeModal()"></a>

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

          Profession:<br>
          <select id="professionSelect">
          </select><br><br>

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
         onclick="addNewRecNode()" data-dismiss="modal">Create</button>
      </div>
    </div>

  </div>
</div>


<?php include(TEMPLATE.DS."footer.php")?>