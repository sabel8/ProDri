var selectedPersonID;

$(document).ready(function () {
	//set the help popover
	$('[data-toggle="popover"]').popover();
});

function choosePerson(personID) {
	selectedPersonID = personID;
	$("tr").removeClass("info");
	$("#person"+personID).addClass("warning");
	$.ajax({
		url: "php_functions/getdatas.php",
		method: "POST",
		data: {
			q: "getPersonInfo",
			personID: personID
		},
		beforeSend: function(){
			$("#personName").val("LOADING...");
			//$("#profession").val("LOADING...");
			//$("#seniority").val("LOADING...");
		},
		success: function(data){
			data = JSON.parse(data);
			$("#personName").val(data[0]);
			$("#profession").val(data[1]);
			$("#seniority").val(data[2]);
			$("#person"+personID).removeClass("warning");
			$("#person"+personID).addClass("info");
		}
	});
}

function addPerson() {
	$.ajax({
		url: "php_functions/setdatas.php",
		method: "POST",
		data: {
			q: "addPerson",
			personName: $("#personName").val(),
			profession: $("#profession").val(),
			seniority: $("#seniority").val(),
			authority: $("#authority").val()
		},
		beforeSend: function(){
			$("#infoBox").html("");			
		},
		success: function(data){
			$("#infoBox").html(data);
			refreshTable();
		}
	});
}

function removePerson() {
	$.ajax({
		url: "php_functions/setdatas.php",
		method: "POST",
		data: {
			q: "removePerson",
			selectedID: selectedPersonID==null?"":selectedPersonID,
			personName: $("#personName").val(),
			profession: $("#profession").val(),
			seniority: $("#seniority").val()
		},
		beforeSend: function(){
			$("#infoBox").html("");			
		},
		success: function(data){
			$("#infoBox").html(data);
			refreshTable();
		}
	});
}

function refreshTable() {
	$.ajax({
		url: "php_functions/getdatas.php",
		method: "POST",
		data: {
			q: "getPersonsList"
		},
		success: function(data){
			data = JSON.parse(data);
			var bodyString = "";
			for (i=0;data.length>i;i++) {
				var curPerson = data[i];
				var id = curPerson[3];
				bodyString+="<tr id='person"+id+"' onclick='choosePerson("+id+")'>"+
				"<td>"+curPerson[0]+"</td>"+
				"<td>"+curPerson[1]+"</td>"+
				"<td>"+curPerson[2]+"</td>"+
				"<td>"+curPerson[4]+"</td>"+
				"</tr>";
			}
			$("#personsTableBody").html(bodyString);
		}
	});
}