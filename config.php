<?php

//output buffering
/*ob_start();

session_start();*/

defined("DS") ? null : define("DS", DIRECTORY_SEPARATOR);

defined("TEMPLATE") ? null : define("TEMPLATE",__DIR__.DS."templates");


//change it!!!
defined("DB_HOST") ? null : define("DB_HOST", "localhost");

defined("DB_USER") ? null : define("DB_USER", "root");

defined("DB_PASS") ? null : define("DB_PASS", "");

defined("DB_NAME") ? null : define("DB_NAME", "graphs_db");

$connection = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);

require_once("functions.php");


query("set names 'utf8'");



 ?>