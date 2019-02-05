<?php 
require_once("config.php");
if ($_POST) {
	//print_r($_POST);
	// Redirect to this page with wiped _POST.
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
include(TEMPLATE.DS."header.php");?>
<div class="container">
	<div id="formBody" class="well">
		<h2>Creating a new abstract process</h2>
		<hr>
		<div class="form-horizontal">
			<div class="form-group">
				<label class="control-label col-sm-2" for="processName">
					<b>Process name:</b>
				</label>
				<div class="col-sm-5">
					<input id="processName" type="text" class="form-control" name="processName" required>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-sm-2" for="processDesc">
					<b>Description:</b>
				</label>
				<div class="col-sm-5">
					<textarea  id="processDesc" class="form-control" name="processDesc" required></textarea>
				</div>
			</div>
		</div>
		<div id="processBuilder"></div>
		<br>
		<button class="btn btn-success" id="createProcess" onclick="submitProcess()">Create process</button>
		<p id="res"></p>
	</div>
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
					Description: <br>
					<textarea id="nodeDescription" rows="5" cols="50" placeholder="Example..."></textarea>
				</form> 
			</div>
			<div class="modal-footer">
				<button style="float:left;" type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button style="float:right;" type="button" class="btn btn-primary" data-dismiss="modal" 
				onclick="graphObj.nodes.push(getNodeData());redraw();">
				Create</button>
			</div>
		</div>

	</div>
</div>
<?php include(TEMPLATE.DS."footer.php"); ?>