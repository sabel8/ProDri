<?php

function query($sql) {
	global $connection;

	return mysqli_query($connection,$sql);
}

function confirm($result) {
	global $connection;

	if (!$result) {
		die("QUERY_FAILED ".mysqli_error($connection));
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
//param $queryTxt = the SQL statement itself
function getRowsOfQuery($queryTxt){
	//print_r($queryTxt."<br><br>");
	global $connection;
	$query = $connection->prepare($queryTxt);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$res="";
	while ($row = $result->fetch_assoc()){
		$res = $res . implode("|",$row) .";";
	}
	return explode(";", $res);
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
	$innerhtml.='</tr></thead><tbody>';
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
	-ID:0 Not yet started, no input avaiable, but should have been started 
	-ID:1 Not yet started, no input avaiable, but should not have been started 
	-ID:2 Not yet started, there is avaiable input, but should have been started 
	-ID:3 Not yet started, there is avaiable input, but should not have been started 
	-ID:4 In progress, in time 
	-ID:5 In progress, delayed, within buffer 
	-ID:6 In progress, delayed, beyond buffer 
	-ID:7 Failed (meg kellett volna, de nem sikerült és nem is fog belátható időn belül) 
	-ID:8 Withdrawn (mégsem kell) 
	-ID:9 Done 
	*/
	switch($value) {
		case 0:$txt="Not yet started, no input avaiable, but should have been started";break;
		case 1:$txt="Not yet started, no input avaiable, but should not have been started";break;
		case 2:$txt="Not yet started, there is avaiable input, but should have been started";break;
		case 3:$txt="Not yet started, there is avaiable input, but should not have been started";break;
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
	//setting up the stringified node array
	$nodesOfProc=getRowsOfQuery("SELECT nodeID,name,xCord,yCord,raci,r.forProcessID,description 
		FROM recommended_nodes n,recommendations r WHERE n.recommendationID=r.ID AND n.recommendationID=".$recID);
	$nodeString="";
	for($n=0;$n<count($nodesOfProc)-1;$n++){
		$curNode = explode("|",$nodesOfProc[$n]);
		$nodeString.="[";
		for($e=0;$e<count($curNode);$e++){
			$nodeString.='\''.$curNode[$e].'\',';
		}
		//cut down last colon
		$nodeString = substr($nodeString,0,-1)."],";
	}
	//cut down last colon
	return substr($nodeString,0,-1);
}

function getEdgesOfRec($recID){
	//setting up the stringified edge array
	$edgesOfProc=getRowsOfQuery("SELECT ID,fromNodeID,toNodeID FROM recommended_edges e
		WHERE recommendationID=".$recID);
	$edgeString="";
	for($n=0;$n<count($edgesOfProc)-1;$n++){
		$curEdge = explode("|",$edgesOfProc[$n]);
		$edgeString.="[";
		for($e=0;$e<count($curEdge);$e++){
			$edgeString.='\''.$curEdge[$e].'\',';
		}
		//cut down last colon
		$edgeString = substr($edgeString,0,-1)."],";
	}
	//cut down last colon
	return substr($edgeString,0,-1);

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
	}
	return $colorClass;
}

//returns the html of a recommendation row
//on click the preview shows up
//param $cells = array of the values in table cells
function getTableRecordRow($cells,$tableID) {
	$innerhtml="";
	global $connection;
	//query for getting the nodes of the recommendation
	$query = $connection->prepare("
		SELECT r.nodeID,r.name,r.xCord,r.yCord,r.status,r.professionID,\"\",r.duration,r.raci,re.forProcessID,re.forProcessID 
		FROM recommended_nodes r, recommendations re WHERE r.recommendationID=?");
	$query->bind_param('i',$cells[0]);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$nodes="";
	while ($row = $result->fetch_assoc()){
		$nodes .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$nodes=rtrim($nodes,",");

	//query for getting the edges of the recommendation
	$query = $connection->prepare("
		SELECT r.ID,r.fromNodeID,r.toNodeID
		FROM recommended_edges r WHERE r.recommendationID=?");
	$query->bind_param('i',$cells[0]);
	confirm($query);
	$query->execute();
	$result = $query->get_result();
	$edges="";
	while ($row = $result->fetch_assoc()){
		$edges .= "['".implode("','",$row)."'],";
	}
	//removing the last unnecessary colon
	$edges=rtrim($edges,",");

	$colorClass=getColorClass($cells[2]);

	$innerhtml.="<tr class='{$colorClass}' style='cursor:pointer' 
		onclick=\"viewRecommendation({$cells[0]},[{$nodes}],[{$edges}],'$tableID')\">";
		//viewRec2([$nodes],[$edges],{$cells[0]},{$cells[2]},false,true,'".$tableID."')
	for ($n=0;$n < count($cells)-1;$n++){
		$innerhtml.='<td class="text-center">';
		switch($n){
			case 2:
				$innerhtml.=getStatusName($cells[2]);
				break;
			default:
				$innerhtml.=$cells[$n];
		}
		$innerhtml.="</td>";
	}
	return $innerhtml;
}

//return an option element with the raci character in value
//and the full raci word in title
function getRACIoption($cell,$raci){
	$selected = strtolower($cell)==$raci?" selected":"";
	return "<option value='".$raci."'".$selected.">".getRACItext($raci)."</option>";
}
?>