<?php

include("report_functions.php");
include("report_arginit.php");
include("report_dbinit.php");

// this page updates notes on a mission and returns you to that page

if (!isset($POST->mission_id))
{
    die("No mission_id provided");
}

if (!isset($POST->notes))
{
    die("No notes provided");
}

$mid = $POST->mission_id->int;
$notes = str_replace(array("\n", "\r"), array('\n', ''), $POST->notes->sql);

$q = "update mission set notes='$notes' where mission_id=$mid";

$db->query($q);


$a = a("/mission.php",
        "group_id", $POST->group_id->url,
        "mission_id", $mid);

header("Location: $a");



?>
