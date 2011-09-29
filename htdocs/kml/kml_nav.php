<?php

//generate kml for a nav plot

include("report_dbinit.php");
include("report_arginit.php");

require_once("utils/cCachedFile.php");

function renderKmlFromCache($contents)
{
    include("kml_head.php");
//    header('Content-Encoding: gzip');
    echo $contents;
    return true;
}

if (!isset($GET->mission_id))
{
    die("mission_id not supplied");
}

$m = $dbo->mission->ID($GET->mission_id->int);


//set up caching
$CACHEDIR = "/data/tmp/kml";
$nextWeek = time() + (7 * 24 * 60 * 60);
$pdsn = MDB2::parseDSN($dsn);
$fake_filename = "kml_nav-mission_{$m->mission_id}-db_{$pdsn['database']}";

//since we are caching data from a db table (not a file, with an mtime),
// we'll use a trick for the last updated date.  if the mission is "over",
// as in "not the most recent mission", then the last updated date will be
// its start time (faster to get than it's max(elapsed_time)).  if the 
// mission is not the latest mission, we'll say that it was last updated
// a day before 
$lm = $db->getOne("select ifnull(max(mission_id), 0) from mission");
$lastupdate = strtotime("{$m->date} {$m->time}");
if ($lm <= $m->mission_id)
{
    $lastupdate - (24 * 60 * 60);
}


$cached = new cCachedFile();
$cached->setCachedir($CACHEDIR);
$cached->setFilename($fake_filename);
$cached->setLastupdate($lastupdate);
$cached->setExpiry($nextWeek);
$cached->setEtag($fake_filename);
$cached->setHashseed("$dsn::mission{$m->mission_id}");

//DETECT CACHED IMAGE, AND BAIL 
//help the browser by not re-downloading an unchanged image
if ($cached->TryClientCache()) die("<!-- client cache -->");
if ($cached->TryServerCache("renderKmlFromCache")) die("<!-- server cache -->");

//start an output buffer so we can save the output
ob_start();
include("kml_head.php");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";

echo " <Document>\n";


echo "  <name>NAV data</name>\n";
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
      and varname in ('NAV_X', 'NAV_Y', 'NAV_ALTITUDE')
    order by elapsed_time asc,
        varname asc
";


$rs = $db->query($q);

//still in the caching process
$points = array();
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    switch ($r->varname)
    {
        case "NAV_X":
            $x = $r->value;
            break;

        case "NAV_Y":
            $y = $r->value;
            break;

        case "NAV_ALTITUDE":
            $a = $r->value;
            break;
    }

    if (isset($a) && isset($x) && isset($y))
    {
        $data = xy2lonlat($lat, $lon, $er, $x, $y) . ",$a";
        $t    = $r->elapsed_time;
        $points["$t"] = $data;
	unset($a);
	unset($x);
	unset($y);
    }
}


//endpoints
echo "  <Folder>\n";
echo "   <name>Endpoints</name>\n";
echo "   <open>0</open>\n";

$keys  = array_keys($points);
$k_beg = $keys[0];
$k_end = $keys[count($keys) - 1]; 

//make the endpoints
echo "   <Placemark>\n";
echo "    <name>{$m->mission_id} Start</name>\n";
echo "    <styleUrl>#pathStartIcon</styleUrl>\n";
echo "    <Point>\n";
echo "     <coordinates>{$points[$k_beg]}</coordinates>\n";
echo "    </Point>\n";
echo "   </Placemark>\n";

echo "   <Placemark>\n";
echo "    <name>{$m->mission_id} End</name>\n";
echo "    <styleUrl>#pathEndIcon</styleUrl>\n";
echo "    <Point>\n";
echo "     <coordinates>{$points[$k_end]}</coordinates>\n";
echo "    </Point>\n";
echo "   </Placemark>\n";

echo "  </Folder>\n";


//make the nav line
echo "  <Placemark>\n";
echo "   <name>Path</name>\n";
echo "   <styleUrl>#pathDR</styleUrl>\n";
echo "   <LineString>\n";
echo "    <tessellate>1</tessellate>\n";
echo "    <extrude>1</extrude>\n";
echo "    <altitudeMode>relativeToSeaFloor</altitudeMode>\n";
echo "    <coordinates>\n";
foreach ($points as $t => $p)
{
    echo "     $p\n";
}
echo "    </coordinates>\n";
echo "   </LineString>\n";
echo "  </Placemark>\n";

/*
//make the timestamps
echo "  <Folder>\n";
echo "   <name>Animation</name>\n";
echo "   <open>0</open>\n";

foreach ($points as $t => $p)
{
    echo "   <Placemark>\n";
    echo "    <styleUrl>#auvIcon</styleUrl>\n";
    echo "    <TimeStamp>\n";
    echo "     <when>" . date('c', $t0 + $t) . "</when>\n";
    echo "    </TimeStamp>\n";
    echo "    <Point>\n";
    echo "     <coordinates>$p</coordinates>\n";
    echo "    </Point>\n";
    echo "   </Placemark>\n";
}
echo "  </Folder>\n";
*/

echo " </Document>\n";
echo "</kml>\n";


//cancel the output buffer, save the output
$data_to_be_cached = ob_get_clean();
//$compressed = gzencode($data_to_be_cached);
//file_put_contents($cached->CacheFile(), $compressed);
file_put_contents($cached->CacheFile(), $data_to_be_cached);


$cached->MakeCacheHeaders();
//header('Content-Encoding: gzip');
//echo $compressed;
echo $data_to_be_cached;

?>
