<?php

include("report_functions.php");
include("report_arginit.php");

//this page displays a chart ... its just a simple container

if (!isset($GET->mission_id))
{
    die("No mission_id provided");
}

if (!isset($GET->chart))
{
    die("No chart target provided");
}

if (!isset($GET->varlist))
{
    die("No varlist array provided");
}


$mid = $GET->mission_id->int;
$title = "Custom Chart for mission $mid";
$h2 = "Custom Chart for "
    . "<a href='/mission.php?mission_id=$mid'>mission $mid</a>";

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

$src = a($GET->chart->string);
echo makeChartHtml($src, $w, $h);
echo "<input type='hidden' name='chart' value='{$GET->chart->string}' />\n";
foreach ($_GET["varlist"] as $v)
{
    echo "<input type='hidden' name='varlist[]' value='$v' />\n";
}
echo "<br /><br />\n";


echo "
<hr>
Resize charts to 
 <input type='text' name='width' value='$w' size='5' /> by
 <input type='text' name='height' value='$h' size='5' />
 <input type='submit' value='resize' />

 ";



echo "</form></body></html>";

?>
