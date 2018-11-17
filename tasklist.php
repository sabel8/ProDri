<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
$username="John Smith";
//if post is set, update database then clear post
//avoiding repetitive form submission
if ($_POST) {
  // Execute code (such as database updates) here.
  foreach ($_POST as $key => $value) {
    if ($value!=-1){
      global $connection;
      $query = $connection->prepare("UPDATE nodes SET duration=? WHERE ID=?");
      $query->bind_param("ii",$value,intval(substr($key,7)));
      if ($query->execute()) {
      } else {
        echo "Error while updating records!";
      }
      
    }
  }
  // Redirect to this page.
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit();
}

function getUserHTML($username) {
	global $connection;
	$innerhtml="<div class='row'><div class='col-sm-4'><h3>Processes: (for $username)</h3><hr>";
	$processesInvolvedRows=getRowsOfQuery("SELECT n.processID,concat(processName,' (',projectName,')') 
    FROM processes p, nodes n, projects proj
    WHERE n.responsiblePersonID=(SELECT pers.ID FROM persons pers WHERE pers.personName='$username') 
    AND proj.ID=p.projectID AND p.ID=n.processID
    GROUP BY p.ID ORDER BY projectName, processName");
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

      $innerhtml.="<option onclick=\"processID=".$cells[0].";createRecommendation2([$nodeString],[$edgeString],".$cells[0].",true,false)
      \" value='".$cells[0]."'>".$cells[1]."</option>";
    }
	$innerhtml.= "</select>";

  //setting up the log table
	$innerhtml.="</div><div class='col-sm-8'><h3 style='color:red'><b>Info:</b></h3><hr>";
	$recLogRows=getRowsOfQuery("SELECT processName,timestamp,text FROM system_message_log log
      LEFT JOIN processes p ON log.processID=p.ID
      WHERE (typeID=16 or typeID=17 or typeID=18) 
      ORDER BY timestamp DESC");
  if(count($recLogRows)>1){
    $innerhtml.=getTableHeader(array("Process","Time","Log"),"logTable");
    for ($i=0;$i<count($recLogRows)-1;$i++) {
      $curLog=explode("|",$recLogRows[$i]);
      $innerhtml.="<tr>";
      for ($n=0;$n<count($curLog);$n++) {
        $innerhtml.="<td>";
        switch ($n) {
          case 0:$innerhtml.=($curLog[$n]==""?"<i>NO TITLE</i>":$curLog[$n]);break;
          default:
            $innerhtml.=$curLog[$n];
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

  <button id='createRecButton' type='button' class='btn btn-success' style='display:none' onclick='submitGraph(processID,4)'>
  <span class='glyphicon glyphicon-ok'></span> Create recommendation
  </button>

  <button id='submitRecButton' type='button' class='btn btn-primary' style='display:none' onclick='changeRecommendationStatus(recomID,1)'>
  <span class='glyphicon glyphicon-ok'></span> Submit recommendation
  </button>

  </div>";

	//setting up the modal for recommendation selection
	$innerhtml.='<a id="recommendationSelectModalTrigger" data-toggle="modal" href="#recommendationSelectModal" style="display:none"></a>';
	$options="";
	$recs=getRowsOfQuery("SELECT r.ID,processName,r.status,r.title FROM recommendations r,processes p 
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
    $options.="<option data-dismiss='modal' onclick=\"recomID=$recomID;viewRec2([$nodeString],[$edgeString],
      $recomID,".$curRec[2].",false,true)\" value='".$curRec[0]."'>".($curRec[3]==""?"":$curRec[3]." : ").$curRec[1]." (".$status.")</option>";
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
 
  <h3>Tasks (for: <?php echo $username; ?>)</h3>
  <?php
  $innerhtml="";
  //getting and setting the tasks table
  $tasks = getRowsOfQuery("SELECT n.nodeID, n.txt, n.description, concat(p.processName,' (',proj.projectName,')'),
     concat(prof.professionName,' (',prof.seniority,')'),'under development', n.status, n.RACI, n.duration,'under dev','under development.'
	 ,'under development..','under development...','under development....',n.priority,'sample log','blank','two buttons..',n.ID
		FROM nodes AS n
		LEFT JOIN persons AS rp
			ON n.responsiblePersonID=rp.ID 
		LEFT JOIN processes AS p
			ON n.processID=p.ID
		LEFT JOIN projects proj
			ON p.projectID=proj.ID
    LEFT JOIN professions prof
      ON prof.ID=n.professionID
		WHERE rp.personName='".$username."'
    ORDER BY n.status DESC, n.priority DESC");
  if (count($tasks)>1){
    $innerhtml.=getTableHeader(array("ID","Name","Description","Process (project)","Profession","Inputs",
	  "Status","RACI","Estimated duration","Planned start","Actual start","Planned finish","Actual finish",
	  "Last update at","Priority","Sytem message","Deliverable(s)","Start / Finish"),"tasksTable");
    for ($i=0;$i<count($tasks)-1;$i++) {
      $curTask=explode("|",$tasks[$i]);
      $innerhtml.="<tr>";
      //-1 because n.ID will not be displayed
      for ($n=0;$n<count($curTask)-1;$n++) {
        $innerhtml.="<td>";
        switch ($n) {
          case 6:
            $innerhtml.=getStatusText($curTask[$n]);break;
          case 7:
            $innerhtml.=getRACItext($curTask[$n]);
            break;
          case 8:
            $innerhtml.='<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'" method="post">'.
                '<input type="number" name="taskDur'.$curTask[count($curTask)-1].'" 
                style="width:40px" min="1" value="'.($curTask[$n]==NULL?"1":$curTask[$n]).'">'.
                ' <input type="submit" class="btn btn-default" value="Submit"></form>';
            break;
          default:
            $innerhtml.=$curTask[$n];
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