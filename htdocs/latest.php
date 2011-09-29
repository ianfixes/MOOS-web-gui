<?php

include("report_dbinit.php");
include("report_functions.php");

//this file is a simple redirect to the page for the latest mission

$latestmission = $dbo->mission->mission_id->First(
    array(),
    array("mission_id" => "desc"));

$r = $dbo->mission->ID($latestmission);
$thedate = date("l M j, Y", strtotime($r->date));

$a = a("/mission.php",
        "group_id", "{$r->date},{$r->location},{$r->vehicle_name}",
        "mission_id", $latestmission);

header("Location: $a");

?>
