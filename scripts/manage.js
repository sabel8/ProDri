var openedCreator=false;
var graphObj;
var recomID;

function submitGraph(processID,personID) {
	var recomID;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			recomID = this.responseText;
		}
	};
	xmlhttp.send("q=newRecom&p="+processID+"&from="+personID);
	console.log("recomID="+recomID);

	for (var i = 0; i < graphObj.nodes.length; i++) {
		var c = nodes[i];
		//name,xCord,yCord,status,professionID,raci,duration,deliverableID,recommendationID
		console.log(runInsert(["recNodes",c.ID,c.txt,c.x,c.y,c.status,c.knowledgeArea,c.RACI,c.duration,c.deliverableID,recomID]));
	}

	for (var i = 0; i < graphObj.edges.length; i++) {
		var c = graphObj.edges[i];
		console.log(runInsert(["recEdges",c.fromNodeID,c.toNodeID,recomID]));
	}
	location.reload(true);
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
	//nodes cons(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID)
	var realNodes=[];
	for (var i = 0; i < recNodes.length; i++) {
		var c = recNodes[i];
		realNodes[i] = new Node(Number(c[0]),c[1],Number(c[2]),Number(c[3]),Number(c[4]),Number(c[5])," ",Number(c[7]),c[8],Number(c[9]));
	}

	var realEdges=[];
	for (var i = 0; i < recEdges.length; i++) {
		var c = recEdges[i];
		realEdges[i]=new Edge(Number(c[0]),Number(c[1]),Number(c[2]));
	}
	graphObj = new Graph(realNodes,realEdges,false,"newNodeModalTrigger","objectInfoModalTrigger",true);
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
	d3.select("#createRecButton").attr("style","display:none");
	d3.select("#closeSVGButton").remove();
	d3.select("svg").remove();
}

function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function changeRecommendationStatus(recomID,status) {
	//refuse button deletes row
	if(status==3){
		removeRecommendation(recomID);
		return;
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
					console.log(runInsert(["log",16,recomID]));
					//alert("You successfully submitted your recommendation. Thank you!");
					break;
				//ACCEPT BUTTON
				case 2:
					//setting up backup withdraw table data
					console.log(runInsert(["wipeWithdrawNodes",recomID]));
					console.log(runInsert(["wipeWithdrawEdges",recomID]));
					console.log(runInsert(["copyNodesToWithdraw",recomID]));
					console.log(runInsert(["copyEdgesToWithdraw",recomID]));
					console.log(runInsert(["log",17,recomID]));

					//making the recommendation live
					console.log(runInsert(["nodeProcDel",recomID]));
					console.log(runInsert(["nodeRecToLive",recomID]));
					console.log(runInsert(["edgeProcDel",recomID]));
					console.log(runInsert(["edgeRecToLive",recomID]));
					console.log(runInsert(["allRecNotLive"]));
					console.log(runInsert(["makeRecLive",recomID]));
					alert("You successfully accepted this recommendation and made it official. Thank you!");
					break;
			}
		}
	};
	xmlhttp.send("q=recomStatusChange&p="+recomID+"&to="+status);

	location.reload(true);
}

function removeRecommendation(recomID){
	console.log(runInsert(["log",18,recomID]));
	console.log(runInsert(["recNodeDel",recomID]));
	console.log(runInsert(["recEdgeDel",recomID]));
	console.log(runInsert(["recDel",recomID]));
	location.reload(true);
}

function addNewRecNode(procID){
	//x and y values are in mousePos variable
	var x = mousePos[0];
	var y = mousePos[1];
	var taskName = d3.select("#nodeTitle").node().value;
	var raci = document.querySelector('input[name="nodeRaci"]:checked').value;
	//constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID){
	nodes.push(new Node(getValidID(nodes),taskName,x,y,0,"","","","",procID));
	redraw();
}

function createRecommendation2(nodesParam,edgesParam,processID){
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


	closeGraph();
	openedCreator=true;
	//constructor(nodes,edges,allowedCreation,newNodeModalTriggerID,objectInfoModalTriggerID,justSpectate)
	graphObj = new Graph(nodes,edges,true,"newNodeModalTrigger","objectInfoModalTrigger",false);
	reviseInAndOutputs();
	d3.select("svg").remove();
	var parent = d3.select("#manageEditorBody").node();
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	d3.select("#createRecButton").attr("style","display:inline-block");
	d3.select("#submitRecButton").attr("style","display:none");
	graphObj.redraw();
}

function viewRec2(nodesParam,edgesParam,recomID,status,allowedCreation,spectateMode,tableID){
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

	//setting up the graph element and the svg
	graphObj = new Graph(nodes,edges,allowedCreation,"newNodeModalTrigger","objectInfoModalTrigger",spectateMode);
	reviseInAndOutputs();
	d3.select("svg").remove();
	console.log(tableID);
	var parent = d3.select("#manageEditorBody").node();
	d3.select("#createRecButton").attr("style","display:none");
	if (status==0){
		d3.select("#submitRecButton").attr("style","display:inline-block");
	}
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	
	graphObj.redraw();
}