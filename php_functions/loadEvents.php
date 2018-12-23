<?php
include_once("../config.php");
$begin = new DateTime($_GET["start"]);
//TODO PERSON ID DYNAMIC
$data=array();
try {
	$end = new DateTime(isset($_GET["end"])?$_GET["end"]:"2019-02-01");
	
	$diff = abs($end->getTimestamp() - $begin->getTimestamp());
	$days = floor($diff / (60*60*24));
} catch (Exception $e) {
}

for ($i=0; $i < $days; $i++) {

    $events = getRowsOfQuery("SELECT events.title ,\"{$begin->format('Y')}\" as year,\"{$begin->format('m')}\"  as month,
	\"{$begin->format('d')}\" as day,time(events.startTime),addTime(time(events.startTime),duration)
        FROM `unavaliable_timeslots` AS events
        JOIN `timeslot_repetitions` EM2 ON EM2.repetition_type = 'weekday' AND EM2.timeslotID=events.ID
        WHERE (year(startTime)=".$begin->format('Y')." AND month(startTime)=".$begin->format('m')." AND day(startTime)=".$begin->format('d').")
			OR (repetition_value={$begin->format('w')} AND UNIX_TIMESTAMP(startTime)<=".strtotime($begin->format('Y-m-d')).") GROUP BY events.ID");
			
	$begin->modify("+1 day");
	
	if(count($events)!=1){
		for ($j=0; $j < count($events)-1; $j++) { 
			$curEvent=explode("|",$events[$j]);
			$data[] = array(
				'allDay' => false,
				'title' => $curEvent[0],
				'start' => $curEvent[1].'-'.$curEvent[2].'-'.$curEvent[3].'T'.$curEvent[4],
				'end' => $curEvent[1].'-'.$curEvent[2].'-'.$curEvent[3].'T'.$curEvent[5]
			);
		}
		
	}
}

//getting the extra events from the timeslot_exceptions table
$plusEvents=getRowsOfQuery("SELECT startTime,endTime,title,avaliable FROM timeslot_exceptions
WHERE personID=1 AND UNIX_TIMESTAMP(startTime)<=".strtotime($begin->format('Y-m-d')));
if(count($plusEvents)!=1){
	for ($j=0; $j < count($plusEvents)-1; $j++) { 
		$curEvent=explode("|",$plusEvents[$j]);
		$data[] = array(
			'allDay' => false,
			'title' => $curEvent[2],
			'start' => $curEvent[0],
			'end' => $curEvent[1],
			'color' => ($curEvent[3]==0?"red":"green")
		);
	}
	
}
echo json_encode($data);
?>