<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
?>

<script type="text/javascript" src="scripts/manage.js"></script>

<div class="container">
	<div id="manageBody" class="well">
		<?php
		$username = "Slay Lewis";
		$innerhtml="";
		switch ($_COOKIE["auth"]) {
			//PROJECT MANAGER
			case "pm":
				$innerhtml="<h3><b>WORK IN PROGRESS...</b></h3>";
				break;

			//PROCESS OWNER
			case 'po':
				$innerhtml="<h3>Submitted recommendations</h3>";
				$query = $connection->prepare("
					SELECT r.ID,p.personName,r.status,pr.processName
					FROM recommendations r, persons p, processes pr
					WHERE r.submitterPersonID=p.ID AND r.forProcessID=pr.ID AND NOT r.status=0");
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
					$innerhtml .= <<<TABLE
						<br>
						<div class="table-responsive">
							<table class="table table-bordered table-hover">
								<thead>
									<tr>
										<th class="text-center">ID</th>
								        <th class="text-center">Submitter person</th>
									    <th class="text-center">Status</th>
								        <th class="text-center">Process name</th>
								        <th class="text-center">Judgement</th>
									</tr>
								</thead>
								<tbody>
TABLE;
					for ($i=0; $i < count($rows)-1; $i++) {

						$cells = explode(",",$rows[$i]);

						//query for getting the nodes of the recommendation
						$query = $connection->prepare("SELECT r.nodeID,r.name,r.xCord,r.yCord,r.status,r.professionID,\"\",r.duration,r.raci,re.forProcessID,re.forProcessID FROM recommended_nodes r, recommendations re
							WHERE r.recommendationID=?");
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
						$query = $connection->prepare("SELECT * FROM recommended_edges re WHERE re.recommendationID=?");
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

						switch ($cells[2]) {
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

						$innerhtml.="<tr class='{$colorClass}' style='cursor:pointer' onclick=\"viewRecommendation({$cells[0]},[{$nodes}],[{$edges}])\">";
						for ($n=0;$n < count($cells);$n++){
							$innerhtml.='<td class="text-center">';
							switch($n){
								case 2:
									switch($cells[2]) {
										case 0:
											$innerhtml.="Not yet submitted.";
											break;
										case 1:
											$innerhtml.="Under review...";
											break;
										case 2:
											$innerhtml.="Accepted!";
											break;
										case 3:
											$innerhtml.="Refused.";
											break;
									}
									break;
								default:
									$innerhtml.=$cells[$n];
							}
							$innerhtml.="</td>";
						}
						if ($cells[2]==1) {
							$innerhtml.="<td class='text-center'>
							<button class='btn btn-success' type='button' onclick='changeRecommendationStatus({$cells[0]},2)'>Accept</button>
							<button class='btn btn-danger' type='button' onclick='changeRecommendationStatus({$cells[0]},3)'>Refuse</button>
							</td></tr>";
						} else {
							$innerhtml.= "<td class='text-center'><i>You have already chosen the fate of this recommendation. Thank you!</i></td></tr>";
						}
						
					}
					$innerhtml .= "</tbody></table></div>";
				} else {
					$innerhtml.= '<div class="alert alert-warning">There isn\'t any recommendations for you to review!</div>';
				}
				break;
			//USER
			case "u":
				$innerhtml = <<<DEL
	<h3>My recommendations (user: {$username})</h3>
	<button type="button" class="btn btn-default" onclick="createRecommendation()">
		<span class='glyphicon glyphicon-plus'></span> Add new recommendation
	</button><br><br>
DEL;
				//getting the recommendations of the user
				$query = $connection->prepare("SELECT r.ID,pr.processName,r.status FROM recommendations r, processes pr 
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
						$innerhtml .= <<<TABLE
						<div class="table-responsive">
							<table class="table table-bordered table-hover">
								<thead>
									<tr>
										<th class="text-center">ID</th>
								        <th class="text-center">Process name</th>
									    <th class="text-center">Status</th>
									    <th class="text-center">Submit</th>
									</tr>
								</thead>
								<tbody>
TABLE;
					//getting the nodes and edges for every recommendation
					for ($i=0; $i < count($rows)-1; $i++) {

						$cells = explode(",",$rows[$i]);
						//query for getting the nodes of the recommendation

						//ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID
						$query = $connection->prepare("
							SELECT r.nodeID,r.name,r.xCord,r.yCord,r.status,r.professionID,\"\",r.duration,r.raci,re.forProcessID
							FROM recommended_nodes r, recommendations re WHERE r.recommendationID=? AND re.ID=r.recommendationID");
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
							FROM recommended_edges r, recommendations re WHERE r.recommendationID=? AND re.ID=r.recommendationID");
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
						switch ($cells[2]) {
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
						$innerhtml.="<tr class='{$colorClass}' style='cursor:pointer' onclick=\"viewRecommendation({$cells[0]},[{$nodes}],[{$edges}])\">";
						for ($n=0;$n < count($cells);$n++){
							$innerhtml.='<td class="text-center">';
							switch($n){
								case 2:
									switch($cells[$n]) {
										case 0:
											$innerhtml.="Not yet submitted.";
											break;
										case 1:
											$innerhtml.="Under review...";
											break;
										case 2:
											$innerhtml.="Accepted! Thank you.";
											break;
										case 3:
											$innerhtml.="Refused. Sorry.";
											break;
									}
									break;
								default:
									$innerhtml.=$cells[$n];
							}
							$innerhtml.="</td>";
						}
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
				break;

			//NONE OF THE ABOVE
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

						Profession:<br>
						<select id="professionSelect" onchange="professionChange()">
						</select><br><br>

						Duration:<br>
						<input type="number" id="nodeDuration" value="7">
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
					<button id="createNodeButton" style="float:right;" type="button" class="btn btn-primary" data-dismiss="modal">Create</button>
				</div>
			</div>

		</div>
	</div>
<?php include(TEMPLATE.DS."footer.php")?>