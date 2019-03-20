<?php

function query($sql) {
	global $connection;

	return mysqli_query($connection,$sql);
}

function confirm($result) {
	global $connection;

	if (!$result) {
		die("<br>QUERY_FAILED ".mysqli_error($connection));
	}
}

function escape_string($string) {

	global $connection;

	return mysqli_real_escape_string($connection,$string);

}

function fetch_array($result) {

	return mysqli_fetch_assoc($result);
}

//returns the array of rows of the query result
//saves boilercode copying, designed for selections!
//safe from xss
//param $queryTxt = the SQL statement itself
function getRowsOfQuery($queryTxt){
	//print_r($queryTxt."<br><br>");
	global $connection;
	$query = $connection->prepare($queryTxt);
	if(!$query){
		die("$queryTxt <br>QUERY_FAILED ".mysqli_error($connection)."<br>");
	}
	$query->execute();
	if ($query==false) {
		print_r("QUERY=\"".$queryTxt."\" ".mysqli_error($connection));
		return null;
	}
	$result = $query->get_result();
	$res="";
	while ($row = $result->fetch_assoc()){
		foreach ($row as $key => $value) {
			$res .= htmlspecialchars($value)."|";
		}
		$res=rtrim($res,"|")."~";
	}
	$array= explode("~",$res);
	return $array;
}

//returns the header of the table, and opened body tag
//must be closed with </tbody></table></div> !
//param $arr = array of the columns' name
function getTableHeader($arr,$id){
	$innerhtml = '<div class="table-responsive">
			<table id="'.$id.'" class="table table-bordered table-hover">
				<thead><tr>';
	for ($i=0; $i < count($arr); $i++) { 
		$innerhtml.='<th class="text-center">'.$arr[$i].'</th>';
	}					
	$innerhtml.='</tr></thead><tbody class="text-center">';
	return $innerhtml;
}

//returns the full word of the raci value
//example: i --> Informed
function getRACItext($value) {
	$txt="";
	switch (strtolower($value)){
		case "r":
			$txt = "Responsible";
			break;
		case "a":
			$txt = "Accountable";
			break;
		case "c":
			$txt = "Consultant";
			break;
		case "i":
			$txt = "Informed";
			break;
		default:
			$txt = "ERROR";
	}
	return $txt;
}

function getStatusText($value){
	$txt="";
	/*
	-ID:0 Not yet started, no input avaiable, and should not have been started 
	-ID:1 Not yet started, no input avaiable, but should have been started 
	-ID:2 Not yet started, there is avaiable input, but should have been started 
	-ID:3 Not yet started, there is avaiable input, and should not have been started 
	-ID:4 In progress, in time 
	-ID:5 In progress, delayed, within buffer 
	-ID:6 In progress, delayed, beyond buffer 
	-ID:7 Failed (meg kellett volna, de nem sikerült és nem is fog belátható időn belül) 
	-ID:8 Withdrawn (mégsem kell)
	-ID:9 Done
	*/
	switch($value) {
		case 0:$txt="Not yet started, no input avaiable, and should not have been started";break;
		case 1:$txt="Not yet started, no input avaiable, but should have been started";break;
		case 2:$txt="Not yet started, there is avaiable input, but should have been started";break;
		case 3:$txt="Not yet started, there is avaiable input, and should not have been started";break;
		case 4:$txt="In progress, in time";break;
		case 5:$txt="In progress, delayed, within buffer";break;
		case 6:$txt="In progress, delayed, beyond buffer";break;
		case 7:$txt="Failed";break;
		case 8:$txt="Withdrawn";break;
		case 9:$txt="Done";break;
		default:$txt="ERROR";
	}
	return $txt;
}

function getNodesOfRec($recID){
	global $connection;
	$nodeQuery = $connection->query("SELECT nodeID,name,xCord,yCord,raci,abstractProcessID,description,professionID 
	FROM abstract_nodes WHERE abstractProcessID=$recID");
	$rows = array();
	while($r = mysqli_fetch_assoc($nodeQuery)) {
		$rows[] = $r;
	}
	return json_encode($rows,JSON_UNESCAPED_UNICODE);
}

function getEdgesOfRec($recID){
	global $connection;
	$edgeQuery = $connection->query("SELECT ID,fromNodeID,toNodeID FROM abstract_edges WHERE abstractProcessID=$recID");
    $rows = array();
    while($r = mysqli_fetch_assoc($edgeQuery)) {
        $rows[] = $r;
    }
	return json_encode($rows,JSON_UNESCAPED_UNICODE);
}

function getStatusName($status) {
	$res="";
	switch($status) {
		case 0:
			$res.="Not yet submitted.";
			break;
		case 1:
			$res.="Under review...";
			break;
		case 2:
			$res.="Accepted! Thank you.";
			break;
		case 3:
			$res.="Refused. Sorry.";
			break;
	}
	return $res;
}


//returns the color class of the row
//according to its status
function getColorClass($status) {
	switch ($status) {
		case '0':
			$colorClass="warning";
			break;
		case '1':
			$colorClass="info";
			break;
		case '2':
			$colorClass="success";
			break;
		case '3':
			$colorClass="danger";
			break;
		default:
			print_r("ERROR at functions/getColorClass function!");
			$colorClass="";
	}
	return $colorClass;
}

//return an option element with the raci character in value
//and the full raci word in title
function getRACIoption($cell,$raci){
	$selected = strtolower($cell)==$raci?" selected":"";
	return "<option value='".$raci."'".$selected.">".getRACItext($raci)."</option>";
}

/**
 * duration - seconds INT
 * canBeStarted - unix timestamp INT
 */
function setEventForTask($duration,$canBeStarted,$userID,$realNodeID,$printInfo,$setPlannedDate) {
	global $connection;
	$eventCounter=0;
	$remainingDur = $duration;
	$nodeTitle = getRowsOfQuery("SELECT txt FROM nodes WHERE ID=$realNodeID")[0];
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
			if ($setPlannedDate==true) {
				//set plannedFinish of the node
				$setPlannedFinishSQL = "UPDATE nodes SET plannedFinish='$endOfTask' WHERE ID=$realNodeID";
				if (!mysqli_query($connection, $setPlannedFinishSQL)) {
					echo "Error: " . $setPlannedFinishSQL . "<br>" . mysqli_error($connection);
				}
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

/* SCHEDULING ALGORITHMS */

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

function calculateCriticalPath($processID,$startTime,$printInfo) {
	$GLOBALS['pathes'] = array();
	$curPath = [];
	$durations = [];
	//add START node
	$startingNodeID = getRowsOfQuery("SELECT nodeID FROM nodes WHERE processID=$processID AND txt='START'")[0];
	$finishNodeID = getRowsOfQuery("SELECT nodeID FROM nodes WHERE processID=$processID AND txt='FINISH'")[0];
	$curPath[] = $startingNodeID;
	$edges = getRowsOfQuery("SELECT ID,fromNodeID,toNodeID FROM edges WHERE processID=$processID");
	calcPathes($startingNodeID,$finishNodeID,[],$curPath,$edges);
	$pathes = $GLOBALS['pathes'];

	//for calculating critical path with the user-defined calendar
	//iterating through the pathes
	for ($i=0; $i < count($pathes); $i++) { 
		$curPath = $pathes[$i];
		$curStart= $startTime;
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
		$durations[] = (int) ($curDur-$startTime);
		//echo date('Y-m-d H:i:s',$curDur)."<br>";
	}

	//printing the pathes and their durations
	if ($printInfo==true) {
		for ($i=0; $i < count($pathes); $i++) { 
			echo implode(" -> ",$pathes[$i]) ." duration = ".$durations[$i]."<br>";
		}
	}

	$maxIndex = array_keys($durations,max($durations))[0];
	$crit = implode(" -> ",$pathes[$maxIndex]);
	//echo "the critical path is: ".$crit." with duration of ".$durations[$maxIndex];
	return $pathes[$maxIndex];
	
}

function calcPathes($startID,$endID,$visited,$localPathList,$edges) {
	global $pathes;
	$visited[$startID] = true;
	if($startID==$endID) {
		$pathes[] = $localPathList;
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
	array_pop($localPathList);
}
?>