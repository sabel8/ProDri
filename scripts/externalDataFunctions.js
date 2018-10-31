/**************      DOWNLOADING   *************/

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

/**************      UPLOADING   *************/
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
		filereader.readAsText(uploadFile);
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
					let a = new Node(d.ID,d.txt,d.x,d.y,d.status,"artist","John Jonas",5,"r");
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
				alert("Error parsing uploaded file\n" + err.message);
				edges = backupEdges;
				nodes = backupNodes;
				//return;
			}
			reviseInAndOutputs();
		};
	} else {
		alert("Your browser won't let you save this graph -- try upgrading your browser to IE 10+ or Chrome or Firefox.");
	}
	selectedEdge=null;
	selectedNode=null;
	redraw();
}