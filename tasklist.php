<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
$username="John Smith";
$workdayHours=8;
//if post is set, update database then clear post
//avoiding repetitive form submission
if ($_POST) {
  global $connection;
  $dur=0;$hours=0;
  $durs=array();
  // Execute code (such as database updates) here.
  foreach ($_POST as $key => $value) {
    if ($value!=-1){
      if (substr($key,0,11) == "taskDurHour"){
        $query = $connection->prepare("UPDATE nodes SET duration=?,lastUpdateTime=NOW() WHERE ID=?");
        $query->bind_param("ii",$value,substr($key,11));
        if ($query->execute()) {
        } else {
          echo "Error while updating records!";
        }
      } else if (substr($key,0,9)=="startTask" and $value="Start"){
        $query = $connection->prepare("UPDATE nodes SET startTime=NOW(),lastUpdateTime=NOW() WHERE ID=?");
        $taskID=substr($key,9);
        $query->bind_param("i",$taskID);
        if ($query->execute()) {
        } else {
          echo "Error while updating records!";
        }
      } else if (substr($key,0,10)=="finishTask" and $value="Finish"){
        $query = $connection->prepare("UPDATE nodes SET finishTime=NOW(),lastUpdateTime=NOW() WHERE ID=?");
        $taskID=substr($key,10);
        $query->bind_param("i",$taskID);
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

function getUserHTML($username) {
	global $connection;
  $innerhtml="<div class='row'><div class='col-sm-4'><h2><b>Processes: ($username)</b></h2><hr>";
  //getting the processes which the user is included in
	$processesInvolvedRows=getRowsOfQuery("SELECT pg.latestVerProcID,pg.name,pg.ID
    FROM abstract_processes ap, process_groups pg,processes p, nodes n
    WHERE n.responsiblePersonID=(SELECT pers.ID FROM persons pers 
    WHERE pers.personName='$username') AND pg.ID=ap.processGroupID
    GROUP BY pg.ID ORDER BY pg.name, title");
	$innerhtml.="<div class='list-group' style='width:100%' name='involvedProcesses'>";
	for ($i=0;$i<count($processesInvolvedRows)-1;$i++) {
		$cells=explode("|",$processesInvolvedRows[$i]);

		//setting up the stringified node array
		$nodesOfProc=getRowsOfQuery("SELECT nodeID,name,xCord,yCord,raci,abstractProcessID,description,professionID 
    FROM abstract_nodes WHERE abstractProcessID=".$cells[0]);
		$nodeString="";
		for($n=0;$n<count($nodesOfProc)-1;$n++){
			$curNode = explode("|",$nodesOfProc[$n]);
			$nodeString.="[";
			for($e=0;$e<count($curNode);$e++){
				$nodeString.='\''.htmlspecialchars($curNode[$e]).'\',';
			}
			//cut down last colon
			$nodeString = substr($nodeString,0,-1)."],";
		}
		//cut down last colon
		$nodeString= substr($nodeString,0,-1);

		//setting up the stringified edge array
		$edgesOfProc=getRowsOfQuery("SELECT ID,fromNodeID,toNodeID FROM abstract_edges WHERE abstractProcessID=".$cells[0]);
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

      $innerhtml.="<a class='list-group-item' onclick=\"title=null;processGroupID=".$cells[2].";
        createRecommendation2([$nodeString],[$edgeString],".$cells[0].",true,false)
        \" value='".$cells[0]."'>".$cells[1]."</a>";
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
    WHERE p.processGroupID=pg.ID");
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
    $options.="<a class='list-group-item' data-dismiss='modal' onclick=\"var title='$title';processGroupID=$processID;
      absProcID=$recomID;viewRec2([$nodeString],[$edgeString],$recomID,".$curRec[2].",true,false)\" value='".$curRec[0]."'>"
      .($title==""?"":$title." : ").$curRec[1]." <span class='badge'>".$status."</span></a>";
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
					<div class="list-group" id="recommendationSelect">
						$options
					</div>
			</div>
		</div>

	</div>
</div>
DEL;
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
  <?php echo getUserHTML("John Smith")?>
</div><div class="container well">
 
  <h2><b>Tasks (for: <?php echo $username; ?>)</b></h2>
  <?php
  $innerhtml="";
  //getting and setting the tasks table
  $tasks = getRowsOfQuery("SELECT n.nodeID, n.txt, n.description, concat(pg.name,' (',proj.projectName,')'),
     concat(prof.professionName,' (',prof.seniority,')'),'inputs', n.status, n.RACI, n.duration,'planned start',n.startTime
	 ,'Planned finish..','Actual finish...',n.lastUpdateTime,n.priority,'sys log','dels','two buttons..',n.ID
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
		WHERE rp.personName='".$username."'
    ORDER BY n.status DESC, n.priority DESC");
  if (count($tasks)>1){
    $innerhtml.=getTableHeader(array("ID","Name","Description","Process (project)","Profession","Inputs",
	  "Status","RACI","Estimated duration (1workday = ".$workdayHours."hrs)","Planned start","Actual start","Planned finish","Actual finish",
	  "Last update at","Priority","Sytem message","Deliverable(s)","Start / Finish"),"tasksTable");
    for ($i=0;$i<count($tasks)-1;$i++) {
      $curTask=explode("|",$tasks[$i]);
      $innerhtml.="<tr>";
      //-1 because n.ID will not be displayed
      for ($n=0;$n<count($curTask)-1;$n++) {
        $innerhtml.="<td>";
        switch ($n) {
          //status
          case 6:
            $innerhtml.=getStatusText($curTask[$n]);break;
          //raci
          case 7:
            $innerhtml.=getRACItext($curTask[$n]);
            break;
          //duration
          case 8:
            if ($curTask[$n]==NULL) {
              $curTaskHour=0;
            } else {
              $curTaskHour=$curTask[$n];
            }//'.htmlspecialchars($_SERVER["PHP_SELF"]).'
            $innerhtml.='<form action="" method="post">'.

                '<input type="number" id="taskDurHour'.$curTask[count($curTask)-1].'" name="taskDurHour'.$curTask[count($curTask)-1].'"
                style="width:50px" min="0" value="'.$curTaskHour.'"> hours<br>'.

                ' <input type="submit" class="btn btn-default" value="Submit"></form>';
            break;
          //actual start
          case 10:
            $innerhtml .= ($curTask[$n]==null ? "Not yet started" : $curTask[$n]);
            break;
          //start/finish button
          case 17:
            $innerhtml.="<form action='' method='post'>";
            if($curTask[10]==null) {
              $innerhtml .= "<input class='btn btn-success' type='submit' name='startTask".$curTask[count($curTask)-1]."' value='Start'>";
            } else {
              $innerhtml .= "<input class='btn btn-danger' type='submit' name='finishTask".$curTask[count($curTask)-1]."' value='Finish'>";
            }
            $innerhtml.="</form>";
            break;
          default:
            $innerhtml.=htmlspecialchars($curTask[$n]);
        }
        $innerhtml.="</td>";
      }
      $innerhtml.="</tr>";
    }
    $innerhtml.="</tbody></table></div>";
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