//NODE CLASS
//constructor of the Node element
class Node {
	constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID){
		this.ID = ID;
		this.txt = txt;
		this.x = x;
		this.y = y;
		this.processID = processID;
		if (txt==="START"){
			this.status = 2;
			this.duration=0;
		} else if (txt==="FINISH"){
			this.status = 0;
			this.duration=0;
		} else {
			this.knowledgeArea = knowledgeArea;
			this.responsiblePerson = responsiblePerson;
			this.duration = duration;
			this.RACI = RACI;
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
		}

		this.toString = function(){
			return "ID: "+this.ID
			+"; text: "+this.txt
			+"; x: "+this.x
			+"; y: "+y
			+"; status: "+this.status
			+"; output: "+this.output
			+"; input: "
			+this.input+"; knowledgeArea: "+getProfessionFromID(this.knowledgeArea)
			+"; res.person: "+getPersonFromID(this.responsiblePerson)
			+"; duration: "+this.duration
			+"; RACI: "+this.RACI
			+"; Process Name: "+getProcessNameFromID(this.processID)
			+"; Project Name: "+getProjectNameFromID(this.processID);
		}

		this.getRelevantData = function() {

			if (this.txt==="START") {
				return "ID: "+this.ID
				+"; text: "+this.txt
				+"; Process Name: "+getProcessNameFromID(this.processID)
				+"; Project Name: "+getProjectNameFromID(this.processID);

			} else if (this.txt==="FINISH") {
				return "ID: "+this.ID
				+"; text: "+this.txt
				+"; status: "+this.status
				+"; Process Name: "+getProcessNameFromID(this.processID)
				+"; Project Name: "+getProjectNameFromID(this.processID);

			} else {
				return "ID: "+this.ID
				+"; text: "+this.txt
				+"; status: "+this.status
				+"; output: "+this.output
				+"; input: "+this.input
				+"; knowledgeArea: "+getProfessionFromID(this.knowledgeArea)
				+"; res.person: "+getPersonFromID(this.responsiblePerson)
				+"; duration: "+this.duration
				+"; RACI: "+this.RACI
				+"; Process Name: "+getProcessNameFromID(this.processID)
				+"; Project Name: "+getProjectNameFromID(this.processID);
			}
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
function insertText(gEl, id, title) {
	//returns if no valid title passed
	if(title==null){
		return;
	}

	//initialize the text element
	var el = gEl.append("text")
	.attr("text-anchor","middle")
	.attr("dy", "-7.5");
	if (title!="START" && title!="FINISH") {
		el.append("tspan").html("ID: ").style("font-weight","bold");
		el.append("tspan").text(id);
		el.append("tspan").html("Name: ").style("font-weight","bold").attr('x', 0).attr('dy', '20');
		el.append("tspan").text(title);
	} else {
		el.append("tspan").html(title).style("font-weight","bold").attr('x', 0).attr('dy', '5');
	}
	
};

function getNodeByID(nodeID) {
	let nodes = graphObj.nodes;
	for (var i = 0;i<nodes.length;i++) {
		if (nodes[i].ID==nodeID) {
			return nodes[i];
		}
	}
	return null;
}

function getProfessionFromID(ID) {
	var result="";
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			let res = this.responseText.split(",");
			result = res[0]+" ("+res[1]+")";
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getprofession&n="+ID, false);
	xmlhttp.send();
	return result;
}

function getPersonFromID(ID) {
	var result="";
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			result = this.responseText;
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getperson&n="+ID, false);
	xmlhttp.send();
	return result;
}

function getProcessNameFromID(ID) {
	var result="";
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			result = this.responseText;
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getprocess&n="+ID, false);
	xmlhttp.send();
	return result;
}

function getProjectNameFromID(ID) {
	var result="";
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			result = this.responseText;
		}
	};
	xmlhttp.open("GET", "php_functions/getdatas.php?q=getproject&n="+ID, false);
	xmlhttp.send();
	return result;
}

function getStartNodeID() {
	for (var i = 0;i<nodes.length;i++) {
		if (nodes[i].txt==="START") {
			return nodes[i].ID;
		}
	}
	return -1;
}

function getFinishNodeID() {
	for (var i = 0;i<nodes.length;i++) {
		if (nodes[i].txt==="FINISH") {
			return nodes[i].ID;
		}
	}
	return -1;
}
//end of node functions

//EDGE CLASS
class Edge {
	constructor(ID,fromNodeID,toNodeID) {
		this.ID = ID;
		this.fromNodeID = fromNodeID;
		this.toNodeID = toNodeID;
		this.toString = function(){
			return "ID: "+this.ID+"; fromNodeID: "+this.fromNodeID
			+"; toNodeID: "+toNodeID;
		}
	}

}

//returns the path of the arrow
//d = the edge object
function getPath(d){
	var from = getNodeByID(d.fromNodeID);
	var to = getNodeByID(d.toNodeID);
	var recW = rectWidth;
	var recH = rectHeight;
	var recR = rectRoundness;

    //case 1
    if (from.x-recW < to.x && to.x < from.x+recW && from.y > to.y){
    	return "M"+from.x+","+(from.y-(recH/2))+
    	"L"+to.x+","+(to.y+(recH/2));
    }
    //case 2
    else if (to.x<=from.x-recW && to.y<=from.y-recH) {
    	return "M"+(from.x-(recW/2)+recR)+","+(from.y-(recH/2)+recR)+
    	"L"+(to.x+(recW/2)-(recR*0.2))+","+(to.y+(recH/2)-(recR*0.2));
    }
    //case 3
    else if(to.x<from.x && (from.y-recH < to.y) && (to.y < from.y+recH)) {
    	return "M"+(from.x-(recW/2))+","+from.y+
    	"L"+(to.x+(recW/2))+","+to.y;
    }
    //case 4
    else if(to.x<=from.x-recW && from.y+recH<=to.y) {
    	return "M"+(from.x-(recW/2)+recR)+","+(from.y+(recH/2)-recR)+
    	"L"+(to.x+(recW/2)-(recR*0.2))+","+(to.y-(recH/2)+(recR*0.2));
    }
    //case 5
    else if (from.x-recW<to.x && to.x<from.x+recW && to.y>from.y) {
    	return "M"+from.x+","+(from.y+(recH/2))+
    	"L"+to.x+","+(to.y-(recH/2));
    }
    //case 6
    else if (from.x+recW<=to.x && from.y+recH<=to.y) {
    	return "M"+(from.x+(recW/2)-recR)+","+(from.y+(recH/2)-recR)+
    	"L"+(to.x-(recW/2)+(recR*0.2))+","+(to.y-(recH/2)+(recR*0.2));
    }
    //case 7
    else if (from.x<to.x && from.y-recH<to.y && to.y<from.y+recH) {
    	return "M"+(from.x+(recW/2))+","+from.y+
    	"L"+(to.x-(recW/2))+","+to.y;
    }
    //case 8
    else if (from.x+recW<=to.x && from.y-recH>=to.y) {
    	return "M"+(from.x+(recW/2)-recR)+","+(from.y-(recH/2)+recR)+
    	"L"+(to.x-(recW/2)+(recR*0.2))+","+(to.y+(recH/2)-(recR*0.2));
    } else {
    	alert("error");
    }
}


//returns the array index of the element
//the element must have an ID property
function getPosInArray(objectID,array) {
	for (var i = 0;i<array.length;i++) {
		let curObject = array[i];
		if (curObject.ID===objectID) {
			return i;
		}
	}
	return null;
}


//index: possible ID
//array: the array of the other elements
function getValidID(array) {
	for (var index = 1;;index++) {
		var wrongID = false;
		for (var i = 0;i<array.length;i++) {
			if (array[i].ID===index) {
				wrongID=true;
				break;
			}
		}
		if (wrongID===true) {
			continue;
		}
		return index;
	}
}


//variables essential for working Graph class
var shiftKeyPressed,toNode,fromNode,mousePos,selectedEdge,selectedNode,justSpectating;

//instance must be named "graphObj" !!!!
class Graph{

	constructor(nodes,edges,allowedCreation,newNodeModalTriggerID,objectInfoModalTriggerID,justSpec){
		this.nodes = nodes;
		this.edges = edges;
		this.allowedCreation = allowedCreation;
		this.newNodeModalTriggerID = newNodeModalTriggerID;
		this.objectInfoModalTriggerID = objectInfoModalTriggerID;
		this.svg;
		justSpectating=justSpec;
	}

	getSVGElement(width,height){
		this.svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
		var thisGraph = this;
		var svg = this.svg;
		d3.select(svg)
			.attr("width", width)
			.attr("height", height)
			.on("click",function(){
				console.log("svg click")
				mousePos = d3.mouse(svg);
				if (shiftKeyPressed===true && thisGraph.allowedCreation===true) {
					d3.select("#"+thisGraph.newNodeModalTriggerID).node().click();
				}
			})
			.on("mousedown", function(d){
				if (d3.event.shiftKey) {
					shiftKeyPressed = true;
				}
			})
			.on("mouseup", function(){shiftKeyPressed = false;})
			.call(d3.zoom()
			    //.scaleExtent([1, 8]) //this modifies the maximum rate of the zoom
			    .on("zoom",thisGraph.zoom));

		//defining the arrow which later will serve
	    //as the marker-end for the edge-pathes

	    //default marker
	    d3.select(svg).append("defs").append("marker")
	    .attr("id","arrowhead")
	    .attr("viewBox","0 0 10 10")
	    .attr("refX",8)
	    .attr("refY",5)
	    .attr("markerWidth",6).attr("markerHeight",6)
	    .attr("orient","auto")
	    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

	    //hovering edge marker
	    d3.select(svg).append("defs").append("marker")
	    .attr("id","arrowheadHover")
	    .attr("viewBox","0 0 10 10")
	    .attr("refX",8).attr("refY",5)
	    .attr("markerWidth",6).attr("markerHeight",6)
	    .attr("orient","auto")
	    .attr("fill","yellow")
	    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

	    //selected edge marker
	    d3.select(svg).append("defs").append("marker")
	    .attr("id","arrowheadSelected")
	    .attr("viewBox","0 0 10 10")
	    .attr("refX",8).attr("refY",5)
	    .attr("markerWidth",6).attr("markerHeight",6)
	    .attr("orient","auto")
	    .attr("fill","blue")
	    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

	    //temporarly edge marker
	    d3.select(svg).append("defs").append("marker")
	    .attr("id","arrowheadTemp")
	    .attr("viewBox","0 0 10 10")
	    .attr("refX",5).attr("refY",5)
	    .attr("markerWidth",6).attr("markerHeight",6)
	    .attr("orient","auto")
	    .append("path").attr("d","M 0 0 L 10 5 L 0 10 z");

	    //this g element holds every node and edge
		var g = d3.select(svg).append("g")
		.attr("id","graph")
		.style("cursor","move");
		this.redraw(); 
		return svg;
	}

	redraw() {
		var thisGraph = this;

		//clearing pathes to be able to redraw them
		d3.selectAll("#edge").remove();
		//clearing nodes to be able to redraw them
		d3.select("#graph").selectAll("g").remove();

		//iterating through the edge array and creating
		//the corresponding SVG PATH elements (=arrows)
		var edges = thisGraph.edges;
		for(let i=0;i<edges.length;i++){
			d3.select("#graph").append("path")
			.attr("id","edge")
			.attr("d",function(){
				return getPath(edges[i]);
			})
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
				let d = edges[i];
				//hiding the status selector of the modal
				d3.select("#statusSelect").style("display","none");

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
		//holds the node's label too
		var nodes = thisGraph.nodes;
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
		.on("mouseup",function(d){shiftKeyPressed=false})
		
		.on("click", function(d,i){
			if (d3.event.defaultPrevented) return;
			console.log("ott")
			//changing status on ctrl+click
			if (d3.event.ctrlKey) {
				if (justSpectating){
					alert("You cannot change a node's status in spectating mode.");
					return;
				}
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
				//only shows if not selected yet
				if (selectedNode!=d) {
					selectedEdge=null;
					selectedNode=d;
					d3.select("#nodeNum"+d.ID).attr("fill","gold");

					//setting up the modal with the text
					d3.select("#objectName").text("Node: "+d.txt);
					let infoSplitted = d.getRelevantData().replace(new RegExp("; ", 'g'), "<br>");
					d3.select("#objectInfo").html(infoSplitted);
					d3.select("#"+thisGraph.objectInfoModalTriggerID).node().click();

					//disable status changing on start and finish nodes
					if (d.txt!="START" && d.txt!="FINISH") {
						d3.select("#statusSelect").style("display","block");
					}

					//setting the current status to the dropdown
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

				//if selected, deselect it
				} else {
					d3.select("#nodeNum"+d.ID).attr("fill",function (d) {
						return d.getColor();
					})
					selectedNode=null;					
				}
			}
		})
		//assingning the functions to dragging
		.call(d3.drag()
			.on("start", thisGraph.dragstarted)
			.on("drag", thisGraph.dragged)
			.on("end", thisGraph.dragend)
			);


		//adding nodes from array
		g.append("rect")
		.attr("id",function(d){return "nodeNum"+d.ID})
		.attr("x", rectWidth/-2)
		.attr("y", rectHeight/-2)
		.attr("width", rectWidth)
		.attr("height", rectHeight)
		.attr("rx", rectRoundness)
		.attr("ry", rectRoundness)
		.attr("fill",function(d){
			if (justSpectating==true) {
				return "beige";
			} else {
				if (selectedNode===d)
					return selectedNodeColor;
				else
					return d.getColor();
			}
		})
		.attr("stroke","black");

		g.each(function(d){
			insertText(d3.select(this),d.ID,d.txt);
		});
		//removes the dragged uncomplete edge
		d3.selectAll("#new").remove();
	}//end of redraw function

	//drag start, (creates if necessary and)
	//draws a temporary edge
	dragstarted(d) {
		if (shiftKeyPressed===true) {
			if (justSpectating==true){
				alert("You are in spectating mode, you cannot create new edge.");
				return;
			}
			fromNode=d;
			if (document.getElementById('new')===null){
				d3.select("#graph").append("path")
				.attr("id","new")
				.attr("stroke","black")
				.attr("stroke-width",edgeWidth)
				.attr("marker-end","url(#arrowheadTemp)");
			}
		}
	}

	//dragging, redraws the temporary edge
	//calculates
	dragged(d){
		if (shiftKeyPressed===true) {
			if (justSpectating==true){return;}
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
	dragend(d) {
		createEdge();
		shiftKeyPressed=false;
	}

	//zoom functionality
	zoom() {
		d3.select("#graph").attr("transform", d3.event.transform);
	}
}
