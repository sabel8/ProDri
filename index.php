<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
?>

<script src="scripts/app.js" type="text/javascript"></script>

<div class="container" id="body">
	<p>CTRL+katt a node-on   --> állapot változtatás</p>
	<p>shift+húzás a noderól --> új edge létrehozása</p>
	<p>katt a node-on        --> infó róla</p>
	<p>Shift+katt a vásznon  --> új node létrehozása</p>
	<p>Ha változtatást jóvá akarod hagyni akkor a submit graph gombra nyomj!</p>
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
	<br>
	<div id="mainDiv">
		<button class="btn btn-default" onclick="downloadButton()">Download</button>
		<!--<input type="file" accept=".json" onchange="upload(event)" id="hidden-file-upload">
		<button class="btn btn-default" onclick="uploadButton()">Upload</button>-->
		<button class="btn btn-default" onclick="calc()">Critical path</button>
		<?php
		//user cannot delete nor overwrite the graph
		if($_SESSION["auth"]!="u"){ echo '<button class="btn btn-default" onclick="deleteSelected()">Delete</button>
		<button id="submitGraphButton" class="btn btn-default" onclick="submitGraphHome()">Submit graph</button>';} ?>
		<br><br>
	</div>

	<a id="objectInfoModalTrigger" style="display: none" data-toggle="modal" href="#objectInfoModal"></a>
	<a id="newNodeModalTrigger" style="display: none" data-toggle="modal" href="#newNodeModal" onclick="setupNewNodeModal()"></a>

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

						Profession:<br>
						<select id="professionSelect" onchange="professionChange()">
						</select><br><br>

						Responsible person:<br>
						<select id="personSelect">
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
					<button style="float:right;" type="button" class="btn btn-primary" data-dismiss="modal" onclick="nodes.push(getNodeData());redraw();reviseInAndOutputs();d3.select('#submitGraphButton').attr('class','btn btn-danger');">Create</button>
				</div>
			</div>

		</div>
	</div>
<?php include(TEMPLATE.DS."footer.php")?>