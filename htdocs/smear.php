<?php

include("report_functions.php");
include("report_arginit.php");
include("report_dbinit.php");

// a chart "smear" displays the same chart for many missions.  this can be
// useful for deciding when something went seriously wrong

if (!isset($GET->charttype))
{
    die("No selectchart array provided");
}

$mid = $GET->mission_id->int;
$m = $dbo->mission->ID($mid);

$missions = $dbo->mission->Records(
    array("date" => "= '{$m->date}'",
            "location" => "= '{$m->location}'",
            "vehicle_name" => "= '{$m->vehicle_name}'",
            "label" => "= '{$m->label}'"),
    array("mission_id" => "asc"));

$title = "Chart Smear";
$group_id = "{$m->date},{$m->location},{$m->vehicle_name}";
$h2 = "Chart smear for {$m->date} - "
    . "<a href='missiongroup.php?group_id=$group_id'>{$m->vehicle_name} "
    . "at {$m->location}</a>";
   
$h3 = "Missions called '{$m->label}'"; 

echo "<html>
<head>
 <title>$title</title></head>
 <script type='text/javascript' src='js/swfobject.js'></script>

<body>
<h2>$h2</h2>
<h3>$h3</h3>
 <form action='?' method='get'>
  <input type='hidden' name='charttype' value='{$GET->charttype->string}' />
";

$w = 850;
$h = 650;

if (isset($GET->width))
{
    $w = $GET->width->int;
}

if (isset($GET->height))
{
    $h = $GET->height->int;
}


foreach ($missions as $m)
{
    $src = str_replace("mission_id", "ignore_id", $GET->charttype->string);
    $src = "{$src}mission_id={$m->mission_id}";

    echo makeChartHtml($src, $w, $h);
//    echo makeClickChartHtml("Mission {$m->mission_id} {$m->label}", 
//                    "icon_chart.png", $src, $w, $h, false);
    echo "<input type='hidden' name='selectchart[]' value='$src' />\n";
    echo "<br /><br />\n";
}


echo "
<hr>
Resize charts to 
 <input type='text' name='width' value='$w' size='5' /> by
 <input type='text' name='height' value='$h' size='5' />
 <input type='submit' value='resize' />

 ";



echo "</form></body></html>";

?>
