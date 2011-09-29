<?php

require_once("utils/cGeodesy.php");

$ge = "GoogleEarth";

//make an attempt to serve plain text if we detect the operator trying to debug the output
if (isset($_GET["kml"]) || $ge == substr($_SERVER["HTTP_USER_AGENT"], 0, strlen($ge)))
{
    header("Content-Type: application/vnd.google-earth.kml+xml; charset=utf-8");
}
elseif (isset($_GET["xml"]))
{
    header("Content-Type: application/xml; charset=utf-8");
}
else
{
    header("Content-Type: text/plain; charset=utf-8");
}

function xy2lonlat($orig_lat, $orig_lon, $radius, $x, $y)
{
    list($lat, $lon) = cGeodesy::LocalGrid2LatLon($orig_lat, $orig_lon, $x, $y, $radius);

    return "$lon,$lat";
}



?>
