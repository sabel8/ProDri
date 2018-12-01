var openedCreator=false;
var graphObj;
var recomID,processID,title;

function createRec(processGroupID,personID,reload) {
	//getting the title of the recommendation
	var person = prompt("Title of the recommendtaion", "Place Holder");
	if(person==null) {
		return -1;
	} else if(person=="") {
		alert("You have to give a proper name!");
		return -1;
	}

	//creating and getting the recommendation ID
	var newProcessID;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			newProcessID = this.responseText;
		}
	};
	xmlhttp.send("q=newProcess&p="+processGroupID+"&from="+personID+"&title="+person);
	console.log("newProcessID="+newProcessID);

	//appending the nodes and the edges to the related recommendation
	updateElementsOfRec(newProcessID);

	if (reload==true){
		location.reload(true);
	}
	return newProcessID;
}

//sets the current nodes and edges array
//to the recommendation is param
function updateElementsOfRec(absProcessID) {
	console.log(runInsert(["absProcNodeDel",absProcessID]));
	console.log(runInsert(["absProcEdgeDel",absProcessID]));

	for (var i = 0; i < graphObj.nodes.length; i++) {
		var c = graphObj.nodes[i];
		//nodeID,name,xCord,yCord,professionID,raci,abstractProcessID,description
		console.log(runInsert(["recNodes",c.ID,c.txt,c.x,c.y,c.knowledgeArea,c.RACI,absProcessID,c.desc]));
	}

	for (var i = 0; i < graphObj.edges.length; i++) {
		var c = graphObj.edges[i];
		console.log(runInsert(["recEdges",c.fromNodeID,c.toNodeID,absProcessID]));
	}
}

function withdraw(recomID){
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
		}
	};
	xmlhttp.send("q=withdraw&p="+recomID);
}

function viewRecommendation(recID,recNodes,recEdges,tableID) {
	closeGraph();
	var realNodes=[];
	for (var i = 0; i < recNodes.length; i++) {
		var c = recNodes[i];
		//query      ID, name,x,y,professionID,n.raci,n.description,p.processGroupID
		//nodes cons(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID, desc)
		realNodes[i] = new Node(Number(c[0]),c[1],Number(c[2]),Number(c[3]),null,Number(c[4]),null,null,c[5],Number(c[7]),c[6]);
	}

	var realEdges=[];
	for (var i = 0; i < recEdges.length; i++) {
		var c = recEdges[i];
		realEdges[i]=new Edge(Number(c[0]),Number(c[1]),Number(c[2]));
	}
	graphObj = new Graph(realNodes,realEdges,false,"newNodeModalTrigger","objectInfoModalTrigger",true,true);
	var closeButton=document.createElement("button");
	d3.select(closeButton)
		.attr("id","closeSVGButton")
		.attr("style","float:right;margin-bottom:20px")
		.on("click",function(){
			closeGraph();
		})
		.attr("class","btn btn-danger")
		.html('<span class="glyphicon glyphicon-remove"></span>');
	var parent = d3.select("#"+tableID).node().parentNode;
	insertAfter(closeButton , parent);
	insertAfter(graphObj.getSVGElement(parent.offsetWidth,400) , d3.select("#closeSVGButton").node());
	redraw();
}

function closeGraph(){
	d3.select("#saveRecButton").attr("style","display:none");
	d3.select("#closeSVGButton").remove();
	d3.select("svg").remove();
}

function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function changeRecommendationStatus(recomAbsProcID,status) {
	//refuse button deletes row
	if(status==3){
		removeRecommendation(recomAbsProcID);
		return;
	} else if (status==1){
		if (recomAbsProcID==""){
			alert("It should have a proper name!");
			return;
		}
	}

	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
			switch(status) {
				//SUBMIT BUTTON
				case 1:
					console.log(runInsert(["log",16,recomAbsProcID]));
					alert("You successfully submitted your recommendation. Thank you!");
					break;
				//ACCEPT BUTTON
				case 2:
					//setting up backup withdraw table data
					console.log(runInsert(["log",17,recomAbsProcID]));
					//making the recommendation live
					console.log(runInsert(["makeRecLive",recomAbsProcID]));
					alert("You successfully accepted this recommendation and made it official. Thank you!");
					break;
			}
		}
	};
	xmlhttp.send("q=recomStatusChange&p="+recomAbsProcID+"&to="+status);

	location.reload(true);
}

function removeRecommendation(recomID){
	console.log(runInsert(["log",18,recomID]));
	console.log(runInsert(["absProcNodeDel",recomID]));
	console.log(runInsert(["absProcEdgeDel",recomID]));
	console.log(runInsert(["recDel",recomID]));
	location.reload(true);
}

function addNewRecNode(){
	//x and y values are in mousePos variable
	var x = mousePos[0];
	var y = mousePos[1];
	var taskName = d3.select("#nodeTitle").node().value;
	var raci = document.querySelector('input[name="nodeRaci"]:checked').value;
	//constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID){
	//copying the processID from the first node
	nodes.push(new Node(getValidID(nodes),taskName,x,y,0,"","","","",graphObj.nodes[0].processID));
	shiftKeyPressed=false;
	redraw();

}

function createRecommendation2(nodesParam,edgesParam,processID){
	//setting up the global node array
	nodes = [];
	//nodeParam(nodeID,txt,x,y,raci,processID,desc)
	//constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID,desc){
	for(var i=0;i<nodesParam.length;i++){
		var cur=nodesParam[i];
		nodes[i]=new Node(Number(cur[0]),cur[1],Number(cur[2]),Number(cur[3]),0,Number(cur[7]),null,null,cur[4],cur[5],cur[6]);
	}

	//setting up the global edge array
	edges=[];
	for(var i=0;i<edgesParam.length;i++){
		var cur=edgesParam[i];
		edges[i] = new Edge(cur[0],cur[1],cur[2]);
	}

	closeGraph();
	openedCreator=true;
	//constructor(nodes,edges,allowedCreation,newNodeModalTriggerID,objectInfoModalTriggerID,justSpectate,abstract)
	graphObj = new Graph(nodes,edges,true,"newNodeModalTrigger","objectInfoModalTrigger",false,true);
	reviseInAndOutputs();
	d3.select("svg").remove();
	var parent = d3.select("#manageEditorBody").node();
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	d3.select("#saveRecButton").attr("style","display:none");
	d3.select("#submitRecButton").attr("style","display:none");
	d3.select("#createRecButton").attr("style","display:inline-block");
	graphObj.redraw();
}

function viewRec2(nodesParam,edgesParam,recomID,status,allowedCreation,spectateMode){
	//setting up the global node array
	nodes = [];
	//nodeParam(nodeID,txt,x,y,raci,processID,desc)
	//constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID,desc){
	for(var i=0;i<nodesParam.length;i++){
		var cur=nodesParam[i];
		nodes[i]=new Node(Number(cur[0]),cur[1],Number(cur[2]),Number(cur[3]),0,null,null,null,cur[4],cur[5],cur[6]);
	}

	//setting up the global edge array
	edges=[];
	for(var i=0;i<edgesParam.length;i++){
		var cur=edgesParam[i];
		edges[i] = new Edge(cur[0],cur[1],cur[2]);
	}

	
	d3.select("svg").remove();
	var parent = d3.select("#manageEditorBody").node();
	d3.select("#createRecButton").attr("style","display:none");
	if (status==1){
		d3.select("#saveRecButton").attr("style","display:inline-block");
		d3.select("#submitRecButton").attr("style","display:none");
	} else if (status==0) {
		d3.select("#saveRecButton").attr("style","display:inline-block");
		d3.select("#submitRecButton").attr("style","display:inline-block");
	} else {
		d3.select("#saveRecButton").attr("style","display:none");
		d3.select("#submitRecButton").attr("style","display:none");
	}
	//setting up the graph element and the svg
	graphObj = new Graph(nodes,edges,(status>1?false:allowedCreation),"newNodeModalTrigger",
		"objectInfoModalTrigger",(status>1?true:spectateMode),true);
	reviseInAndOutputs();
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	graphObj.redraw();
}