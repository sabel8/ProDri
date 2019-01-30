<?php
require_once("../config.php");

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

//getting the number of the nodes in the process
$nodeCounter = getRowsOfQuery("SELECT count(ID) FROM nodes WHERE processID=$processID")[0];

//array for counting how many node have been put into calendar
$doneNodeIDs = [];


$pathes=[];
$critPath = calculateCriticalPath($processID);
array_pop($critPath);
print_r($critPath);echo" : critpath<br>";
fillInTaskEvent(null);

function fillInTaskEvent($curEdges){
	global $processID, $doneNodeIDs, $critPath;
	//the node with the text 'START'
	if (count($doneNodeIDs) == 0) {
		$startNodeID = getRowsOfQuery("SELECT nodeID FROM nodes WHERE txt='START' AND processID=$processID")[0];
		$curEdges = getRowsOfQuery("SELECT toNodeID FROM edges WHERE processID=$processID AND fromNodeID=$startNodeID");
		echo count($doneNodeIDs).". START";echo"<br>";
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
					//todo PUT INTO CALENDAR
					echo count($doneNodeIDs).". ";print_r($curTask[0]);echo "<br>";
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
				echo count($doneNodeIDs).". ";print_r($curTask[0]);echo " <br>";
				scheduleTask($nodeID);
				$doneNodeIDs[] = $nodeID;
				fillInTaskEvent(getRowsOfQuery("SELECT toNodeID FROM edges WHERE processID=$processID AND fromNodeID=$nodeID"));
			}
		}


		/*for ($j=1; $j < 4; $j++) { 
			$freebegin=getFirstAvaliableTimeslot(1,$time);
			echo "<b>".$freebegin." = $j. jó</b><br>";
			$time = getEndOfFreeTimeslot(1,$freebegin);
			echo "<b>".$time." = $j. vég</b><br><br>";
		}*/
	}

}


function scheduleTask($taskID){
	global $connection, $processID;
	$realNodeID = (int) getRowsOfQuery("SELECT ID FROM nodes WHERE nodeID=$taskID AND processID=$processID")[0];
	$duration = (int) getRowsOfQuery("SELECT duration FROM nodes WHERE ID=$realNodeID");
	/* $sql = "INSERT INTO unavaliable_timeslots (title,nodeID,personID,startTime,duration) VALUES ('Work',$realNodeID,
	(SELECT responsiblePersonID FROM nodes WHERE ID=$realNodeID),$startTime)"; */
	if (true) {

	}
}


//timeformat: 2019-01-12 03:03:59
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
	JOIN `timeslot_repetitions` trw ON trw.repetition_type = 'weekday' AND trw.timeslotID=events.ID
	WHERE weekday('$time')=IF(repetition_value=0,6,repetition_value-1) AND TIME(events.startTime)<=TIME('$time') 
		AND ADDTIME(TIME(events.startTime),events.duration)>TIME('$time') #starts not after $time and ends after $time");
	//if yes...
	if(count($regular)>1){
		$start = explode("|",$regular[0])[0];
		$time = explode("|",$regular[0])[1];
		//echo "start: ".$start." end: ".$time."<br>";
		//echo $time." volt regular, ez a vége<br>";
		//inspects if there is any free exception event interrupting the regular event
		$exception =getRowsOfQuery("SELECT startTime,endTime,'exception in regular event' FROM timeslot_exceptions 
			WHERE startTime>='$start' AND endTime<'$time' AND avaliable=True AND personID=$userID");
		//print_r($exception);echo "<br>";
		//if there is, return it's startDateTime
		if(count($exception)>1) {
			//echo "the interrupting exception won<br>";
			return explode("|",$exception[0])[0];
		} 
		return getFirstAvaliableTimeslot($userID,$time);
	} 
	//print_r($exceptions);echo "<br>";print_r($regular);echo "<br><br>";
	//echo "the end of the exception won<br>";
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
		WHERE startTime>'$time' AND personID=$userID AND avaliable=false");

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
	global $pathes;
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

	//DONT DELETE
	//for calculating critical path with the graph defined durations
	/*for ($i=0; $i < count($pathes); $i++) { 
		$curIDs = $pathes[$i];
		$duration = 0;
		for ($j=0; $j < count($curIDs); $j++) {
			$duration += intval(getRowsOfQuery("SELECT duration FROM nodes WHERE processID=$processID AND nodeID=".$curIDs[$j])[0]);
		}
		$durations[] = $duration;
	}*/

	//printing the pathes and their durations
	for ($i=0; $i < count($pathes); $i++) { 
		echo implode(" -> ",$pathes[$i]) ."<br>";
		echo $durations[$i]."<br>";
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