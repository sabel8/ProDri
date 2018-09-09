

window.onload = function() {
	var width = window.innerWidth*0.8,
		height = 500;
	var nodes = new Array();
	nodes.push(new Node(0,"példa 1",300,200));
	nodes.push(new Node(1,"példa 2",100,300));


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

	var mousePos;

	function redraw(){
		var g = d3.select("#graph").selectAll("g")
			.data(nodes)
			.enter()
			.append("g")
			.attr("transform", function(d){
				return "translate("+d.x+","+d.y+")"
			})
			.on("mouseenter", function(){
				d3.select(this).classed("hover",true);
			})
			.on("click",function(d,i){
				alert("Here will be the info of the node/task."
					+"\nThis node has the text \""+d.txt+"\" on it."
					+"\nIts ID is "+d.ID+".")})
			.on("mouseleave", function(){
				d3.select(this).classed("hover",false);
			})
			.on("mousedown", function(d){
				mousePos = d3.mouse(this);
				d3.select("#graph").append("path")
		        .attr('d', 'M' + d.x + ',' + d.y + 'L' + d.x + ',' + d.y);
			})
			.call(d3.drag()
				.on("start", dragstarted)
	            .on("drag", dragged)
	            .on("end", dragend));

		g.append("circle")
			.attr("r", 40)
			.attr("fill","lightblue")
			.attr("stroke","gray")

		g.each(function(d) {
			insertText(d3.select(this), d.txt)
		});
	}

	function dragstarted(d) {
		event.stopPropagation();
		d3.select("#graph").append("path").attr("id","new");
		let e = d3.event;/*
		console.log(mousePos);
		console.log("event:  "+e.x+","+e.y);
		console.log("circle: "+d.x+","+d.y);*/
	}
	
	function dragged(d){
		let e = d3.event;
		let mouseX = e.x+mousePos[0];
		let mouseY = e.y+mousePos[1];
		d3.select("#new")
			.attr("d","M"+d.x+","+d.y+"L"+mouseX+","+mouseY+"Z")
			.attr("stroke","black")
			.attr("stroke-width","5");
		//redraw();
	}
	
	function dragend(d) {

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


//constructor of the Node element
function Node(ID, txt, x, y) {
	this.ID = ID;
	this.txt = txt;
	this.x = x;
	this.y = y;
}

Node.prototype.toString = function() {
  return "text: "+this.text+" x: "+this.x+" y: "+y;
}

insertText = function (gEl, title) {
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

