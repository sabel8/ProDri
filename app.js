
//the whole functionality starts after the page has loaded
window.onload = function() {
	var width = window.innerWidth*0.8,
		height = 500;
	var nodes = new Array(),
		edges = new Array();
	nodes.push(new Node(0,"példa 0",300,200));
	nodes.push(new Node(1,"példa 1",100,300));
	nodes.push(new Node(2,"példa 2",500,200));


	d3.select("body").append("h1").text("Üdv!");

	var svg = d3.select("body").append("svg")
		.attr("width", width)
		.attr("height", height)
		.on("click", createNode)
		.call(d3.zoom()
		    //.scaleExtent([1, 8]) //this modifies the maximum rate of the zoom
		    .on("zoom",zoom));


	var g = svg.append("g")
		.attr("id","graph")
		.style("cursor","move");

	function zoom() {
		d3.select("#graph").attr("transform", d3.event.transform);
	}

	var toNode, fromNode, mousePos;
	var diffFromCursor = 10,
		circleRadius = 40,
		edgeWidth = 3;

	function redraw(){
		//clearing pathes to be able to redraw them
		d3.selectAll("path").remove();

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
				alert("Here will be the info of the node/task."
					+"\nThis node has the text \""+d.txt+"\" on it."
					+"\nIts ID is "+d.ID+".")})
			.on("mouseleave", function(){
				toNode = null;
				d3.select(this).attr("class",null);
			})
			.on("mousedown", function(d){
				mousePos = d3.mouse(this);
			})
			.call(d3.drag()
				.on("start", dragstarted)
	            .on("drag", dragged)
	            .on("end", dragend));
		//adding nodes from array	
		g.append("circle")
			.attr("r", circleRadius)
			.attr("fill","lightblue")
			.attr("stroke","gray");

		g.each(function(d) {
			insertText(d3.select(this), d.txt)
		});


		var e = d3.select("#graph");
		for(let i=0;i<edges.length;i++){
			e.append("path")
				.attr("d",function() {
					let currentEdge = edges[i];
					let from = nodes[currentEdge.fromNodeID];
					let to = nodes[currentEdge.toNodeID];
					let dir = "M"+from.x+","+from.y+"L"+to.x+","+to.y;
					console.log(i+" "+dir);
					return dir;
				})
				.attr("stroke","black")
				.attr("stroke-width",edgeWidth);
		}


		d3.selectAll("#new").remove();
	}

	function dragstarted(d) {
		let e = d3.event;
		fromNode=d;
		if (document.getElementById('new')===null)
		d3.select("#graph").append("path")
			.attr("id","new")
			.attr("stroke","black")
			.attr("stroke-width",edgeWidth);
		/*console.log(d);
		console.log(mousePos);
		console.log("event:  "+e.x+","+e.y);
		console.log("circle: "+d.x+","+d.y);*/
	}
	
	function dragged(d){
		let e = d3.event;
		let mouseX = e.x+mousePos[0];
		let mouseY = e.y+mousePos[1];
		let diffX=e.x-d.x;
		let diffY=e.y-d.y;
		if (diffX>0){
			mouseX-=diffFromCursor
		}else{
			mouseX+=diffFromCursor
		}
		if (diffY>0){
			mouseY-=diffFromCursor
		}else{
			mouseY+=diffFromCursor
		}
		d3.select("#new")
			.attr("d","M"+d.x+","+d.y+"L"+mouseX+","+mouseY);
	}
	
	function dragend(d) {
		createEdge();
		redraw();
	}

	function createEdge() {
		if (toNode!=null) {
			var a = new Edge(edges.length,fromNode.ID,toNode.ID);
			edges.push(a);
		}
		//console.log(edges);
	}
	

	function createNode(){
		if (d3.event.shiftKey) {
			let event = d3.mouse(d3.select("#graph").node());
			let txt = prompt("Please enter the title of the task", "Példa egy");
			if (txt===null){return;}
			nodes.push(new Node(nodes.length,txt,event[0],event[1]));
		}
		redraw();
	}


	redraw();
};


//NODE CLASS
//constructor of the Node element
class Node {
	constructor(ID, txt, x, y){
		this.ID = ID;
		this.txt = txt;
		this.x = x;
		this.y = y;
		this.toString = function(){
			return "text: "+this.txt+" x: "+this.x+" y: "+y;
		}
	}
}

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