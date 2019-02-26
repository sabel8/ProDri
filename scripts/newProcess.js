window.onload = function() {	
	//dimensions for the main svg element
	var height = 400;
	var width = d3.select("#formBody").node().offsetWidth;
	//constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI, processID, desc)
	var startNode = new Node(1,"START",100,50,0,"-1",null,0,"",null,"");
	var finishNode = new Node(2,"FINISH",width-150,height-50,0,"-1",null,0,"",null,"");
	graphObj = new Graph([startNode,finishNode],[],true,"newNodeModalTrigger","objectInfoModalTrigger",false,true);
	d3.select("#processBuilder").node().appendChild(graphObj.getSVGElement("100%",height));
	reviseInAndOutputs();
	redraw();
};

function submitProcess(){
	let title=$("#processName").val(),desc=$("#processDesc").val();
	if (title=="" || desc==""){
		alert("You must fill in the gaps!");
		return;
	}
	$.ajax({
		url: "php_functions/setdatas.php",
		method: "POST",
		data: {
			q: "newAbstractProcess",
			title : $("#processName").val(),
			desc  : $("#processDesc").val(),
			nodes : JSON.stringify(graphObj.nodes),
			edges : JSON.stringify(graphObj.edges)
		},
		beforeSend: function(){
			$("#res").html("LOADING...");
		},
		success: function(data){
			alert(data);
			$("#res").html(data);
		}
	});
}