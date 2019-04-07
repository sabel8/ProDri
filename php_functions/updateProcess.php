<?php
$printInfo=0;
require_once(__DIR__."/../config.php");
if (isset($_GET['processID'])) {
	$processID=$_GET['processID'];
} else {
	die("ProcessID shall be given in GET!");
}

//checks if the process is instantiated
//if not, don't run the algorithm
if (count(getRowsOfQuery("SELECT ID FROM nodes WHERE processID=$processID and (durationStatus IS NULL OR NOT durationStatus = 1)
 AND NOT txt IN ('START','FINISH')"))>1) {
	return;
}

global $connection;
$nodeIDs = getRowsOfQuery("SELECT ID,nodeID FROM nodes WHERE processID = $processID AND NOT txt IN ('START','FINISH')");

//go through all the nodes
for ($i=0; $i < count($nodeIDs)-1; $i++) {
	$curNodeID = explode("|",$nodeIDs[$i])[0];
	$curNodeAbstractID = explode("|",$nodeIDs[$i])[1];
	$predecessors = getRowsOfQuery("SELECT ID FROM nodes WHERE processID=$processID
		AND nodeID IN (SELECT fromNodeID FROM edges WHERE toNodeID=$curNodeAbstractID AND processID=$processID)");
	$inputFound = false;
	//go through all the predecessors' deliverable folders to check if there is any input avaliable
	for ($j=0; $j < count($predecessors)-1; $j++) {
		$dir = __DIR__."/../deliverables/$processID/{$predecessors[$j]}"; //lehet itt hiba van és egy mappával feljebb kell menni
		if (!file_exists($dir) or !is_dir($dir)) {continue;}
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
			  if ($entry != "." && $entry != "..") {
				if ($printInfo==true) echo $entry." bent.<br>";
				$inputFound = true;
				break;
			  }
			}
			closedir($handle);
		}
		if ($inputFound == true) {break;}
	}
	$taskReserve = intval(getRowsOfQuery("SELECT taskReserve FROM processes WHERE ID=$processID")[0]);
	$inputFound= ($inputFound==true?1:0);
	$query = $connection->prepare("UPDATE nodes SET status = 
	CASE 
		WHEN plannedStart IS NULL THEN status
		WHEN actualFinish IS NOT NULL THEN 9
		WHEN actualStart IS NULL AND $inputFound=FALSE AND plannedStart>now() THEN 0
		WHEN actualStart IS NULL AND $inputFound=FALSE AND plannedStart<=now() THEN 1
		WHEN actualStart IS NULL AND $inputFound=TRUE AND plannedStart<=now() THEN 2
		WHEN actualStart IS NULL AND $inputFound=TRUE AND plannedStart>now() THEN 3
		WHEN NOT actualStart IS NULL AND actualFinish IS NULL AND now()-actualStart<duration THEN 4
		WHEN NOT actualStart IS NULL AND actualFinish IS NULL AND now()-actualStart>duration
			AND (?/100+1)*duration >= now()-actualStart THEN 5
		WHEN NOT actualStart IS NULL AND actualFinish IS NULL AND now()-actualStart>duration
			AND (?/100+1)*duration < now()-actualStart THEN 6
	END WHERE ID=?");
	if ($query === false) {
		if ($printInfo==true) {
			echo "Lista: ";
			print_r($connection->error);
		}
		die();
	}
	$query->bind_param("iii",$taskReserve,$taskReserve,$curNodeID);
	if ($query->execute()) {
		if ($printInfo==true) echo "Yay!";
	} else {
		if ($printInfo==true) echo "Error while updating tasks' status!";
	}

}

//in progress, in time
$inProgressInTimeTasks=getRowsOfQuery("SELECT ID,nodeID,duration,responsiblePersonID
	FROM nodes WHERE processID=$processID AND status=4");
for ($i=0; $i < count($inProgressInTimeTasks)-1; $i++) { 
	$curNode = explode("|",$inProgressInTimeTasks[$i]);
	$nodeID = $curNode[0];
	$nodeAbstractID = $curNode[1];
	$duration = $curNode[2];
	$curUserID = $curNode[3];
	//select the last finishing event record of the predecessors of the task
	$canBeStarted=getRowsOfQuery("SELECT UNIX_TIMESTAMP(ADDTIME(startTime,duration)) as end
		FROM unavaliable_timeslots WHERE nodeID IN (SELECT ID FROM nodes WHERE processID=$processID AND 
		nodeID IN (SELECT fromNodeID FROM edges WHERE toNodeID=$nodeAbstractID AND processID=$processID))
		ORDER BY end DESC LIMIT 1 ")[0];
	$canBeStarted = $canBeStarted==""?time():$canBeStarted;
	setEventForTask($duration,$canBeStarted,$curUserID,$nodeID,$printInfo,false);
}

//in progress, delayed
$inProgressDelayedTasks=getRowsOfQuery("SELECT ID,nodeID,duration,responsiblePersonID,unix_timestamp(actualStart)
	FROM nodes WHERE processID=$processID AND status IN (5,6)");
for ($i=0; $i < count($inProgressDelayedTasks)-1; $i++) { 
	$curNode = explode("|",$inProgressDelayedTasks[$i]);
	$realNodeID = $curNode[0];
	$nodeAbstractID = $curNode[1];
	$duration = $curNode[2];
	$curUserID = $curNode[3];
	$canBeStarted = $curNode[4];
	
	$eventCounter=0;
	$endOfFreeTime=$canBeStarted;
	$nodeTitle = getRowsOfQuery("SELECT txt FROM nodes WHERE ID=$realNodeID")[0];
	while (strtotime($endOfFreeTime) < time()){
		$eventCounter++;
		$freeStart = getFirstAvaliableTimeslot($curUserID,date('Y-m-d H:i:s', $canBeStarted));
		if (strtotime($freeStart) > time()) {
			break;
		}
		$endOfFreeTime = getEndOfFreeTimeslot($curUserID,$freeStart);
		if (strtotime($endOfFreeTime) >= time()) {
			$partNo=$eventCounter>1?" (part ". $eventCounter .")":"";
			$curDur= time() - strtotime($freeStart);
		} else {
			$partNo=" (part ". $eventCounter .")";
			$curDur = strtotime($endOfFreeTime) - strtotime($freeStart);
		}
		$endOfTask = date('Y-m-d H:i:s',strtotime($freeStart) + $curDur);
		$canBeStarted = strtotime($endOfFreeTime);

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

//tasks not yet started
$futureTasks=getRowsOfQuery("SELECT nodeID FROM nodes WHERE processID=$processID AND status IN (0,1,2,3) AND NOT txt='FINISH'");
//critical path for the process
$critPath=calculateCriticalPath($processID,time(),$printInfo);
for ($i=0;$i<count($critPath);$i++) {
	$value=$critPath[$i];
	if (in_array($value,$futureTasks)){
		$curNode = explode("|",getRowsOfQuery("SELECT ID,nodeID,duration,responsiblePersonID FROM nodes
			WHERE nodeID=$value AND processID=$processID")[0]);
		$nodeID = $curNode[0];
		$nodeAbstractID = $curNode[1];
		$duration = $curNode[2];
		$curUserID = $curNode[3];
		//select the last finishing event record of the predecessors of the task
		$canBeStarted=getRowsOfQuery("SELECT UNIX_TIMESTAMP(ADDTIME(startTime,duration)) as end
			FROM unavaliable_timeslots WHERE nodeID IN (SELECT ID FROM nodes WHERE processID=$processID AND 
			nodeID IN (SELECT fromNodeID FROM edges WHERE toNodeID=$nodeAbstractID AND processID=$processID))
			ORDER BY end DESC LIMIT 1")[0];
		if ($canBeStarted < time() or $canBeStarted=="") {
			$canBeStarted=time();			
		}
		setEventForTask($duration,$canBeStarted,$curUserID,$nodeID,$printInfo,false);
	}
}


//setting the actualFinish of the process
$projectReserve=getRowsOfQuery("SELECT reserve FROM projects
	WHERE ID=(SELECT projectID FROM processes WHERE ID=$processID)")[0];
$query = $connection->prepare("UPDATE processes SET actualFinish=(SELECT addtime(startTime,duration) as end
	FROM unavaliable_timeslots WHERE nodeID IN (SELECT ID FROM nodes WHERE processID=?) 
	ORDER BY end DESC LIMIT 1),
	status = CASE 
		WHEN plannedFinish-startTime > actualFinish-startTime THEN 1 /*before time*/
		WHEN plannedFinish-startTime = actualFinish-startTime THEN 2 /*in time*/
		WHEN (plannedFinish-startTime)*((?/100)+1) > actualFinish-startTime THEN 3 /*delayed, in buffer*/
		WHEN (plannedFinish-startTime)*((?/100)+1) < actualFinish-startTime THEN 4 /*delayed, outta buffer*/
		END WHERE ID=?");
$query->bind_param("iiii",$processID,$projectReserve,$projectReserve,$processID);
if ($query->execute()) {
	if ($printInfo==true) echo "Yay!";
} else {
	if ($printInfo==true) echo "Error while updating process' plannedFinish!";
}

//setting the actualDuration of the nodes
for ($i=0; $i < count($nodeIDs)-1; $i++) {
	$curNodeID = explode("|",$nodeIDs[$i])[0];
	$query = $connection->prepare("UPDATE nodes SET actualDuration=(SELECT SUM(TIME_TO_SEC(duration)) 
		FROM unavaliable_timeslots WHERE nodeID=?) WHERE ID=?");
	$query->bind_param("ii",$curNodeID,$curNodeID);
	if ($query->execute()) {
		if ($printInfo==true) echo "ezislefutdejo!$curNodeID ";
	} else {
		if ($printInfo==true) echo "Error while updating tasks' actualDuration!";
	}
}

?>