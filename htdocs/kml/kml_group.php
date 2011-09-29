<?php

//generate a kml folder full of individual missions

include("report_dbinit.php");
include("report_arginit.php");

include("kml_head.php");

if (!isset($GET->group_id))
{
    die("group_id not supplied");
}


echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n\n";
echo '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";

echo " <Folder>
        <name>Missions</name>
        <open>1</open>

        <Style>
         <ListStyle>
          <listItemType>checkOffOnly</listItemType>
         </ListStyle>
        </Style>
";

$r = explode(",", $GET->group_id->string);
$s = $_SERVER["SERVER_NAME"];
$thedate = date("l M j, Y", strtotime($r[0]));

echo "  <name><![CDATA[$thedate - {$r[2]} at {$r[1]}]]></name>\n";
//include("style.php");


$where = array("date" => "= '{$r[0]}'",
                "location" => "= '{$r[1]}'",
                "vehicle_name" => "= '{$r[2]}'");
$order = array("time" => "desc"); 

foreach ($dbo->mission->Records($where, $order) as $rec)
{
    $m = $rec->mission_id;
    $l = trim($rec->label);
    echo "\n" . 
    "  <NetworkLink>\n" . 
    "   <name>Mission $m - $l</name>\n" .
    "   <visibility>0</visibility>\n" .
    "   <open>1</open>\n" . 
    "   <flyToView>0</flyToView>\n" . 
    "   <refreshVisibility>0</refreshVisibility>\n" .
    "   <Link>\n" . 
    "    <href>http://$s/kml/kml_mission.php?mission_id=$m</href>\n" . 
    "    <refreshMode>onExpire</refreshMode>\n" . 
    "    <refreshInterval>3600</refreshInterval>\n" . 
    "    <viewRefreshMode>never</viewRefreshMode>\n" . 
    "   </Link>\n" .
    "  </NetworkLink>\n";

}


echo " </Folder>\n";
echo "</kml>\n";

?>
