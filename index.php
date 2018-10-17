<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
?>

<script src="app.js" type="text/javascript"></script>

<div class="container" id="body">
	<p>CTRL+katt a node-on   --> állapot változtatás</p>
	<p>shift+húzás a noderól --> új edge létrehozása</p>
	<p>katt a node-on        --> infó róla</p>
	<p>Shift+katt a vásznon  --> új node létrehozása</p>
	<hr>
	<p>
		ID of starting node: <input type="number" id="startNodeID" value="1"><br>
		ID of finishing node: <input type="number" id="finishNodeID" value="2">
	</p>
	<div id="pathCalculationModes">
		<p>Mode of critical path calculation:<br>
			<input id="IDMode" type="radio" name="pathCalculationMode">
			<label for="IDMode">Use the ID's above</label><br>
			<input id="startEndMode" type="radio" name="pathCalculationMode" checked>
			<label for="startEndMode">Use START --> END nodes</label>
		</p>
	</div>
	<hr>
	<div id="authorities">
		<p>Authority (beta):
			<input id="processOwnerAuth" type="radio" name="authority" value="po" checked>
			<label for="processOwnerAuth">Proces owner</label>
			<input id="userAuth" type="radio" name="authority" value="u">
			<label for="userAuth">User</label>
		</p>
	</div>

	<a id="objectInfoModalTrigger" style="display: none" data-toggle="modal" href="#objectInfoModal"></a>
	<a id="newNodeModalTrigger" style="display: none" data-toggle="modal" href="#newNodeModal"></a>

	<!-- Obejct Info Modal -->
	<div id="objectInfoModal" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 id="objectName" class="modal-title">Object name</h4>
				</div>
				<div class="modal-body">
					<p id="objectInfo">Object info....</p>
					<select id="statusSelect" onchange="selectStatus()">
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
						Knowledge area: (beta)<br>
						<input type="text" id="nodeKnowledgeArea" value="lawyer">
						<br><br>
						Responsible person: (beta)<br>
						<input type="text" id="nodeResponsiblePerson" value="Soma Kiss">
						<br><br>
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
					<button style="float:right;" type="button" class="btn btn-primary" data-dismiss="modal" onclick="nodes.push(getNodeData());redraw();reviseInAndOutputs()">Create</button>
				</div>
			</div>

		</div>
	</div>
	
<?php include(TEMPLATE.DS."footer.php")?>