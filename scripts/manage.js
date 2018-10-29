var openedCreator=false;
var nodes,edges;
var graphObj;

function createRecommendation(){
	getNodesAndEdges();
	openedCreator=true;
	graphObj = new Graph(nodes,edges,true,"newNodeModalTrigger","objectInfoModalTrigger",false);
	reviseInAndOutputs();
	d3.select("#manageEditorBody").html("");
	var parent = d3.select("#manageEditorBody").node();
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	var button = d3.select("#manageEditorBody").append("button")
		.attr("type","button")
		.attr("class","btn btn-primary")
		.on("click",function(){submitGraph(1,4/*to be changed from static*/)})
		.html("<span class='glyphicon glyphicon-ok'></span> Create recommendation");
	graphObj.redraw();
}

function submitGraph(processID,personID) {
	for (var i = 0; i < graphObj.nodes.length; i++) {
		var c = nodes[i];
		//name,xCord,yCord,status,professionID,raci,duration,deliverableID,recommendationID
		console.log(runInsert(["recNodes",c.ID,c.txt,c.x,c.y,c.status,c.knowledgeArea,c.RACI,c.duration,c.deliverableID,processID]));
	}

	for (var i = 0; i < graphObj.edges.length; i++) {
		var c = graphObj.edges[i];
		console.log(runInsert(["recEdges",c.fromNodeID,c.toNodeID,processID]));
	}

	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
		}
	};
	xmlhttp.send("q=newRecom&p="+processID+"&from="+personID);

	//location.reload(true);

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

	console.log(realEdges);
	d3.select("#manageEditorBody").html("");
	graphObj = new Graph(realNodes,realEdges,false,"newNodeModalTrigger","objectInfoModalTrigger",true);
	var parent = d3.select("#manageEditorBody").node();
	parent.appendChild(graphObj.getSVGElement(parent.offsetWidth,400));
	redraw();
}

function changeRecommendationStatus(recomID,status) {
	console.log(recomID);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST", "php_functions/setdatas.php", false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
			switch(status) {
				case 1:
					alert("You successfully submitted your recommendation. Thank you!");
					break;
				case 2:
					alert("You successfully accepted this recommendation. Thank you!");
					break;
				case 3:
					alert("You successfully refused this recommendation. Thank you!");
					break;
			}
		}
	};
	xmlhttp.send("q=recomStatusChange&p="+recomID+"&to="+status);

	location.reload(true);
}