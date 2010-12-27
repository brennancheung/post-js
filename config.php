<?php

$hostname = "localhost";
$database = "vm";
$username = "root";
$password = "";

$dbConn = mysql_connect($hostname, $username, $password) or die(mysql_error());
mysql_select_db($database, $dbConn);

?>