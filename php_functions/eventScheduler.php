<?php
require_once(__DIR__."/../config.php");
$printInfo=true;

if (isset($_GET["processID"])) {
	$processID = $_GET["processID"];
	$startTime = isset($_GET["startTime"])?$_GET["startTime"]:time();
	if (is_numeric($processID) && is_numeric($startTime)) {
		$processID = (int) $processID;
		$startTime = (int) $startTime;
	} else {
		die("ProcessID and startTime has to be a number!<br>");
	}
} else {
	die("ProcessID or startTime is not set in GET.");
}

//array for counting how many node have been put into calendar
$doneNodeIDs = [];

//echo getFirstAvaliableTimeslot(1,"2019-02-01 10:37:45")."ezezez<br>";
$pathes=[];
deleteEventsOfProcess($processID);
$critPath = calculateCriticalPath($processID);
array_pop($critPath);
if($printInfo==true){print_r($critPath);}
if($printInfo==true){echo " : critpath<br>";}
fillInTaskEvent(null);

function fillInTaskEvent($curEdges){
	global $processID, $doneNodeIDs, $critPath, $printInfo;
	//the node with the text 'START'
	if (count($doneNodeIDs) == 0) {
		$startNodeID = getRowsOfQuery("SELECT nodeID FROM nodes WHERE txt='START' AND processID=$processID")[0];
		$curEdges = getRowsOfQuery("SELECT toNodeID FROM edges WHERE processID=$processID AND fromNodeID=$startNodeID");
		if($printInfo==true){echo count($doneNodeIDs).". START<br>";}
		$doneNodeIDs[] = $startNodeID;
		fillInTaskEvent($curEdges);
	}else {
		$nodesOnCritPath = array_intersect($critPath,$curEdges);
		if (count($nodesOnCritPath)>0) {
			//go through all nodes on critical path
			foreach ($nodesOnCritPath as $key => $nodeID) {
				if(in_array($nodeID,$doneNodeIDs)) {
					continue;
				}
				$curTask = getRowsOfQuery("SELECT txt FROM nodes WHERE processID=$processID AND nodeID=$nodeID");
				
				$prevTasks = getRowsOfQuery("SELECT fromNodeID FROM edges WHERE processID=$processID AND toNodeID=$nodeID");
				array_pop($prevTasks);
				//if all predecessors are scheduled
				if (count(array_intersect($prevTasks,$doneNodeIDs)) == count($prevTasks)) {
					if($printInfo==true){echo "<br><hr><br>".  count($doneNodeIDs).". ";print_r($curTask[0])."<br>";}
					scheduleTask($nodeID);
					$doneNodeIDs[] = $nodeID;
					//no longer taking it into consideration
					array_splice($curEdges, $key, 1);
					fillInTaskEvent(getRowsOfQuery("SELECT toNodeID FROM edges WHERE processID=$processID AND fromNodeID=$nodeID"));
				}
			}
		}

		//go through the tasks NOT on critical path
		for ($i=0; $i < count($curEdges)-1; $i++) {
			$nodeID=$curEdges[$i];
			if(in_array($nodeID,$doneNodeIDs)) {
				continue;
			}
			$curTask = getRowsOfQuery("SELECT txt FROM nodes WHERE processID=$processID AND nodeID=$nodeID");
			
			$prevTasks = getRowsOfQuery("SELECT fromNodeID FROM edges WHERE processID=$processID AND toNodeID=$nodeID");
			array_pop($prevTasks);
			//if all predecessors are scheduled
			if (count(array_intersect($prevTasks,$doneNodeIDs)) == count($prevTasks)) {
				if($printInfo==true){echo count($doneNodeIDs).". ";print_r($curTask[0]);echo "<br><hr><br><br>";}
				scheduleTask($nodeID);
				$doneNodeIDs[] = $nodeID;
				fillInTaskEvent(getRowsOfQuery("SELECT toNodeID FROM edges WHERE processID=$processID AND fromNodeID=$nodeID"));
			}
		}
	}

}

function scheduleTask($taskID){
	global $connection, $processID, $startTime, $printInfo;
	$realNodeID = (int) getRowsOfQuery("SELECT ID FROM nodes WHERE nodeID=$taskID AND processID=$processID")[0];
	$nodeTitle = getRowsOfQuery("SELECT txt FROM nodes WHERE ID=$realNodeID")[0];
	if ($nodeTitle=="START" OR $nodeTitle=="FINISH") {
		return;
	}
	$predecessors = getRowsOfQuery("SELECT fromNodeID FROM edges WHERE processID=$processID AND toNodeID=$taskID");
	
	if($printInfo==true){print_r($predecessors);echo " : $nodeTitle task predecs<br>";}
	$userID=getRowsOfQuery("SELECT responsiblePersonID FROM nodes WHERE ID=$realNodeID")[0];
	$remainingDur = (int) getRowsOfQuery("SELECT duration FROM nodes WHERE ID=$realNodeID")[0];

	$canBeStarted = (int) $startTime;
	//goes through the predecessor nodes
	//finding for the first when the node can be started
	//(end timestamp of the predecessor which finishes last)
	for ($i=0; $i < count($predecessors)-1; $i++) {
		$curPredecID = $predecessors[$i];
		//if the predecessor is start then continue
		if(count(getRowsOfQuery("SELECT ID FROM nodes WHERE txt='START' AND nodeID=$curPredecID")) > 1){
			continue;
		}
		$lastTime = getRowsOfQuery("SELECT unix_timestamp(startTime)+time_to_sec(duration) as endTime FROM unavaliable_timeslots WHERE
		nodeID=(SELECT ID FROM nodes WHERE processID=$processID AND nodeID=$curPredecID) ORDER BY endTime DESC");
		//echo "<br>".date('Y-m-d H:i:s',$canBeStarted) . " vagy " . date('Y-m-d H:i:s', $lastTime[0])."<br>";
		if ((int) $lastTime[0] > $canBeStarted) {
			$canBeStarted = $lastTime[0];
		}
	}
	$eventCounter=0;
	//set plannedStart of the node
	$setPlannedStartSQL = "UPDATE nodes SET plannedStart=\"".
		getFirstAvaliableTimeslot($userID,date('Y-m-d H:i:s', $canBeStarted))."\" WHERE ID=$realNodeID";
	if (!mysqli_query($connection, $setPlannedStartSQL)) {
		echo "Error: " . $setPlannedStartSQL . "<br>" . mysqli_error($connection);
	}
	while ($remainingDur > 0){
		$eventCounter++;
		$freeStart = getFirstAvaliableTimeslot($userID,date('Y-m-d H:i:s', $canBeStarted));
		$endOfFreeTime = getEndOfFreeTimeslot($userID,$freeStart);
		$curDur = strtotime($endOfFreeTime) - strtotime($freeStart);
		//if this fills the task
		if ($remainingDur - $curDur < 0) {
			$curDur = $remainingDur;
			$canBeStarted = strtotime($freeStart) + $curDur;
			$endOfTask = date('Y-m-d H:i:s',strtotime($freeStart) + $curDur);
			//set plannedFinish of the node
			$setPlannedFinishSQL = "UPDATE nodes SET plannedFinish='$endOfTask' WHERE ID=$realNodeID";
			if (!mysqli_query($connection, $setPlannedFinishSQL)) {
				echo "Error: " . $setPlannedFinishSQL . "<br>" . mysqli_error($connection);
			}
			$partNo=$eventCounter>1?" (part ". $eventCounter .")":"";
		} else {
			$canBeStarted = strtotime($endOfFreeTime);
			$partNo=" (part ". $eventCounter .")";
		}
		$remainingDur -= $curDur;
		if($printInfo==true){
			print_r($freeStart);echo ":from $nodeTitle $partNo<br>";
			print_r(date('Y-m-d H:i:s',strtotime($freeStart)+$curDur));echo ":end $nodeTitle $partNo<br>";
		}
		$sql = "INSERT INTO unavaliable_timeslots (title,nodeID,personID,startTime,duration) 
		VALUES (CONCAT('WORK: ','$nodeTitle','$partNo'), $realNodeID,(SELECT responsiblePersonID FROM nodes WHERE ID=$realNodeID),
		'$freeStart',SEC_TO_TIME($curDur))";
		//running the query
		if (!mysqli_query($connection, $sql) ) {
			echo "Error: " . $sql . "<br>" . mysqli_error($connection);
		}
	}
}

function deleteEventsOfProcess($processID) {
	global $connection;
	if (!($query = $connection->prepare("DELETE FROM unavaliable_timeslots 
	WHERE NOT personID=0 AND nodeID IN (SELECT ID FROM nodes WHERE processID=?)"))) {
		echo "Prepare failed: (" . $connection->errno . ") " . $connection->error;
	}
	$query->bind_param("i",$processID);

	if (!$query->execute()) {
		echo "Error deleting record: " . mysqli_error($connection);
	}
}

//timeformat: 2019-01-12 03:03:59 (input and returning, too)
function getFirstAvaliableTimeslot($userID,$time) {
	//echo $time."ez az ido<br>";
	//$time-kor ütközik-e valamelyik eseménnyel
	$exceptions = getRowsOfQuery("SELECT endTime,'timeslot exception' FROM timeslot_exceptions 
		WHERE startTime<='$time' AND endTime>'$time' AND avaliable=false AND personID=$userID");
	if(count($exceptions)>1){
		$time = explode("|",$exceptions[0])[0];
		return getFirstAvaliableTimeslot($userID,$time);
	}
	//is there any regular event happening at that time
	$regular = getRowsOfQuery("SELECT IF(addtime(date('$time'),time(startTime))>'$time',addtime(date('$time'),time(startTime)),'$time'),
		addtime(date('$time'), addtime(time(startTime),duration)),'regular event' FROM unavaliable_timeslots events
		#checks weekly repetitions
		LEFT JOIN `timeslot_repetitions` trw ON trw.repetition_type = 'weekday' AND trw.timeslotID=events.ID
		WHERE weekday('$time')=IF(repetition_value=0,6,repetition_value-1) AND TIME(events.startTime)<=TIME('$time') 
		AND ADDTIME(TIME(events.startTime),events.duration)>TIME('$time') #starts not after $time and ends after $time");
	//if yes...
	$regular = array_merge($regular, getRowsOfQuery("SELECT startTime,addTime(startTime,duration) FROM unavaliable_timeslots 
		WHERE NOT nodeID=0 AND startTime<='$time' AND addtime(startTime,duration)>'$time'"));
	//print_r($regular);echo"<br>";
	if(count($regular)>2){
		if($regular[0]=="") {
			$start = explode("|",$regular[1])[0];
			$time = explode("|",$regular[1])[1];
		} else {
			$start = explode("|",$regular[0])[0];
			$time = explode("|",$regular[0])[1];
		}
		//echo "start: ".$start." end: ".$time."<br>";
		//echo $time." volt regular, ez a vége<br>";
		//inspects if there is any free exception event interrupting the regular event
		$exception =getRowsOfQuery("SELECT startTime,endTime,'exception in regular event' FROM timeslot_exceptions 
			WHERE startTime>='$start' AND endTime<'$time' AND avaliable=True AND personID=$userID");
		//print_r($exception);echo "<br>";
		//if there is, return it's startDateTime
		if(count($exception)>1) {
			//the interrupting exception won
			return explode("|",$exception[0])[0];
		} 
		return getFirstAvaliableTimeslot($userID,$time);
	} 
	return $time;
}

//timeformat: 2019-01-12 03:03:59
//time is the starting moment of the free timeslot
function getEndOfFreeTimeslot($userID,$time) {
	//if free event is happening now, return its endDateTime
	$freeException = getRowsOfQuery("SELECT endTime FROM timeslot_exceptions
		WHERE startTime<='$time' AND endTime>'$time' AND personID=$userID AND avaliable=true");
	if(count($freeException)>1) {
		return explode("|",$freeException[0])[0];
	}
	
	//already begun but hasn't ended OR the next event
	$regular =getRowsOfQuery("SELECT IF(addtime(date('$time'),time(startTime))>'$time',addtime(date('$time'),time(startTime)),'$time')
	,addtime(date('$time'),	addtime(time(startTime),duration)),'regular event' FROM unavaliable_timeslots events
	#checks weekly repetitions
	JOIN `timeslot_repetitions` trw ON trw.repetition_type = 'weekday' AND trw.timeslotID=events.ID
	WHERE (weekday('$time')=IF(repetition_value=0,6,repetition_value-1) AND 
	(TIME(events.startTime)<=TIME('$time') AND ADDTIME(TIME(events.startTime),events.duration)>TIME('$time')) 
	OR (TIME(events.startTime)>TIME('$time')) ) LIMIT 1");
	

	$nextBusyException=getRowsOfQuery("SELECT startTime FROM timeslot_exceptions
		WHERE startTime>'$time' AND personID=$userID AND avaliable=false ORDER BY startTime ASC LIMIT 1");

	if(count($nextBusyException)>1 && count($regular)>1) {
		$regular = explode("|",$regular[0])[0];
		$nextBusyException = explode("|",$nextBusyException[0])[0];
		$final = getRowsOfQuery("SELECT IF('$nextBusyException'<='$regular','$nextBusyException','$regular')");
		return explode("|",$final[0])[0];
	} else {
		return explode("|",$regular[0])[0];
	}
	
}

function calculateCriticalPath($processID) {
	global $pathes, $printInfo;
	$curPath = [];
	$durations = [];
	//add START node
	$startingNodeID = getRowsOfQuery("SELECT nodeID FROM nodes WHERE processID=$processID AND txt='START'")[0];
	$finishNodeID = getRowsOfQuery("SELECT nodeID FROM nodes WHERE processID=$processID AND txt='FINISH'")[0];
	$curPath[] = $startingNodeID;
	$edges = getRowsOfQuery("SELECT ID,fromNodeID,toNodeID FROM edges WHERE processID=$processID");
	calcPathes($startingNodeID,$finishNodeID,[],$curPath,$edges);

	//for calculating critical path with the user-defined calendar
	//iterating through the pathes
	for ($i=0; $i < count($pathes); $i++) { 
		$curPath = $pathes[$i];
		$curStart= $GLOBALS['startTime'];
		$curDur = 0;
		//iterating through the nodes of the current path
		for ($j=0; $j < count($curPath); $j++) {
			$curID = $curPath[$j];
			$datas = explode("|",getRowsOfQuery("SELECT responsiblePersonID,duration FROM nodes WHERE nodeID=$curID AND processID=$processID")[0]);
			//START or FINISH node
			if($datas[0] == "" && count($datas) == 1) {
				continue;
			}
			$personID = $datas[0];
			$remainingTime = (int) $datas[1];
			while($remainingTime>0) {
				$startWorkTime = getFirstAvaliableTimeslot($personID,date('Y-m-d H:i:s',$curStart));
				$endWorkTime = getEndOfFreeTimeslot($personID,$startWorkTime);
				if (strtotime($endWorkTime)-strtotime($startWorkTime) > $remainingTime) {
					$curDur = strtotime($startWorkTime)+$remainingTime;
					$curStart = $curDur;
					break;
				} else {
					$remainingTime -= strtotime($endWorkTime)-strtotime($startWorkTime);
					$curStart=strtotime($endWorkTime);
				}
			}
		}
		$durations[] = (int) ($curDur-$GLOBALS['startTime']);
		//echo date('Y-m-d H:i:s',$curDur)."<br>";
	}

	//printing the pathes and their durations
	if ($printInfo==true) {
		for ($i=0; $i < count($pathes); $i++) { 
			echo implode(" -> ",$pathes[$i]) ."<br>";
			echo $durations[$i]."<br>";
		}
	}

	$maxIndex = array_keys($durations,max($durations))[0];
	$crit = implode(" -> ",$pathes[$maxIndex]);
	//echo "the critical path is: ".$crit." with duration of ".$durations[$maxIndex];
	return $pathes[$maxIndex];
	
}

function calcPathes($startID,$endID,$visited,$localPathList,$edges) {
	$visited[$startID] = true;
	if($startID==$endID) {
		$GLOBALS['pathes'][] = $localPathList;
	}

	for ($i=0; $i < count($edges)-1; $i++) {
		$curEdge = explode("|",$edges[$i]); //ID,fromNodeID,toNodeID

		if ($curEdge[1] == $startID) {
			$curNode = $curEdge[2];
			if(!isset($visited[$curNode])) {
				$localPathList[] = $curNode;
				calcPathes($curNode,$endID,$visited,$localPathList,$edges);
				array_pop($localPathList);
			}
		}
	}

	$visited[$startID] = false;
}
?>