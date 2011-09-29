<?php

include("report_functions.php");
include("report_arginit.php");

//this page prints a set of charts.

if (!isset($GET->mission_id))
{
    die("No mission_id provided");
}

if (!isset($GET->selectchart))
{
    die("No selectchart array provided");
}

$mid = $GET->mission_id->int;
$title = "Charts for mission $mid";
$h2 = "Charts for <a href='/mission.php?mission_id=$mid'>mission $mid</a>";

echo "<html>
<head>
 <title>$title</title></head>
 <script type='text/javascript' src='js/swfobject.js'></script>

<body>
<h2>$h2</h2>
 <form action='?' method='get'>
  <input type='hidden' name='mission_id' value='$mid' />
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


foreach ($_GET["selectchart"] as $src)
{
    echo makeChartHtml($src, $w, $h);
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
