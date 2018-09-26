//NODE CLASS
//constructor of the Node element
class Node {
	constructor(ID, txt, x, y, status){
		this.ID = ID;
		this.txt = txt;
		this.x = x;
		this.y = y;
		this.output = 0;
		this.input = 0;
		// 0 : not yet started
		// 1 : in progress
		// 2 : done
		if (status==null){
			this.status = 0;
		} else {
			this.status = status;
		}

		this.toString = function(){
			return "text: "+this.txt+" x: "+this.x+" y: "+y
			+" status: "+this.status+" output: "
			+this.output+" input: "+this.input;
		}
	}

	getColor(){
		let color; 
		switch(this.status) {
			case 0:
			color = "#ff3333";
			break;
			case 1:
			color = "lightblue";
			break;
			case 2:
			color = "#33cc33";
		}
		return color;
	}

	getStatus(){
		let text; 
		switch(this.status) {
			case 0:
			text = "Not yet started";
			break;
			case 1:
			text = "In progress";
			break;
			case 2:
			text = "Done";
		}
		return text;
	}
}

//seperates the text into words for readability
function insertText(gEl, title) {
	//returns if no valid title passed
	if(title==null){
		return;
	}

	//count the words
	var words = title.split(/\s+/g),
	nwords = words.length;

	//initialize the text element
	var el = gEl.append("text")
	.attr("text-anchor","middle")
	.attr("dy", "-" + (nwords-1)*7.5);

	//add span elements to the text
	for (var i = 0; i < words.length; i++) {
		var tspan = el.append('tspan').text(words[i]);
		if (i > 0)
			tspan.attr('x', 0).attr('dy', '15');
	}
};

//iterate through the edges and if
//all predecessors are done then
//sets the INPUT of the node to 1
function checkForInput(nodeID) {
	var allPreviousDone = true;
	for (var i=0;i<edges.length;i++) {
		var aktEdge = edges[i];
		if (aktEdge.toNodeID === nodeID) {
			if (nodes[aktEdge.fromNodeID].status != 2) {
				allPreviousDone=false;
			}
		} 
	}
	if (allPreviousDone === true) {
		nodes[nodeID].input = 1;
	}
}

//EDGE CLASS
class Edge {
	constructor(ID,fromNodeID,toNodeID) {
		this.ID = ID;
		this.fromNodeID = fromNodeID;
		this.toNodeID = toNodeID;
		this.toString = function(){
			return "ID: "+this.ID+"; fromNodeID: "+this.fromNodeID
			+"; toNodeID: "+toNodeID+";";
		}
	}

}


//defining the arrays which holds the data
//of the nodes and the edges
var nodes = new Array(),
edges = new Array();

//SAPMLE VALUES FOR REPRESENTATION
//AND TESTING PURPOSES
nodes.push(new Node(0,"példa 0",300,200,0));
nodes.push(new Node(1,"példa 1",100,300,2));
nodes.push(new Node(2,"példa 2",500,200,0));
nodes.push(new Node(3,"példa 3",400,350,0));
edges.push(new Edge(0,1,0));
edges.push(new Edge(1,0,2));
edges.push(new Edge(2,1,3));
edges.push(new Edge(3,3,2));

//dimensions for the main svg element
var width = window.innerWidth*0.8,
height = 500;

//defining variables for edge drawing
//mousePos: start of dragging in coordinates
//fromNode: the node where the dragging started
var toNode, fromNode, mousePos, shiftKeyPressed=false;

//desing values for svg elements
//speaks for themselves...
var diffFromCursor = 0.98,
circleRadius = 40,
edgeWidth = 3;

//the whole functionality starts after the page has loaded
window.onload = function() {

	

	//testing :D
	d3.select("body").append("h1").text("Üdv!");

	d3.select("body").append("input")
	.attr("type","submit")
	.attr("onclick","downloadButton()")
	.attr("value","Download");
	d3.select("body").append("input")
	.attr("type","file")
	.attr("accept",".json")
	.attr("onchange","upload(event)")
	.attr("id","hidden-file-upload");
	d3.select("body").append("button")
	.attr("onclick","uploadButton()")
	.text("Upload");
	d3.select("body").append("br");

	//adding the main svg element to the Body
	//zoom functionality linked here to zoom function
	var svg = d3.select("body").append("svg")
	.attr("width", width)
	.attr("height", height)
	.on("click", createNode)
	.on("mouseup", function(){shiftKeyPressed = false})
	.call(d3.zoom()
		    //.scaleExtent([1, 8]) //this modifies the maximum rate of the zoom
		    .on("zoom",zoom));

    //defining the arrow which later will serve
    //as the marker-end for the edge-pathes
    svg.append("defs").append("marker")
    .attr("id","arrowhead")
    .attr("viewBox","0 0 10 10")
    .attr("refX",circleRadius-10).attr("refY",5)
    .attr("markerWidth",6).attr("markerHeight",6)
    .attr("orient","auto")
    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

    svg.append("defs").append("marker")
    .attr("id","arrowheadTemp")
    .attr("viewBox","0 0 10 10")
    .attr("refX",5).attr("refY",5)
    .attr("markerWidth",6).attr("markerHeight",6)
    .attr("orient","auto")
    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

	//this g element holds every node and edge
	var g = svg.append("g")
	.attr("id","graph")
	.style("cursor","move");

	//zoom functionality
	function zoom() {
		d3.select("#graph").attr("transform", d3.event.transform);
	}
	redraw();

	//sets the output and input values
	//for the predefined data
	for (var i=0;i<nodes.length;i++) {
		var aktNode = nodes[i];
		checkForInput(aktNode.ID);
		if (aktNode.status == 2) {
			aktNode.output = 1;
		}
	}
};//end of window.onload


//drag start, (creates if necessary and)
//draws a temporary edge
function dragstarted(d) {
	if (shiftKeyPressed===true) {
		fromNode=d;
		if (document.getElementById('new')===null)
			d3.select("#graph").append("path")
		.attr("id","new")
		.attr("stroke","black")
		.attr("stroke-width",edgeWidth)
		.attr("marker-end","url(#arrowheadTemp)");
	}
}

//dragging, redraws the temporary edge
//calculates
function dragged(d){
	if (shiftKeyPressed===true) {
		let e = d3.event;
		let mouseX = e.x+mousePos[0];
		let mouseY = e.y+mousePos[1];
		let diffX=e.x-d.x;
		let diffY=e.y-d.y;
		let moveEdgeX=(mouseX-d.x)*diffFromCursor;
		let moveEdgeY=(mouseY-d.y)*diffFromCursor;
		d3.select("#new")
		.attr("d","M"+d.x+","+d.y+"l"+moveEdgeX+","+moveEdgeY);
	} else {
		var draggedNode = nodes[d.ID];
		draggedNode.x += d3.event.dx;
		draggedNode.y += d3.event.dy;
		redraw();
	}
}

//drag end
function dragend(d) {
	createEdge();
	shiftKeyPressed=false;
}

//this function triggers nodes
//according to their status
//this is only for dynamic graph usage
function verifyStatus() {
	for (var i = 0;i<nodes.length;i++) {
		var aktNode = nodes[i];
		checkForInput(aktNode.ID);
	}
}

//creates a new edge and pushes it to the array
//if it passes validation
function createEdge() {
	if(shiftKeyPressed===true){

		//if mouse is released without
		//hovering over any edge
		if (toNode!=null) {

			//if the user drags the line to the same node
			//it aborts the creation process of the edge
			if (toNode.ID!=fromNode.ID){
				var a = new Edge(edges.length,fromNode.ID,toNode.ID);

				let isValidEdge = true;

				//iterating through the edge array
				//for validating the edge
				for (var i = 0;i<edges.length;i++) {
					let akt = edges[i];

					//if there is already an edge like this
					if (a.fromNodeID===akt.fromNodeID && a.toNodeID===akt.toNodeID) {
						alert("This edge already exists!");
						isValidEdge = false;
					}

					//if there is already an edge reversed
					if (a.fromNodeID===akt.toNodeID && a.toNodeID===akt.fromNodeID) {
						alert("You cannot make an edge referring to the same node as from.");
						isValidEdge = false;
					}
				}
				//meets all the requirements
				if(isValidEdge===true) {
					edges.push(a);
					checkForInput(a.toNodeID);
				}
			}
		}
		redraw();
	}
}

//creates nodes on shift+click
function createNode(){
	if (d3.event.shiftKey) {
		let event = d3.mouse(d3.select("#graph").node());
		let txt = prompt("Please enter the title of the task", "Példa egy");
		if (txt===null){return;}
		nodes.push(new Node(nodes.length,txt,event[0],event[1]));
	}
	redraw();
}

//this function is called if there is any 
//DATA RELATED interaction on the svg
//so, zoom does not call this
function redraw(){
	//clearing pathes to be able to redraw them
	d3.selectAll("#edge").remove();
	//clearing nodes to be able to redraw them
	d3.select("#graph").selectAll("g").remove();

	//iterating through the edge array and creating
	//the corresponding SVG PATH elements
	for(let i=0;i<edges.length;i++){
		d3.select("#graph").append("path")
		.attr("id","edge")
		.attr("d",function() {
			let currentEdge = edges[i];
			let from = nodes[currentEdge.fromNodeID];
			let to = nodes[currentEdge.toNodeID];
			let dir = "M"+from.x+","+from.y+"L"+to.x+","+to.y;
			return dir;
		})
		.attr("stroke","black")
		.attr("stroke-width",edgeWidth)
		.attr("marker-end","url(#arrowhead)");
	}

	//drawing nodes with d3 from the array
	//in a separate g element which
	//hold the node's label too
	var g = d3.select("#graph").selectAll("g")
	.data(nodes)
	.enter()
	.append("g")
	.attr("transform", function(d){
		return "translate("+d.x+","+d.y+")"
	})
	.on("mouseenter", function(d){
		toNode = d;
		d3.select(this).classed("hover",true);
	})
	.on("click",function(d,i){
			//changing status on ctrl+click
			if (d3.event.ctrlKey) {
				var aktNode = nodes[i];
				if(aktNode.input === 0){
					alert("Cannot change this node's status.");
					return;
				}
				if (aktNode.status!=2) {
					aktNode.status++;
					if (aktNode.status == 2) {
						aktNode.output = 1;
					}
				}
				verifyStatus();
			}
			//simple click alerts info about the node
			else
				alert("Here will be the info of the node/task."
					+"\nThis node has the text \""+d.txt+"\" on it."
					+"\nIts ID is "+d.ID+"."
					+"\nIts status is "+d.getStatus()
					+"\n"+d);

		})
		//reverts to original color
		.on("mouseleave", function(){
			toNode = null;
			d3.select(this).attr("class",null);
		})
		//checks if shift is pressed (for dragging)
		.on("mousedown", function(d){
			if (d3.event.shiftKey) {
				shiftKeyPressed = true;
			}
			mousePos = d3.mouse(this);
		})
		//assingning the functions to dragging
		.call(d3.drag()
			.on("start", dragstarted)
			.on("drag", dragged)
			.on("end", dragend));

	//adding nodes from array
	g.append("circle")
	.attr("r", circleRadius)
	.attr("fill",function(d,i){let a = new Node(d);return d.getColor()})
	.attr("stroke","black");


	//adds the corresponding label to each node
	g.each(function(d) {
		insertText(d3.select(this), d.txt)
	});

	//removes the dragged uncomplete edge
	d3.selectAll("#new").remove();
}

//creates JSON file
function downloadButton() {
	var str = [window.JSON.stringify({"nodes" :nodes, "edges" :edges})];
	download("myGraph.json",str);
}


//helper function for downloading
function download(filename, text) {
	var element = document.createElement('a');
	element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
	element.setAttribute('download', filename);
	element.style.display = 'none';
	document.body.appendChild(element);
	element.click();
	document.body.removeChild(element);
}


function uploadButton() {
	//clicks the invisible upload input button
	//then fires the upload(event) function
	document.getElementById("hidden-file-upload").click();
}


//helper function for uploading
function upload(event) {
	if (File && FileReader && FileList) {
		var uploadFile = event.target.files[0];

		var filereader = new FileReader();
		filereader.onload = function(){

        	//backup data if parsing is unsuccessful
        	let backupNodes = nodes
        	backupEdges = edges;

        	try {
				//getting data from the file
				//and pushing it to the arrays
				var jsonObj = JSON.parse(filereader.result);
				var nodeArr = jsonObj.nodes;
				edges = new Array();
				nodes = new Array();
				for(var i=0;i<nodeArr.length;i++) {
					let d = nodeArr[i];
					let a = new Node(d.ID,d.txt,d.x,d.y,d.status);
					nodes.push(a);
				}
				var edgeArr = jsonObj.edges;
				for(var i=0;i<edgeArr.length;i++) {
					let d = edgeArr[i];
					let a = new Edge(d.ID,d.fromNodeID,d.toNodeID);
					edges.push(a);
				}
				redraw();
			} catch (err) {
				window.alert("Error parsing uploaded file\n" + err.message);
				edges = backupEdges;
				nodes = backupNodes;
				return;
			}
		};
		filereader.readAsText(uploadFile);

	} else {
		alert("Your browser won't let you save this graph -- try upgrading your browser to IE 10+ or Chrome or Firefox.");
	}
	redraw();
}