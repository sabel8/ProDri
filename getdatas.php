<?php
require_once("config.php");
// get the q parameter from URL
$q = $_REQUEST["q"];
$res = "";
if ($q=="nodes") {
	$query = query("
		SELECT n.ID, n.txt, n.xCord, n.yCord, n.status, professionName, personName, n.duration, n.RACI, p.processName, projectName
	 	FROM nodes AS n
	 	LEFT JOIN professions AS prof
	 		ON n.professionID=prof.ID
		LEFT JOIN persons AS pers
			ON n.responsiblePersonID=pers.ID
		LEFT JOIN processes AS p
			ON n.processNameID=p.ID
		LEFT JOIN projects
			ON p.projectID=projects.ID
		");
	confirm($query);
	while ($row = fetch_array($query)){
		$res = $res . implode("|",$row) .";\n";
	}
	echo $res;




} else if ($q=="edges") {
	$query = query("SELECT * FROM edges");
	confirm($query);
	while ($row = fetch_array($query)){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;




} else if ($q=="tasklist") {
	$personName = $_REQUEST["n"];
	global $connection;
	$query = $connection->prepare("
		SELECT n.ID, n.txt, n.status, n.duration, n.RACI, p.processName, projectName
		FROM nodes AS n
		LEFT JOIN persons AS rp
			ON n.responsiblePersonID=rp.ID 
		LEFT JOIN processes AS p
			ON n.processNameID=p.ID
		LEFT JOIN projects
			ON p.projectID=projects.ID
		WHERE rp.personName=?
		ORDER BY n.status DESC");
	confirm($query);
	$query->bind_param('s',$personName);
	$query->execute();
	$result = $query->get_result();
	while ($row = $result->fetch_assoc()){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;



} else if ($q == "edittables") {
	//getting the selected list item
	$curLi = $_REQUEST["t"];

	//matching the table name with the list item
	$curLi = substr($curLi, 0,-8);

	global $connection;
	
	//getting the table column names
	$query = $connection->prepare("
		SELECT column_name
		FROM information_schema.columns
		WHERE table_name='{$curLi}'");
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	while ($row = $result->fetch_assoc()){
		$res = $res . implode(",",$row) .",";
	}
	$res=substr($res, 0,-1).";";

	//getting the table records
	$query = $connection->prepare("SELECT * FROM {$curLi}");
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	while ($row = $result->fetch_assoc()){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;


} else if ($q == "getprojects") {

	$query = query("SELECT ID, projectName FROM projects");
	confirm($query);
	while ($row = fetch_array($query)){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;


} else if ($q == "getprofessions") {
	$query = query("SELECT * FROM professions");
	confirm($query);
	while ($row = fetch_array($query)){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;


} else if($q=="getdeltypes") {
	$query = query("SELECT * FROM deliverable_types");
	confirm($query);
	while ($row = fetch_array($query)){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;
}

?>