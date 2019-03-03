<?php
$printInfo=false;
require_once(__DIR__."/../config.php");
if (isset($_GET['processID'])) {
	$processID=$_GET['processID'];
} else {
	die("ProcessID shall be given in GET!");
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
		WHEN NOT actualFinish IS NULL THEN 9
		WHEN actualStart IS NULL AND $inputFound=FALSE AND plannedStart>now() THEN 0
		WHEN actualStart IS NULL AND $inputFound=FALSE AND plannedStart<=now() THEN 1
		WHEN actualStart IS NULL AND $inputFound=TRUE AND plannedStart<=now() THEN 2
		WHEN actualStart IS NULL AND $inputFound=TRUE AND plannedStart>now() THEN 3
		WHEN NOT actualStart IS NULL AND actualFinish IS NULL AND plannedFinish>now() THEN 4
		WHEN NOT actualStart IS NULL AND actualFinish IS NULL AND plannedFinish<now() AND
			(?/100+1)*duration >= now()-actualStart THEN 5
		WHEN NOT actualStart IS NULL AND actualFinish IS NULL AND plannedFinish<now() AND
			(?/100+1)*duration < now()-actualStart THEN 6
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

?>