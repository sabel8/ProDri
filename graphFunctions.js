var pathes=new Array();
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
				localPathList.splice(localPathList.length-1,1);
			}
		}
	}
	
	visited[startID]=false;
}

//helper function for critical path calculation
function calc() {

	var startingNodeID = d3.select("#startNodeID").node().value;
	var finishNodeID = d3.select("#finishNodeID").node().value;

	if (getNodeByID(startingNodeID)==null || getNodeByID(finishNodeID)==null) {
		alert("The selected start or finish node does not exist.");
		return;
	} else if(startingNodeID==finishNodeID) {
		alert("The starting node cannot be the end node too.");
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
			alert("The critical path: "+pathes[maxDurIndex]+" (ID of the nodes)"+
				"\nIt's duration is : "+maxDuration);
		}
	}
}

function deleteSelected() {
	//hiding the status selector
	d3.select("#statusSelect").style("display","block");
	if (selectedEdge==null && selectedNode==null) {
		alert("Nothing is selected.");
	}
	//DELETE EDGE
	else if (selectedEdge!=null) {
		let response = confirm("Do you really want to delete the selected edge?");
		if (response === true) {
			deleteEdge(selectedEdge);
			selectedEdge=null;
			redraw();
			reviseInAndOutputs();
		}
	}
	//DELETE NODE
	else if (selectedNode!=null) {
		let response = confirm("Do you really want to delete the selected"
			+"\nnode and all the edges connected to it?");
		if (response === true) {
			//deleting all edges related to the node
			for (var i = 0;i<edges.length;i++) {
				var curEdge=edges[i];
				//if it derives from or points at the node
				if (curEdge.toNodeID===selectedNode.ID || curEdge.fromNodeID===selectedNode.ID) {
					deleteEdge(curEdge);
					//deletion makes length decreased by one
					i--;
				}
			}
			//deleting the node itself
			let pos = getPosInArray(selectedNode.ID,nodes);
			nodes.splice(pos,1);
			selectedNode=null;
			redraw();
			reviseInAndOutputs();
		}
	}
}


function selectStatus() {
	var chosen = d3.select("#statusSelect").node().value;
	var curNode = getNodeByID(selectedNode.ID);
	switch(chosen) {
		case "notStartedOption":
			curNode.status = 0;
			break;
		case "inProgressOption":
			if (curNode.input==1) {
				curNode.status = 1;
			} else {
				alert("You cannot change this node's status "+
					"because not all predecessors are done.");
				d3.select("#statusSelect").node().value = "notStartedOption";
			}
			break;
		case "doneOption":
			if (curNode.input==1) {
				curNode.status = 2;
			} else {
				alert("You cannot change this node's status "+
					"because not all predecessors are done.")
				d3.select("#statusSelect").node().value = "notStartedOption";
			}
			break;
	}
	reviseInAndOutputs();
	let infoSplitted = curNode.toString().replace(new RegExp("; ", 'g'), "<br>");
	d3.select("#objectInfo").html(infoSplitted);
	redraw();
}

//helper function
//do not use directly
function deleteEdge(edge){
	let pos = getPosInArray(edge.ID,edges);
	edges.splice(pos,1);
}


//iterates through all nodes and
//defining it's INPUT and OUTPUT value
function reviseInAndOutputs() {
	//sets the output and input values
	//for the predefined data
	for (var i=0;i<nodes.length;i++) {
		var aktNode = nodes[i];
		//sets input
		checkForInput(aktNode.ID);
		//if input is 0
		//then status changes to 0
		if(aktNode.input===0) {
			aktNode.status = 0;
		}
		//sets output
		if (aktNode.status == 2) {
			aktNode.output = 1;
		} else {
			aktNode.output = 0;
		}
	}
}

//iterate through the edges and if
//all predecessors are done then
//sets the INPUT of the node to 1
function checkForInput(nodeID) {
	var allPreviousDone = true;
	for (var i=0;i<edges.length;i++) {
		var aktEdge = edges[i];
		if (aktEdge.toNodeID === nodeID) {
			if (getNodeByID(aktEdge.fromNodeID).status != 2) {
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