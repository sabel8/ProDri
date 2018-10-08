var curProcess="Sample Project";

//defining the arrays which holds the data
//of the nodes and the edges
var nodes = new Array(),
edges = new Array();

//SAPMLE VALUES FOR REPRESENTATION
//AND TESTING PURPOSES
nodes.push(new Node(0,"példa 0",300,200,1,"artist","John Jonas",5,"r",curProcess));
nodes.push(new Node(1,"példa 1",100,300,2,"artist","John Jonas",5,"r",curProcess));
nodes.push(new Node(2,"példa 2",600,100,0,"artist","John Jonas",5,"r",curProcess));
nodes.push(new Node(3,"példa 3",500,350,0,"artist","John Jonas",3,"r",curProcess));
nodes.push(new Node(4,"START",100,100,null,null,null,null,null,curProcess));
nodes.push(new Node(5,"FINISH",850,300,null,null,null,null,null,curProcess));
edges.push(new Edge(0,1,0));
edges.push(new Edge(1,0,2));
edges.push(new Edge(2,1,3));
edges.push(new Edge(3,3,2));
edges.push(new Edge(4,4,1));
edges.push(new Edge(5,2,5));

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
edgeWidth = 3,
selectedNodeColor = "gold",
rectRoundness = 20,
rectWidth = 150,
rectHeight = 75;

//variable for deleting edge and node
var selectedEdge, selectedNode;

//defines the authority
//this disables most functions
var authority,authRadios;



//the whole functionality starts after the page has loaded
window.onload = function() {

	//gets the authority radio buttons
	authRadios = document.querySelectorAll('input[type=radio][name="authority"]');

	//changes authority if it's radio changes
	Array.prototype.forEach.call(authRadios, function(radio) {
		radio.addEventListener('change', function () {authority = this.value;});
	});

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
	d3.select("body").append("button")
	.attr("onclick","deleteSelected()")
	.text("Delete");
	d3.select("body").append("button")
	.attr("onclick","calc()")
	.text("Critical path");

	d3.select("body").append("br");

	//adding the main svg element to the Body
	//zoom functionality linked here to zoom function
	var svg = d3.select("body").append("svg")
	.attr("width", width)
	.attr("height", height)
	.on("click", function() {
		mousePos = d3.mouse(d3.select("#graph").node());
		if (shiftKeyPressed===true) {
			if (authority==="u") {
				alert("Unfortunately, you cannot make new nodes...");
			} else {
				//opening the query modal
				d3.select("#newNodeModalTrigger").node().click();
			}		
		}
		shiftKeyPressed=false;	
	})
	.on("mousedown", function(d){
		if (d3.event.shiftKey) {
			shiftKeyPressed = true;
		}
	})
	.on("mouseup", function(){shiftKeyPressed = false})
	.call(d3.zoom()
		    //.scaleExtent([1, 8]) //this modifies the maximum rate of the zoom
		    .on("zoom",zoom));

    //defining the arrow which later will serve
    //as the marker-end for the edge-pathes

    //default marker
    svg.append("defs").append("marker")
    .attr("id","arrowhead")
    .attr("viewBox","0 0 10 10")
    .attr("refX",8)
    .attr("refY",5)
    .attr("markerWidth",6).attr("markerHeight",6)
    .attr("orient","auto")
    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

    //hovering edge marker
    svg.append("defs").append("marker")
    .attr("id","arrowheadHover")
    .attr("viewBox","0 0 10 10")
    .attr("refX",8).attr("refY",5)
    .attr("markerWidth",6).attr("markerHeight",6)
    .attr("orient","auto")
    .attr("fill","yellow")
    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

    //selected edge marker
    svg.append("defs").append("marker")
    .attr("id","arrowheadSelected")
    .attr("viewBox","0 0 10 10")
    .attr("refX",8).attr("refY",5)
    .attr("markerWidth",6).attr("markerHeight",6)
    .attr("orient","auto")
    .attr("fill","blue")
    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

    //temporarly edge marker
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
	reviseInAndOutputs();
	d3.select("svg").node().focus();
	
};//end of window.onload

//drag start, (creates if necessary and)
//draws a temporary edge
function dragstarted(d) {
	//console.log("dragging started")
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
		var draggedNode = getNodeByID(d.ID);
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


//this function is called if there is any 
//DATA RELATED interaction on the svg
//so, zoom does not call this
function redraw(){
	//clearing pathes to be able to redraw them
	d3.selectAll("#edge").remove();
	//clearing nodes to be able to redraw them
	d3.select("#graph").selectAll("g").remove();

	//iterating through the edge array and creating
	//the corresponding SVG PATH elements (=arrows)
	for(let i=0;i<edges.length;i++){
		d3.select("#graph").append("path")
		.attr("id","edge")
		.attr("d",getPath(edges[i])/*function() {
			let currentEdge = edges[i];
			let from = getNodeByID(currentEdge.fromNodeID);
			let to = getNodeByID(currentEdge.toNodeID);
			let dir = "M"+from.x+","+from.y+"L"+to.x+","+to.y;
			return dir;}*/)
		.attr("stroke",function(){
			if (selectedEdge===edges[i])
				return "blue";
			else
				return "black";
		})
		.attr("stroke-width",edgeWidth)
		.attr("marker-end",function(){
			if (selectedEdge===edges[i])
				return "url(#arrowheadSelected)";
			else
				return "url(#arrowhead)";
		})
		.on("mouseenter", function(){
			d3.select(this).classed("hoverEdge",true)
				.classed("selectedEdge",false)
				.attr("marker-end","url(#arrowheadHover)")
		})
		.on("mouseleave", function(){
			let d = edges[i];
			if (selectedEdge===d) {
				d3.select(this).classed("selectedEdge",true)
					.attr("marker-end","url(#arrowheadSelected)");
			} else {
			d3.select(this).classed("hoverEdge",false)
				.attr("marker-end","url(#arrowhead)")
			}
		})
		//sets the selectedEdge variable and shows
		//info modal if not selected
		.on("click",function(){
			let d = edges[i]
			if (selectedEdge===d) {
				d3.select(this).classed("hoverEdge",false)
					.attr("marker-end","url(#arrowhead)");
				selectedEdge=null;
			} else {
				d3.select(this).classed("selectedEdge",true)
					.attr("marker-end","url(#arrowheadSelected)");
				selectedNode = null;
				selectedEdge = d;
				//display the edge info modal on click
				d3.select("#objectName").text("Edge");
				let infoSplitted = d.toString().replace(new RegExp("; ", 'g'), "<br>");
				d3.select("#objectInfo").html(infoSplitted)
				d3.select("#objectInfoModalTrigger").node().click();
			}
			redraw();
		});
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
				selectedNode=null;
				var curNode = nodes[i];
				if(curNode.input === 0){
					alert("Cannot change this node's status.");
					return;
				}
				if (curNode.status!=2) {
					curNode.status++;
					if (curNode.status == 2) {
						curNode.output = 1;
					}
				}
				reviseInAndOutputs();
				redraw();
			}
			//simple click alerts info about the node
			else {
				if (selectedNode!=d) {
					selectedEdge=null;
					selectedNode=d;
					d3.select("#nodeNum"+d.ID).attr("fill","gold");
					d3.select("#objectName").text("Node: "+d.txt);
					let infoSplitted = d.getData().replace(new RegExp("; ", 'g'), "<br>");
					d3.select("#objectInfo").html(infoSplitted);
					d3.select("#objectInfoModalTrigger").node().click();
					if (d.txt!="START"&&d.txt!="FINISH") {
						d3.select("#statusSelect").style("display","block");
					}
					let statSelect = d3.select("#statusSelect").node();
					switch(d.status) {
						case 0:
							statSelect.value = "notStartedOption";
							break;
						case 1:
							statSelect.value = "inProgressOption";
							break;
						case 2:
							statSelect.value = "doneOption";
							break;
					}

				} else {
					d3.select("#nodeNum"+d.ID).attr("fill",function (d) {
						return d.getColor();
					})
					selectedNode=null;					
				}
				redraw();
				}
			})
		//reverts to original color
		.on("mouseleave", function(d){
			toNode = null;
			d3.select(this).attr("class",null);
			if (selectedNode===d) {
				d3.select("#nodeNum"+d.ID).attr("fill",selectedNodeColor);
			}
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

	//data-toggle="modal" data-target="#myModal"

	//adding nodes from array
	g.append("rect")
	.attr("id",function(d){return "nodeNum"+d.ID})
	.attr("x", rectWidth/-2)
	.attr("y", rectHeight/-2)
	.attr("width", rectWidth)
	.attr("height", rectHeight)
	.attr("rx", rectRoundness)
	.attr("ry", rectRoundness)
	.attr("fill",function(d,i){
		if (selectedNode===d)
			return selectedNodeColor;
		else
			return d.getColor()
	})
	.attr("stroke","black");


	//adds the corresponding label to each node
	g.each(function(d) {
		insertText(d3.select(this), d.ID, d.txt)
	});

	//removes the dragged uncomplete edge
	d3.selectAll("#new").remove();
}