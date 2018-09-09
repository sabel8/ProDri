

window.onload = function() {
	var width = window.innerWidth*0.8,
		height = 500;
	var nodes = new Array();
	var disableMove=true;
	var mouseOverCircle = false;
	nodes.push(new Node("példa 1",300,200));
	nodes.push(new Node("példa 2",100,300));


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

	function redraw(){
		var g = d3.select("#graph").selectAll("g")
			.data(nodes)
			.enter()
			.append("g")
			.attr("transform", function(d){
				return "translate("+d.x+","+d.y+")"
			})
			.on("mouseenter", function(){
				mouseOverCircle = true;
				d3.select(this).classed("hover",true);
			})
			.on("click",function(d,i){
				alert("Here will be the info of the node/task."
					+"\nThis node has the text \""+d.txt+"\" on it."
					+"\nIts ID is "+i+".")})
			.on("mouseleave", function(){
				mouseOverCircle = false;
				d3.select(this).classed("hover",false);
			});
		g.append("circle")
			.attr("r", 40)
			.attr("fill","lightblue")
			.attr("stroke","gray")

		g.each(function(d) {
			insertText(d3.select(this), d.txt)
		});
	}


	function createNode(){
		if (d3.event.shiftKey) {

			let event = d3.mouse(d3.select("#graph").node());
			let txt = prompt("Please enter the title of the task", "Példa egy");
			if (txt===null){return;}
			let a = new Node(txt,event[0],event[1]);
			nodes.push(a);
			redraw();
		};
	}
	redraw();
};


//constructor of the Node element
function Node(txt, x, y) {
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

