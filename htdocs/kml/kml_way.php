<?php

//generate kml for waypoints

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

echo "  <name>Waypoint data</name>\n";
include("style.php");

//inital data
$lat = $m->origin_latitude;
$lon = $m->origin_longitude;
$er  = cGeodesy::EarthRadius($lat);
$t0  = strtotime("{$m->date} {$m->time}");

//cache some data
$q = "
    select elapsed_time,
        varname, 
        value 
    from app_data
    where mission_id = {$m->mission_id}
      and varname in ('WAYPOINT_X', 'WAYPOINT_Y', 'NAV_ALTITUDE')
    order by elapsed_time asc,
        varname asc
";


$rs = $db->query($q);

//still in the caching process
$points = array();
$lastpoint = "";
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    switch ($r->varname)
    {
        case "WAYPOINT_X":
            $x = $r->value;
            break;

        case "WAYPOINT_Y":
            $y = $r->value;
            break;

    }

    if (isset($x) && isset($y))
    {
        $data = xy2lonlat($lat, $lon, $er, $x, $y);
        if ($lastpoint != $data)
        {
            $lastpoint = $data;
            $points[] = $data;
        }
	unset($x);
	unset($y);
    }
}

echo "  <Folder>\n";
echo "   <name>Points</name>\n";
echo "   <open>0</open>\n";

foreach ($points as $p)
{
    echo "   <Placemark>\n";
    echo "    <styleUrl>#waypointIcon</styleUrl>\n";
    echo "    <Point>\n";
    echo "     <coordinates>$p</coordinates>\n";
    echo "    </Point>\n";
    echo "   </Placemark>\n";
}

echo "  </Folder>\n";
echo " </Document>\n";
echo "</kml>\n";

?>
