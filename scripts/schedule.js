$( document ).ready(function() {

// page is now ready, initialize the calendar...
setCalendar();
});

function see(){
	var events = [];
	events = $('#calendar').fullCalendar('clientEvents');
	console.log(events);
}

function setCalendar() {
	
	$('#calendar').fullCalendar({
		validRange: {
			start: '2018-12-13'
	  	},
		header:{
			left:   'title',
			center: 'agendaWeek,month,listWeek',
			right:  'today prev,next'
		},
		defaultView: 'agendaWeek',
		nowIndicator :true,

		locale:"en-gb",
		timeFormat: 'H:mm',

		firstDay:1,

		themeSystem:"bootstrap3",

		businessHours:{
			dow:[1,2,3,4,5],
			start: '8:00',
			end: '16:00'
		},
		slotLabelFormat:"H:mm",
		
		events: "php_functions/loadEvents.php",

		eventClick: function(calEvent, jsEvent, view){
			$("#modalEventTitle").html(calEvent.title);
			var description = "<b>FROM: </b>" + calEvent.start.format("Y MMM D ddd, HH:mm:ss")
			+ "<br><b>TO:    </b>" + calEvent.end.format("Y MMM D ddd, HH:mm:ss")
			+ "<br><b>DURATION: </b>" + moment.duration(calEvent.end.diff(calEvent.start)).humanize();
			$("#modalEventBody").html(description);
			$("#eventDetailsModal").modal();
			/*console.log(calEvent);
			console.log(jsEvent);
			console.log(view); */
		}
	});

	var now = new Date();

	$('#datetimepicker1').datetimepicker({
		format: "YYYY-MM-DD HH:mm",
		dayViewHeaderFormat: 'YYYY MMMM',
		minDate: now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes(),
		useCurrent: false,
		collapse: true,
		locale: moment.locale(),
		allowInputToggle: true,
		showClose: true,
		keepOpen: false
	}).on( "dp.change", function() {setMinDate();});
	

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
}

function setMinDate() {
	$('#datetimepicker2').data("DateTimePicker").clear();
	$('#datetimepicker2').data("DateTimePicker").minDate(new Date(1000*$('#datetimepicker1').data("DateTimePicker").viewDate().format("X")));
}

function submitNewEvent() {
	document.getElementById("newEventForm").submit();
}