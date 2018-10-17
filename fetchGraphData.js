//getting data from database
//through getdatas.php file
function getNodesAndEdges() {
	var array;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
			//console.log(this.responseText)
			setNodes(array);
		}
	};
	xmlhttp.open("GET", "getdatas.php?q=nodes", true);
	xmlhttp.send();

	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			array = this.responseText.split(";");
			setEdges(array);
		}
	};
	xmlhttp.open("GET", "getdatas.php?q=edges", true);
	xmlhttp.send();
}

function setNodes(array) {
	nodes=new Array();
	for (var i=0;i<array.length-1;i++) {
		//current node data
		var c = array[i].split("|");
		if (c[1]=="START") {
			nodes.push(new Node(i,c[1],Number(c[2]),Number(c[3]),2,null,null,null,null,c[9],c[10]));
		} else if (c[1]=="FINISH") {
			nodes.push(new Node(i,c[1],Number(c[2]),Number(c[3]),0,null,null,null,null,c[9],c[10]));
		} else {
			nodes.push(new Node(i,c[1],Number(c[2]),Number(c[3]),Number(c[4]),c[5],c[6],Number(c[7]),c[8],c[9],c[10]));
		}
	}
	//redraw if graph should be shown
	if(window.location.pathname=="/graphs/" || window.location.pathname=="/graphs/index.php") {
		redraw();
	}
}
function setEdges(array) {
	edges=new Array();
	for (var i=0;i<array.length-1;i++) {
		//current edge data
		var c = array[i].split(",");
		edges.push(new Edge(i,Number(c[1]),Number(c[2])));
	}
}