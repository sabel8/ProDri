var openedCreator=false;
var graphObj;

function createRecommendation(){
	getNodesAndEdges(1);//todo --> dinamic
	openedCreator=true;
	graphObj = new Graph(nodes,edges,true,"newNodeModalTrigger","objectInfoModalTrigger",false);
	reviseInAndOutputs();
	d3.select("#manageEditorBody").html("");
	var parent = d3.select("#manageEditorBody").node();
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	var button = d3.select("#manageEditorBody").append("button")
		.attr("type","button")
		.attr("class","btn btn-primary")
		.on("click",function(){submitGraph(1,4/*to be changed from static*/);})
		.html("<span class='glyphicon glyphicon-ok'></span> Create recommendation");
	graphObj.redraw();
}

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

function viewRecommendation(recID,recNodes,recEdges) {
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
	d3.select("#manageEditorBody").html("");
	graphObj = new Graph(realNodes,realEdges,false,"newNodeModalTrigger","objectInfoModalTrigger",true);
	d3.select("#manageEditorBody").append("button")
		.attr("id","closeSVGButton")
		.attr("style","float:right;margin-bottom:20px")
		.on("click",function(){
			d3.select("#closeSVGButton").remove();
			d3.select("svg").remove();
		})
		.attr("class","btn btn-danger")
		.html('<span class="glyphicon glyphicon-remove"></span>');
	var parent = d3.select("#manageEditorBody").node();
	parent.appendChild(document.createElement('br'));
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	redraw();
}

function changeRecommendationStatus(recomID,status) {
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
			switch(status) {
				case 1:
					//alert("You successfully submitted your recommendation. Thank you!");
					break;
				//ACCEPT BUTTON
				case 2:
					//setting up backup withdraw table data
					console.log(runInsert(["wipeWithdrawNodes",recomID]));
					console.log(runInsert(["wipeWithdrawEdges",recomID]));
					console.log(runInsert(["copyNodesToWithdraw",recomID]));
					console.log(runInsert(["copyEdgesToWithdraw",recomID]));

					//making the recommendation live
					console.log(runInsert(["nodeProcDel",recomID]));
					console.log(runInsert(["nodeRecToLive",recomID]));
					console.log(runInsert(["edgeProcDel",recomID]));
					console.log(runInsert(["edgeRecToLive",recomID]));
					console.log(runInsert(["allRecNotLive"]));
					console.log(runInsert(["makeRecLive",recomID]));
					alert("You successfully accepted this recommendation and made it official. Thank you!");
					break;
				//REFUSE BUTTON	
				case 3:
					alert("You successfully refused this recommendation. Thank you!");
					break;
			}
		}
	};
	xmlhttp.send("q=recomStatusChange&p="+recomID+"&to="+status);

	location.reload(true);
}

function removeRecommendation(recomID){
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
