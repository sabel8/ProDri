<?php
require_once(__DIR__."/../config.php");
$printInfo=0;

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
$critPath = calculateCriticalPath($processID,$GLOBALS['startTime'],$printInfo);
array_pop($critPath);
if($printInfo==true){print_r($critPath);}
if($printInfo==true){echo " : critpath<br>";}
fillInTaskEvent(null);

//setting the plannedFinish of the process
$query = $connection->prepare("UPDATE processes SET plannedFinish=(SELECT addtime(startTime,duration) as end
	FROM unavaliable_timeslots WHERE nodeID IN (SELECT ID FROM nodes WHERE processID=?) 
	ORDER BY end DESC LIMIT 1), actualFinish=plannedFinish WHERE ID=?");
$query->bind_param("ii",$processID,$processID);
if ($query->execute()) {
	if ($printInfo==true) echo "Yay!";
} else {
	if ($printInfo==true) echo "Error while updating process' plannedFinish!";
}

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
	$duration = (int) getRowsOfQuery("SELECT duration FROM nodes WHERE ID=$realNodeID")[0];

	$canBeStarted = (int) $startTime;
	//goes through the predecessor nodes
	//finding the first occasion when the node can be started
	//(end timestamp of the predecessor which finishes last)
	for ($i=0; $i < count($predecessors)-1; $i++) {
		$curPredecID = $predecessors[$i];
		//if the predecessor is start then continue
		if(count(getRowsOfQuery("SELECT ID FROM nodes WHERE txt='START' AND nodeID=$curPredecID")) > 1){
			continue;
		}
		$lastTime = getRowsOfQuery("SELECT unix_timestamp(startTime)+time_to_sec(duration) as endTime FROM unavaliable_timeslots WHERE
		nodeID=(SELECT ID FROM nodes WHERE processID=$processID AND nodeID=$curPredecID) ORDER BY endTime DESC");
		if ((int) $lastTime[0] > $canBeStarted) {
			$canBeStarted = $lastTime[0];
		}
	}
	//set plannedStart of the node
	$setPlannedStartSQL = "UPDATE nodes SET plannedStart=\"".
		getFirstAvaliableTimeslot($userID,date('Y-m-d H:i:s', $canBeStarted))."\" WHERE ID=$realNodeID";
	if (!mysqli_query($connection, $setPlannedStartSQL)) {
		echo "Error: " . $setPlannedStartSQL . "<br>" . mysqli_error($connection);
	}
	setEventForTask($duration,$canBeStarted,$userID,$realNodeID,$printInfo,true);
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
?>