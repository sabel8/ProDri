//ID of the process
var curProcessID = 1;

//defining variables for edge drawing
//mousePos: start of dragging in coordinates
//fromNode: the node where the dragging started
var toNode, fromNode, mousePos, shiftKeyPressed=false;

//variable for deleting edge and node
var selectedEdge, selectedNode;

var graphObj;
getNodesAndEdges(curProcessID);//TODO --> dynamic

//the whole functionality starts after the page has loaded
window.onload = function() {	
	//dimensions for the main svg element
	height = 400;

	graphObj = new Graph(nodes,edges,true,"newNodeModalTrigger","objectInfoModalTrigger",false);
	d3.select("#mainDiv").node().appendChild(graphObj.getSVGElement("100%",height));
	reviseInAndOutputs();
	redraw();
};//end of window.onload



//helper function to new node creating
//returns the list of people
//with the specified profession
function getPersonOfProfession(professionID){
	//http request
	var array;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
		}
	};
	xmlhttp.open("GET", "getdatas.php?q=getpersons&p="+professionID, false);
	xmlhttp.send();
	return array;
}

function submitGraphHome() {
	if (confirm("Are you sure you want to overwrite this process? This action cannot be undone.")) {
		//deleting all edges of the process
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "php_functions/delete_data.php", false);
		xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				console.log(this.responseText);
			}
		};//Send the proper header information along with the request
		xmlhttp.send("q=deleteObjectsOfProcess&p="+curProcessID);
		console.log(curProcessID);

		var backupN = nodes, backupE=edges;
		for (var i = 0; i < nodes.length; i++) {
			var c = nodes[i]; //current node
			var params = ["nodes",c.ID,c.txt,c.x,c.y,c.status,c.knowledgeArea,c.responsiblePerson,c.duration,c.RACI,c.processID];
			console.log(runInsert(params)); //running the insert
		}
		for (var i = 0; i < edges.length; i++) {
			var c = edges[i]; //current edge
			var params = ["edges",c.fromNodeID,c.toNodeID,curProcessID];
			console.log(runInsert(params));
		}
		location.reload(true);
	}
}