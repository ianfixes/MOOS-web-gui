<?php

//generate KML to represent a mission

include("report_dbinit.php");
include("report_arginit.php");

include("kml_head.php");

if (!isset($GET->mission_id))
{
    die("mission_id not supplied");
}


echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";

echo " <Document>\n";

$m = $dbo->mission->ID($GET->mission_id->int);

echo "  <name><![CDATA[Mission {$m->mission_id} - {$m->label} ]]></name>\n";

echo " <Placemark>\n";
echo "  <name>{$m->mission_id} Origin</name>\n";
echo "   <Point>\n";
echo "    <coordinates>{$m->origin_longitude},{$m->origin_latitude}</coordinates>\n";
echo "   </Point>\n";
echo " </Placemark>\n";

include("style.php");

echo "\n" . 
"  <NetworkLink>\n" . 
"   <name>NAV</name>\n" .
"   <visibility>1</visibility>\n" .
"   <open>1</open>\n" . 
"   <flyToView>0</flyToView>\n" . 
"   <refreshVisibility>1</refreshVisibility>\n" .
"   <Link>\n" . 
"    <href>http://$s/kml/kml_nav.php?mission_id={$m->mission_id}</href>\n" . 
"    <refreshMode>onExpire</refreshMode>\n" . 
"    <refreshInterval>3600</refreshInterval>\n" . 
"    <viewRefreshMode>never</viewRefreshMode>\n" . 
"   </Link>\n" .
"  </NetworkLink>\n";

echo "\n" . 
"  <NetworkLink>\n" . 
"   <name>WAY</name>\n" .
"   <visibility>0</visibility>\n" .
"   <open>1</open>\n" . 
"   <flyToView>0</flyToView>\n" . 
"   <refreshVisibility>0</refreshVisibility>\n" .
"   <Link>\n" . 
"    <href>http://$s/kml/kml_way.php?mission_id={$m->mission_id}</href>\n" . 
"    <refreshMode>onExpire</refreshMode>\n" . 
"    <refreshInterval>3600</refreshInterval>\n" . 
"    <viewRefreshMode>never</viewRefreshMode>\n" . 
"   </Link>\n" .
"  </NetworkLink>\n";



echo " </Document>\n";
echo "</kml>\n";

?>
