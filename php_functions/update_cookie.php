<?php
//cant get it to work
require_once("../config.php");
setcookie("auth",$_GET["auth"]);
//$_COOKIE["auth"] = $_GET["auth"];
echo $_COOKIE["auth"];
die("omg");
?>