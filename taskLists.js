function getTasks() {
	var name = d3.select("#personName").node().value;
	var taskArray;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			taskArray = this.responseText.split(";");
			//console.log(this.responseText)
			createTasksTable(taskArray);
		}
	};
	xmlhttp.open("GET", "getdatas.php?q=tasklist&n="+name.replace(/ /g,"+"), true);
	xmlhttp.send();
}

function createTasksTable(array) {
	//if nothing is returned
	//inform the user
	if(array.length-1==0) {
		d3.select("#tasksTable").node().innerHTML = "<h3>This person either doesn't exist or doesn't have any task.</h3>";
		return;
	}
	var tableHtml = "<table class='table table-hover'>";
	tableHtml+="<tr><th>ID</th><th>Name</th><th>Status</th><th>Duration</th>"
	+"<th>RACI</th><th>Process name</th><th>Project name</th></tr>";

	//append the task with their input values
	for (var i=0;i<array.length-1;i++) {
		reviseInAndOutputs();
		array[i]+=","+getNodeByID(array[i][0]).input;
		array[i] = array[i].split(",");
	}

	//sorting to show "can be started" tasks first
	array.sort(sortByInputs);

	//rendering the table
	for (var i=0;i<array.length-1;i++) {
		var c = array[i];
		//defining the background color
		//accordance with the status
		if (c[2]==0){
			tableHtml += "<tr class='danger'>";
		} else if (c[2]==1) {
			tableHtml += "<tr class='info'>";
		} else {
			tableHtml += "<tr class='success'>";
		}

		for (var n=0;n<c.length-1;n++){
			if (n==2) {
				switch (Number(c[n])){
					case 0:
						if (c[c.length-1]==1){
							tableHtml += "<td>Not started yet</td>";
						} else {
							tableHtml += "<td>Can't be started</td>";
						}
						break;
					case 1:
						tableHtml += "<td>In progress</td>";
						break;
					case 2:
						tableHtml += "<td>Done</td>";
						break;
				}
			} else if(n==4) {
				switch (c[n]){
					case "r":
						tableHtml += "<td>Responsible</td>";
						break;
					case "a":
						tableHtml += "<td>Accountable</td>";
						break;
					case "c":
						tableHtml += "<td>Consultant</td>";
						break;
					case "i":
						tableHtml += "<td>Informed</td>";
						break;
				}
			} else {
				tableHtml += "<td>"+c[n]+"</td>";
			}
		}

		tableHtml += "</tr>";
	}

	tableHtml += "</table>";

	d3.select("#tasksTable").node().innerHTML = tableHtml;
}

//helper function for sorting
function sortByInputs(a,b){
	if (Number(b[b.length-1])>Number(a[a.length-1])){
		return 1;
	} else {
		return 0;
	}
}