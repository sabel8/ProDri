/** TABLE FUNCTIONS **/

var activeLi;

//called if a new list item is selected
function getDatabaseEditor(activeLiparam) {
	activeLi=activeLiparam;
	setActiveDropdown(activeLi);
	tableSetup(activeLi);	
}


//modifies the css of the dropdown element
function setActiveDropdown(activeLi) {
	d3.selectAll("ul[id='dropdown'] li").classed("active",false);
	let curLi = d3.select("#"+activeLi)
	curLi.attr("class","active");
	d3.select("#dropdownButton").node().innerHTML =curLi.node().textContent+' <span class="caret"></span>';
}


//getting and setting up the table
function tableSetup(activeLi) {
	//clearing the table if previously drawn
	d3.select("#editTable").node().innerHTML = "";

	//http request
	var array;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
			createEditTable(array);
			setupModal(activeLi,array);
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=edittables&t="+activeLi, true);
	xmlhttp.send();

	//adding the button
	d3.select("#editTable").node().innerHTML +=
		"<br><button type='button' class='btn btn-default' data-toggle='modal' data-target='#newElementModal'>"
		+"<span class='glyphicon glyphicon-plus'></span> Add new</button>";
}

function createEditTable(array) {
	var editTableEl = d3.select("#editTable").node();
	if (array.length <= 1) {
		editTableEl.innerHTML = "<h3>ERROR</h3><br>"+array.toString(); 
	}
	var tableHtml = "<table class='table table-hover'>";
	for (var i = 0;i<array.length-1;i++) {
		
		var columns=array[i].split(",");
		//setting up the column names
		if (i==0){
			//creating row for the column names
			tableHtml += "<thead>";
			tableHtml += "<tr>";
			for (var n=0;n<columns.length;n++){
				tableHtml += "<th>"+columns[n]+"</th>";
			}
			tableHtml += "<th>"+"Edit"+"</th>";
			tableHtml += "</tr></thead>";
			continue;
		} else {
			//creating rows for the records
			tableHtml += "<tr>";
			for (var n=0;n<columns.length;n++){
				tableHtml += "<td>"+columns[n]+"</td>";
			}
			//setting up the edit and remove buttons
			tableHtml += '<td><a onclick="modifyRecord();"><span class="glyphicon glyphicon-edit"></span></a>'
				+' <a onclick="deleteRecord(\''+activeLi+'\','+columns[0]+');">'
				+'<span class="glyphicon glyphicon-remove"></span></a></td>';
		}

		tableHtml += "</tr>";
	}
	tableHtml += "</table>";

	editTableEl.innerHTML += "<br>" + tableHtml;
}

/** MODAL FUNCTIONS **/

//wtf?
function setupModal(activeLi,array) {
	setModalTitle(activeLi);

	
	/*Task name:<br>
	<input type="text" id="nodeTitle" value="Example task 1">
	<br><br>*/
}


//setting the title of the modal
function setModalTitle(activeLi) {
	var title = "",form="";
	switch(activeLi){
		case "projectsDropdown":
			title = "project";
			form = "Project name:<br>"
				+'<input type="text" id="projectName" value="Example project 1"><br><br>';
			break;
		case "processesDropdown":
			title = "process"; 
			form = "Process name:<br>"
				+'<input type="text" id="processTitle" value="Example process 1"><br><br>'
				+'Project name:<br><select id="projectSelect">';
			var projects = getProjects();
			form += "<option value='-1' selected></option>";
			for (var i = 0; i < projects.length-1; i++) {
				//first element is ID
				//second element is name
				let curProject = projects[i].split(",");
				form += "<option value=\""+curProject[0]+"\">"+curProject[1]+"</option>";
			}
			form += "</select>";
			break;
		case "professionsDropdown":
			title = "profession";
			form = "Profession name:<br>"
				+'<input type="text" id="professionName" value="Example profession 1"><br><br>'
				+'Seniority:<br>'
				+'<input type="text" id="seniority" value="junior">';
			break;
		case "personsDropdown":
			title = "person";
			form = "Person name:<br>"
				+'<input type="text" id="personName" value="Slay Lewis"><br><br>'
				+'Profession:<br><select id="professionSelect">';
			var professions = getProfessions();
			for (var i = 0; i < professions.length-1; i++) {
				//first element is ID
				//second element is name
				//third element is seniority
				let curProf = professions[i].split(",");
				form += "<option value=\""+curProf[0]+"\">"+curProf[1]+" ("+curProf[2]+")"+"</option>";
			}
			form += "</select>";
			break;
		case "deliverablesDropdown":
			title = "deliverable";
			form = "Deliverable name:<br>"
				+'<input type="text" id="deliverableName" value="paperwork"><br><br>'
				+'Type:<br><select id="delTypeSelect">';
			var delTypes = getDeliverableTypes();
			for (var i = 0; i < delTypes.length-1; i++) {
				//first element is ID
				//second element is name
				//third element is seniority
				let curDelType = delTypes[i].split(",");
				form += "<option value=\""+curDelType[0]+"\">"+curDelType[1]+"</option>";
			}
			form += "</select>";
			break;
		case "deliverable_typesDropdown":
			title = "deliverable type";
			form = "Name of the deliverable type:<br>"
				+'<input type="text" id="deliverableTypeName" value="Example deliverable type 1"><br><br>'
			break;
	}
	d3.select("#elementName").node().innerHTML = title;
	d3.select("#newElementForm").node().innerHTML = form;
}

//helper function for dropdown menu
//called at new process creating
function getProjects() {
	var array;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getprojects", false);
	xmlhttp.send();
	return array;
}

//helper function for dropdown menu
//called at new person record creating
function getProfessions(){
	var array;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getprofessions", false);
	xmlhttp.send();
	return array;
}

//helper function for dropdown menu
//called at new deliverable record creating
function getDeliverableTypes() {
	var array;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getdeltypes", false);
	xmlhttp.send();
	return array;
}

/** ADDING, EDITING, DELETING RECORD FUNCTIONS **/

//called when create button of the modal is clicked
function createRecord() {
	var result = "";
	switch(activeLi){
		case "projectsDropdown":
			var projectName = d3.select("#projectName").node().value;
			result = runInsert(new Array("projects",projectName));
			alert(result);
			break;
		case "processesDropdown":
			var processName = d3.select("#processTitle").node().value;
			var selectEl = d3.select("#projectSelect").node();
			var projectID = selectEl.options[selectEl.selectedIndex].value;
			result = runInsert(["processes",processName,projectID]);
			alert(result);
			break;
		case "professionsDropdown":
			var professionName = d3.select("#professionName").node().value;
			var seniority = d3.select("#seniority").node().value;
			result = runInsert(["professions",professionName,seniority]);
			alert(result);
			break;
		case "personsDropdown":
			var personName = d3.select("#personName").node().value;
			var selectEl = d3.select("#professionSelect").node();
			var professionID = selectEl.options[selectEl.selectedIndex].value;
			result = runInsert(new Array("persons",personName,professionID));
			alert(result);
			break;
		case "deliverablesDropdown":
			var deliverableName = d3.select("#deliverableName").node().value;
			var selectEl = d3.select("#delTypeSelect").node();
			var delTypeID = selectEl.options[selectEl.selectedIndex].value;
			result = runInsert(new Array("deliverables",deliverableName,delTypeID));
			alert(result);
			break;
		case "deliverable_typesDropdown":
			var deliverableName = d3.select("#deliverableTypeName").node().value;
			result = runInsert(new Array("deliverable_types",deliverableName));
			alert(result);
			break;
	}
	//refreshing the table
	tableSetup(activeLi);
}

function deleteRecord(tableName, ID) {
	var params = new Array(tableName.substring(0,tableName.length-8),ID);
	var response;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			response = this.responseText;
			tableSetup(activeLi);
		}
	};
	xmlhttp.open("GET", "php_functions/setdatas.php?q=delete&p="+params.toString(), false);
	xmlhttp.send();
	alert(response);
}