$(document).ready(function () {
	// page is now ready, initialize the calendar...
	setCalendar();
});

function see() {
	events = $('#calendar').fullCalendar('clientEvents');
	for (var i = 0; i < events.length; i++) {
		var mainEvent = events[i];
		//not inspecting regular events
		if(mainEvent.regular==true) {
			continue;
		}
		/* console.log("mainevent "+mainEvent.title);
		console.log(mainEvent.start._i);
		console.log(mainEvent.end._i); */
		for (var j = 0; j < events.length; j++) {
			//not inspecting the element with itself
			if (j == i) {
				continue;
			}
			var curEvent = events[j];
			/* console.log("curEvent " + curEvent.title);
			console.log(curEvent.start._i);
			console.log(curEvent.end._i); */

			//curEvent starts earlier, but meets mainEvent
			if (mainEvent.start > curEvent.start && mainEvent.start < curEvent.end && mainEvent.regular == false && curEvent.regular == true) {
				/*alert("gotcha "+mainEvent.title);*/

				//if curEvent lasts longer than the mainEvent
				if (mainEvent.end < curEvent.end) {
					//create the new element
					var splittedEvent = {
						title: curEvent.title + " (part 2)",
						start: mainEvent.end._i,
						end: curEvent.end._i,
						regular: true,
						canEdit: false
					};
					curEvent.title += " (part 1)";

					//blocking the program to create the same event twice
					$('#calendar').fullCalendar('removeEvents', function(event){
						if(event.end._i == splittedEvent.end && event.start._i == splittedEvent.start) {
							return true;
						} else {
							return false;
						}
					});
					$('#calendar').fullCalendar('renderEvent', splittedEvent, true);

					//a new event is added so the loop must run one more time
					j--;
				}

				curEvent.end = mainEvent.start;
				$('#calendar').fullCalendar('updateEvent', curEvent);
			}
			//mainEvent start earlier and ends in curEvent
			else if (mainEvent.start < curEvent.start && mainEvent.end > curEvent.start && mainEvent.end < curEvent.end && mainEvent.regular == false && curEvent.regular == true) {
				curEvent.start = mainEvent.end;
				$('#calendar').fullCalendar('updateEvent', curEvent);
			}
		}
	}
	/* console.log(events); */
}

function openEditModal() {
	$('#eventEditModal').modal();
	$('#fromDateTimeEdit').html();
}

function setCalendar() {

	$('#calendar').fullCalendar({
		validRange: {
			start: '2018-12-13'
		},
		header: {
			left: 'title',
			center: 'agendaWeek,month,listWeek',
			right: 'today prev,next'
		},
		defaultView: 'agendaWeek',
		nowIndicator: true,

		locale: "en-gb",
		timeFormat: 'H:mm',

		firstDay: 1,

		themeSystem: "bootstrap3",

		businessHours: {
			dow: [1, 2, 3, 4, 5],
			start: '8:00',
			end: '16:00'
		},
		slotLabelFormat: "H:mm",

		eventAfterAllRender: function (view) {
			see();
		},

		events: "php_functions/loadEvents.php",
		
		//opening event info modal on click
		eventClick: function (calEvent, jsEvent, view) {
			$("#modalEventTitle").html(calEvent.title);
			var description = "<b>FROM: </b>" + calEvent.start.format("Y MMM D ddd, HH:mm:ss") +
				"<br><b>TO:    </b>" + calEvent.end.format("Y MMM D ddd, HH:mm:ss") +
				"<br><b>DURATION: </b>" + moment.duration(calEvent.end.diff(calEvent.start)).humanize() +
				"<br><b>NodeID: </b>" + calEvent.nodeID;
			$("#modalEventBody").html(description);
			if (calEvent.canEdit == true) {
				$("#editEventButton").show();
			} else {
				$("#editEventButton").hide();
			}
			$("#eventDetailsModal").modal();

			//setting up the editing modal values
			if (calEvent.canEdit == true) {
				$("#deleteEventButton").val(calEvent.dbID);
				$("#saveEventButton").val(calEvent.dbID);
				$("#eventNameInput").val(calEvent.title);
				$("#avaliableEvent").prop('checked',calEvent.avaliable);
				$('#datetimepicker4').data("DateTimePicker").minDate(calEvent.start.local().toDate());
				$('#datetimepicker3').data("DateTimePicker").date(calEvent.start.local().toDate());
				$('#datetimepicker4').data("DateTimePicker").date(calEvent.end.local().toDate());
				//set date for datetimepicekr4
			}
		}
	});

	var now = new Date();

	$('#datetimepicker1').datetimepicker({
		format: "YYYY-MM-DD HH:mm",
		dayViewHeaderFormat: 'YYYY MMMM',
		minDate: now.getFullYear() + "-" + (now.getMonth() + 1) + "-" + now.getDate() + " " + now.getHours() + ":" + now.getMinutes(),
		useCurrent: false,
		collapse: true,
		locale: moment.locale(),
		allowInputToggle: true,
		showClose: true,
		keepOpen: false
	}).on("dp.change", function () {
		setMinDate('#datetimepicker1','#datetimepicker2');
	});


	$('#datetimepicker2').datetimepicker({
		format: "YYYY-MM-DD HH:mm",
		dayViewHeaderFormat: 'YYYY MMMM',
		minDate: $('#datetimepicker1').data("DateTimePicker").minDate(),
		useCurrent: false,
		collapse: true,
		locale: moment.locale(),
		allowInputToggle: true,
		showClose: true,
		keepOpen: false
	});

	$('#datetimepicker3').datetimepicker({
		format: "YYYY-MM-DD HH:mm",
		dayViewHeaderFormat: 'YYYY MMMM',		
		minDate: now.getFullYear() + "-" + (now.getMonth() + 1) + "-" + now.getDate() + " " + now.getHours() + ":" + now.getMinutes(),
		useCurrent: false,
		collapse: true,
		locale: moment.locale(),
		allowInputToggle: true,
		showClose: true,
		keepOpen: false
	}).on("dp.change", function () {
		setMinDate('#datetimepicker3','#datetimepicker4');
	});

	$('#datetimepicker4').datetimepicker({
		format: "YYYY-MM-DD HH:mm",
		dayViewHeaderFormat: 'YYYY MMMM',
		minDate: $('#datetimepicker3').data("DateTimePicker").minDate(),
		useCurrent: false,
		collapse: true,
		locale: moment.locale(),
		allowInputToggle: true,
		showClose: true,
		keepOpen: false
	});
}

function setMinDate(idOfFromDateTimeInput,idOfToDateTimeInput) {
	var fromDate = parseInt($(idOfFromDateTimeInput).data("DateTimePicker").viewDate().format("X"));
	var toDate = parseInt($(idOfToDateTimeInput).data("DateTimePicker").viewDate().format("X"));
	if(fromDate>=toDate) {
		$(idOfToDateTimeInput).data("DateTimePicker").clear();
	}
	$(idOfToDateTimeInput).data("DateTimePicker").minDate(new Date(1000 * fromDate));
}

function submitNewEvent() {
	document.getElementById("newEventForm").submit();
}