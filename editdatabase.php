<?php
require_once("config.php");
include(TEMPLATE.DS."header.php");
?>

<div class="container">
	<div class="well">
		<div class="dropdown">
		  <button id="dropdownButton" class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Select an option
		  <span class="caret"></span></button>
		  <ul id="dropdown" class="dropdown-menu">
		    <li id="projectsDropdown"><a onclick="getDatabaseEditor('projectsDropdown');">Projects</a></li>
		    <li id="processesDropdown"><a onclick="getDatabaseEditor('processesDropdown');">Processes</a></li>
		    <li class="divider"></li>
		    <li id="personsDropdown"><a onclick="getDatabaseEditor('personsDropdown');">Persons</a></li>
		    <li id="professionsDropdown"><a onclick="getDatabaseEditor('professionsDropdown');">Professions</a></li>
		    <li class="divider"></li>
		    <li id="deliverablesDropdown"><a onclick="getDatabaseEditor('deliverablesDropdown');">Deliverables</a></li>
		    <li id="deliverable_typesDropdown"><a onclick="getDatabaseEditor('deliverable_typesDropdown');">Deliverable types</a></li>
		  </ul>
		</div>
		<div id="editTable">
			
		</div>
	</div>

	<!-- Node Creating Query Modal -->
	<div id="newElementModal" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 id="nodeName" class="modal-title">New <span id="elementName"></span> record:</h4>
				</div>
				<div class="modal-body">
					<form id="newElementForm"> <!--

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
						</div>

						<br><br>
						Description: (beta)<br>
						<textarea name="nodeDescription" rows="5" cols="50">Example...</textarea>

						-->
					</form> 
				</div>
				<div class="modal-footer">
					<button style="float:left;" type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button style="float:right;" type="button" class="btn btn-primary" data-dismiss="modal" onclick="createRecord();">Create</button>
				</div>
			</div>

		</div>
	</div>

<?php include(TEMPLATE.DS."footer.php")?>