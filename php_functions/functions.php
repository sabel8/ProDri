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
?>