<?php

//generate a kml folder for each deployed location in the AUV's db

include("report_dbinit.php");
include("report_arginit.php");

include("kml_head.php");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";

echo " <Folder>\n";

echo "  <name><![CDATA[MOOS Mission Groups]]></name>\n";
echo "  <visibility>0</visibility>\n"; 
echo "  <open>0</open>\n"; 
echo "  <description>List of all MOOS mission groups</description>\n"; 

$q = "
    select date, 
        location, 
        vehicle_name, 
        max(mission_id) maxmission
    from mission
    group by date, location, vehicle_name
    order by maxmission desc
";

$s = $_SERVER["SERVER_NAME"];
$rs = $db->query($q);

while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $thedate = date("l M j, Y", strtotime($r->date));

    $gid = "{$r->date},{$r->location},{$r->vehicle_name}";

    $name = "$thedate - {$r->vehicle_name} at {$r->location}";

    echo "\n" . 
    "  <NetworkLink>\n" . 
    "   <name>$name</name>\n" .
//    "   <description>$name</description>\n" .
    "   <visibility>0</visibility>\n" .
    "   <open>0</open>\n" . 
    "   <refreshVisibility>0</refreshVisibility>\n" .
    "   <flyToView>0</flyToView>\n" . 
    "   <Link>\n" . 
    "    <href>http://$s/kml/kml_group.php?group_id=$gid</href>\n" . 
    "    <refreshMode>onExpire</refreshMode>\n" . 
    "    <refreshInterval>3600</refreshInterval>\n" . 
    "    <viewRefreshMode>never</viewRefreshMode>\n" . 
    "   </Link>\n" .
    "  </NetworkLink>\n";

}


echo " </Folder>\n";
echo "</kml>\n";

?>
