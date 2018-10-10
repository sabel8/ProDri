<?php
require_once("config.php");
// get the q parameter from URL
$q = $_REQUEST["q"];
$res = "";
if ($q=="nodes") {
	$query = query("
		SELECT n.ID, n.txt, n.xCord, n.yCord, n.status, ka.areaName, rp.personName, n.duration, n.RACI, p.processName
	 	FROM nodes AS n
	 	LEFT JOIN knowledge_areas AS ka
	 		ON n.knowledgeAreaID=ka.ID
		LEFT JOIN responsible_persons AS rp
			ON n.responsiblePersonID=rp.ID
		LEFT JOIN processes AS p
			ON n.processNameID=p.ID
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
	$query = query("
		SELECT n.ID, n.txt, n.status, n.duration, n.RACI, p.processName
		FROM nodes AS n
		LEFT JOIN responsible_persons AS rp
			ON n.responsiblePersonID=rp.ID 
		LEFT JOIN processes AS p
			ON n.processNameID=p.ID
		WHERE rp.personName='$personName'
		ORDER BY n.status DESC");
	confirm($query);
	while ($row = fetch_array($query)){
		$res = $res . implode(",",$row) .";";
	}
	echo $res;
}

?>