var pathes=new Array();

//helper function for critical path calculation
function calc() {
	var startingNodeID, finishNodeID;

	if (d3.select("#IDMode").node().checked === true) {
		startingNodeID = d3.select("#startNodeID").node().value;
		finishNodeID = d3.select("#finishNodeID").node().value;

		if (getNodeByID(startingNodeID)==null || getNodeByID(finishNodeID)==null) {
			alert("The selected start or finish node does not exist.");
			return;
		} else if(startingNodeID==finishNodeID) {
			alert("The starting node cannot be the end node too.");
		}
	} else {
		startingNodeID = getStartNodeID();
		finishNodeID = getFinishNodeID();
		if (startingNodeID===-1 || finishNodeID===-1) {
			alert("There is no START or FINISH node!");
			return;
		}
	}

	pathes=new Array();
	var curPath = new Array();
	var durations = new Array();
	//add starting node
	curPath.push(startingNodeID);
	calcPathDur(Number(startingNodeID),Number(finishNodeID),new Array(),curPath);
	for (var i = 0; i < pathes.length; i++) {
		var curIDs = pathes[i].split(',');
		var duration = 0;
		for (var j = 0; j < curIDs.length; j++) {
			duration += getNodeByID(curIDs[j]).duration;
		}
		durations.push(duration);
	}

	//getting the longest path
	if (durations.length===pathes.length) {
		var maxDurIndex=0;
		var maxDuration=durations[maxDurIndex];
		for (var i = 1; i < durations.length; i++) {
			if (maxDuration<durations[i]) {
				maxDuration=durations[i];
				maxDurIndex=i;
			}
		}
		if (pathes[maxDurIndex]==null || maxDuration==null) {
			alert("There is not any route between the start and the end node.")
		} else {
			var critPath = pathes[maxDurIndex];

			//changing the start and finish node ID
			//to its names
			if (critPath.substr(0,1)==getStartNodeID()) {
				critPath = critPath.replace(getStartNodeID(),"START");
			}
			if (critPath.substr(-1)==getFinishNodeID()) {
				critPath = critPath.replace(getFinishNodeID(),"FINISH");
			}

			//replacing the commas to arrows
			critPath = critPath.replace(/,/g," -> ");

			alert("The critical path: (ID of the nodes)"+
				"\n"+critPath+
				"\nIt's duration is : "+maxDuration);
		}
	}
}

function calcPathDur(startID,endID,visited,localPathList) {
	visited[startID] = true;

	if (startID===endID) {
		pathes.push(localPathList.toString());
	}

	for (var i=0;i<edges.length;i++) {
		if (edges[i].fromNodeID===startID) {
			var curNode = getNodeByID(edges[i].toNodeID);
			if(!visited[curNode.ID]) {
				localPathList.push(curNode.ID);
				calcPathDur(curNode.ID,endID,visited,localPathList);
				//removes  last element
				localPathList.pop();
			}
		}
	}
	
	visited[startID]=false;
}

function deleteSelected() {
	edges = graphObj.edges;
	nodes = graphObj.nodes;
	if(justSpectating==true){
		alert("You are in spectating mode, thus cannot delete anything!");
		return;
	}
	/*let nodes = graphObj.nodes;
	let edges = graphObj.edges;*/
	//hiding the status selector of the modal
	d3.select("#statusSelect").style("display","none");
	if (selectedEdge==null && selectedNode==null) {
		alert("Nothing is selected.");
	}
	//DELETE EDGE
	else if (selectedEdge!=null) {
		let response = confirm("Do you really want to delete the selected edge?"
			+" This action cannot be undone.");
		if (response === true) {
			//console.log(runInsert(["edgeDel",selectedEdge.ID]));
			deleteEdge(selectedEdge);
			selectedEdge=null;
			reviseInAndOutputs();
		}
	}
	//DELETE NODE
	else if (selectedNode!=null) {
		if (confirm("Do you really want to delete the selected"
		+"\nnode and all the edges connected to it?"
		+" This action cannot be undone.") === true) {
			//deleting all edges related to the node
			for (var i = 0;i<edges.length;i++) {
				var curEdge=edges[i];
				//if it derives from or points at the node
				if (curEdge.toNodeID==selectedNode.ID || curEdge.fromNodeID==selectedNode.ID) {
					//console.log(runInsert(["edgeDel",curEdge.ID]));
					deleteEdge(curEdge);
					//deletion makes length decreased by one
					i--;
				}
			}
			//deleting the node itself
			//console.log(runInsert(["nodeDel",selectedNode.ID]));
			var pos = getPosInArray(selectedNode.ID,nodes);
			nodes.splice(pos,1);
			selectedNode=null;
			reviseInAndOutputs();
		}
	}
	d3.select("#submitGraphButton").attr("class","btn btn-danger");
	redraw();
}

//called if node status is changed
//in the modal
function selectStatus() {
	var chosen = d3.select("#statusSelect").node().value;
	var curNode = getNodeByID(selectedNode.ID);
	
	//checks if status can be changed then sets it
	switch(chosen) {
		case "notStartedOption":
			curNode.status = 0;
			break;
		case "inProgressOption":
			if (curNode.input == 1){
				curNode.status = 1;
			} else {
				alert("You cannot change this node's status "+
					"because not all predecessors are done.")
				d3.select("#statusSelect").node().value = "notStartedOption";
			}
			break;
		default:
			if (curNode.input == 1) {
				curNode.status = 2;
			} else {
				alert("You cannot change this node's status "+
					"because not all predecessors are done.")
				d3.select("#statusSelect").node().value = "notStartedOption";
			}
	}
	reviseInAndOutputs();
	let infoSplitted = curNode.getRelevantData().replace(new RegExp("; ", 'g'), "<br>");
	d3.select("#objectInfo").html(infoSplitted);
	redraw();
}

//helper function
//do not use directly
function deleteEdge(edge){
	var nodes = graphObj.nodes;
	var edges = graphObj.edges;
	var pos = getPosInArray(edge.ID,edges);
	edges.splice(pos,1);
}

//makes the options for the avaliable professions
//at the node creating table
function setupNewNodeModal() {
	var professions = getProfessions();
	var form="<option value='-1'></option>";
	for (var i = 0; i < professions.length-1; i++) {
		//first element is ID
		//second element is name
		//third element is seniority
		var curProf = professions[i].split(",");
		form += "<option value=\""+curProf[0]+"\">"+curProf[1]+" ("+curProf[2]+")"+"</option>";
	}
	d3.select("#professionSelect").node().innerHTML = form;

	//professionChange();	
}

function professionChange(){
	var selectEl = d3.select("#professionSelect").node();
	var professionID = selectEl.options[selectEl.selectedIndex].value;

	var form="";
	var persons = getPersonOfProfession(professionID);
	for (var i = 0; i < persons.length-1; i++) {
		//first element is ID
		//second element is name
		//third element is seniority
		var curPerson = persons[i].split(",");
		form += "<option value=\""+curPerson[0]+"\">"+curPerson[1]+"</option>";
	}
	d3.select("#personSelect").node().innerHTML = form;
}


//iterates through all nodes and
//defining it's INPUT and OUTPUT value
function reviseInAndOutputs() {
	let nodes = graphObj.nodes;
	let edges = graphObj.edges;
	//sets the output and input values
	//for the predefined data
	for (var i=0;i<nodes.length;i++) {
		var curNode = nodes[i];
		//sets input
		checkForInput(curNode.ID);
		//if input is 0
		//then status changes to 0
		if(curNode.input===0) {
			curNode.status = 0;
		}
		//automatically changes FINISH
		//node status
		if(curNode.txt==="FINISH" && curNode.input===1) {
			curNode.status=2;
		}
		//sets output
		if (curNode.status == 2) {
			curNode.output = 1;
		} else {
			curNode.output = 0;
		}
	}
}

//iterate through the edges and if
//all predecessors are done then
//sets the INPUT of the node to 1
function checkForInput(nodeID) {
	let nodes = graphObj.nodes;
	let edges = graphObj.edges;
	var allPreviousDone = true;
	for (var i=0;i<edges.length;i++) {
		var curEdge = edges[i];
		if (curEdge.toNodeID === nodeID) {
			if (getNodeByID(curEdge.fromNodeID).status != 2) {
				allPreviousDone=false;
				break;
			}
		} 
	}
	if (allPreviousDone === true) {
		getNodeByID(nodeID).input = 1;
	} else {
		getNodeByID(nodeID).input = 0;
	}
}

//called on node creation
//when the query has been submitted
function getNodeData() {
	//x and y values are in mousePos variable
	let x = Math.floor(mousePos[0]);
	let y = Math.floor(mousePos[1]);
	let taskName = d3.select("#nodeTitle").node().value;

	var selectEl = d3.select("#professionSelect").node();
	let profession = selectEl.options[selectEl.selectedIndex].value;

	selectEl = d3.select("#personSelect").node();

	//let duration = d3.select("#nodeDuration").node().value;
	let raci = document.querySelector('input[name="nodeRaci"]:checked').value;
	let desc = $("#nodeDescription").val();

	//VALIDATION MISSING
	return new Node(getValidID(graphObj.nodes),taskName,x,y,0,profession,null,null,raci,null,desc);
	//VALIDATION MISSING
}


//creates a new edge and pushes it to the array
//if it passes validation
function createEdge() {
	let nodes = graphObj.nodes;
	let edges = graphObj.edges;
	if(shiftKeyPressed===true){

		//if mouse is released without
		//hovering over any edge
		if (toNode!=null) {

			//if the user drags the line to the same node
			//it aborts the creation process of the edge
			if (toNode.ID!=fromNode.ID){
				var a = new Edge(getValidID(edges),fromNode.ID,toNode.ID);

				let isValidEdge = true;

				//iterating through the edge array
				//for validating the edge
				for (var i = 0;i<edges.length;i++) {
					let cur = edges[i];

					//if there is already an edge like this
					if (a.fromNodeID===cur.fromNodeID && a.toNodeID===cur.toNodeID) {
						alert("This edge already exists!");
						isValidEdge = false;
					}

					//if there is already an edge reversed
					if (a.fromNodeID===cur.toNodeID && a.toNodeID===cur.fromNodeID) {
						alert("You cannot make an edge referring to the same node as from.");
						isValidEdge = false;
					}
				}
				//meets all the requirements
				if(isValidEdge===true) {
					edges.push(a);
					//checkForInput(a.toNodeID);
					reviseInAndOutputs();
					redraw();
					d3.select("#submitGraphButton").attr("class","btn btn-danger");
				}
			}
		}
	}
}

function redraw(){
	if (typeof graphObj !== 'undefined') {
		graphObj.redraw();
	}
}