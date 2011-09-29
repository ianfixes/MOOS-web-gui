<?php 

$title = "Mission Group";
include("report_header.php"); 

//this page prints the missions in a group as well as some aggregate data

if (!isset($GET->group_id))
{
    echo "<h1>You didn't specify a mission group ID</h1>\n";
    include("report_footer.php");
    die();
}

$r = explode(",", $GET->group_id->string);
$thedate = date("l M j, Y", strtotime($r[0]));

$a = a("missiongroup.php",
        "group_id", "{$r[0]},{$r[1]},{$r[2]}");

echo "<h2>$thedate - ";
echo "<a href=\"$a\">{$r[2]} at {$r[1]}</a></h2>\n";



echo makeClickChartHtml("Battery Voltages", "icon_battery.png",
                        a("/charts/battery_voltage.php", "mission_id", NULL), 
                        850, 650);
echo makeClickChartHtml("Battery Temperatures", "icon_battery.png",
                        a("/charts/battery_temperature.php", "mission_id", NULL), 
                        850, 650);

echo "<h3>Missions</h3>\n";

$where = array("date" => "= '{$r[0]}'",
                "location" => "= '{$r[1]}'",
                "vehicle_name" => "= '{$r[2]}'");
$order = array("time" => "desc"); 

echo "<ul class='missiongroup'>\n";
foreach ($dbo->mission->Records($where, $order) as $rec)
{
    $a = a("mission.php",
            "mission_id", $rec->mission_id);

    echo " <li>";
    echo "{$rec->time} - ";
    echo "<a href=\"$a\">{$rec->label}</a> ";
    echo "<span class='notes'>{$rec->notes}</span>\n";
    echo "</li>\n"; 
}
echo "</ul>\n";


//echo makeChartHtml(a("/charts/dotsize.php"), 350, 350);
//echo makeClickChartHtml("Battery Voltages", a("/charts/battery_voltage.php", "mission_id", 300), 850, 650);
//echo makeClickChartHtml("Battery Temperatures", a("/charts/battery_temperature.php", "mission_id", 300), 850, 650);
//echo makeChartHtml(a("/charts/battery_temperature.php", "mission_id", 300), 850, 650);
//echo makeChartHtml(a("/charts/battery_voltage.php", "mission_id", 300), 850, 650);

include("report_footer.php"); 

?>
