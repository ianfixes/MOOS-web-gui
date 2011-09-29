<?php

require_once("db/cDbObjects.php");

//you will need to edit this line to match your username, password, and db name
$dsn = "mysql://user:pass@localhost/field_tests";

$dbo = new cDbObjects($dsn);
$db = cDb::singleton($dsn);

?>
