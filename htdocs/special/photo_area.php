<?php 

//calculate the area surveyed by adding up the area in all the pictures taken.
// we use the viewing angle of the lens and the altitude info to estimate this.

$title = "Photographic Area";
include("report_header.php"); 

?>

<h1>Odyssey IV Photographic Survey Area</h1>
<?php


$q = "
    select mission.date, 
        mission.location, 
        mission.vehicle_name, 
        max(mission.mission_id) maxmission
    from mission
    inner join (
            select mission_id, count(*)
            from app_messages
            where varname='ICAMERA_SAVEDFILE'
            group by mission_id
        ) pics
        using (mission_id)
    group by date, location, vehicle_name
    order by maxmission desc
";

$rs = $db->query($q);

echo "<form action='?'>\n";

//print a box if we've submitted
if (isset($GET->submit))
{
    echo "<div style='overflow:auto;height:10em;padding:0px; "
         . "border-top:1px solid black;'>";
}

//loop over mission groups
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $thedate = date("l M j, Y", strtotime($r->date));
    $groupid = "{$r->date},{$r->location},{$r->vehicle_name}";

    $c = isset($_GET["missiongroup"]) && in_array($groupid, $_GET["missiongroup"]) 
        ? " checked='checked' " : "";

    $a = a("/missiongroup.php",
            "group_id", $groupid,
            "missiongroup", NULL,
            "mission", NULL);

    echo "<h2>";
    echo "<input type='checkbox' name='missiongroup[]' value='$groupid' "
         . "id='check_$groupid' $c />";
    echo "<label for='check_$groupid'>$thedate</label> - ";
    echo "<a href=\"$a\">{$r->vehicle_name} at {$r->location}</a></h2>\n";

    $where = array("date" => "= '{$r->date}'",
                    "location" => "= '{$r->location}'",
                    "vehicle_name" => "= '{$r->vehicle_name}'");
    $order = array("time" => "desc"); 

    //loop over missions in the group
    echo "<ul class='missiongroup'x>\n";
    foreach ($dbo->mission->Records($where, $order) as $rec)
    {
        $m = $rec->mission_id;
        //look up number of records
        $pics = $dbo->app_messages->varname->Count(
                        array("mission_id" => "=$m",
                              "varname" => "='ICAMERA_SAVEDFILE'"));

        $c = isset($_GET["mission"]) && in_array($m, $_GET["mission"]) 
            ? " checked='checked' " : "";

        if (0 < $pics)
        {
            $a = a("/mission.php",
                    "mission_id", $rec->mission_id,
                    "missiongroup", NULL,
                    "mission", NULL);

            echo " <li><input type='checkbox' name='mission[]' ";
            echo        " value='$m' id='chk_$m' $c />";
            echo "<label for='chk_$m'>{$rec->time}</label> - ";
            echo "<a href=\"$a\">{$rec->label}</a> ";
            echo "<span class='notes'>{$rec->notes}</span>";
            echo "</li>\n"; 
        }
    }
    echo "</ul>\n";

}


if (isset($GET->submit))
{
    echo "</div>";
}

echo "<br><br>";
echo "Viewing angle X and Y for the basler are 0.510472157 and 0.386512004";
echo "<br><br>";

echo "<table border='0'>\n";
echo align("Viewing Angle (X)", "viewangle_x", "radians");
echo align("Viewing Angle (Y)", "viewangle_y", "radians");
echo align("Max Altitude", "max_altitude", "meters");
echo "</table>\n";

echo "<br>\n";
echo "<br>\n";
echo "<input type='submit' name='submit' "
    . "value='Process Checked Missions and Groups' />";

echo "</form>\n";

if (!isset($GET->viewangle_x) 
    || !isset($GET->viewangle_y) 
    || !isset($GET->max_altitude))
{
    echo "<h3 style='color:red;'>Check your inputs</h3>";
    include("report_footer.php");
    die();
}


//combine missions and mission groups.  use array indices for uniqueness
$allmissions = array();
if (isset($_GET['missiongroup']))
{
    foreach (@$_GET['missiongroup'] as $mg)
    {
        foreach (getMissions($mg) as $m)
        {
            $allmissions[$m] = '';
        }
    }
}

if (isset($_GET['mission']))
{
    foreach (@$_GET['mission'] as $m)
    {
        $allmissions[$m] = '';
    }
}

$allmissions = array_keys($allmissions);
rsort($allmissions);

$total_area = 0;
echo "<table border='1' cellpadding='3'>\n";
echo " <tr><th>Mission</th><th>Area</th></tr>\n";
foreach ($allmissions as $m)
{
    $area = getArea($m, 
                $GET->viewangle_x->float,
                $GET->viewangle_y->float,
                $GET->max_altitude->float
                );
    $total_area += $area;
    echo " <tr>\n";
    echo "  <td>$m</td>\n";
    echo "  <td>$area</td>\n";
    echo " </tr>\n";
    flush();
}
echo " <tr><th>Total</th><th>$total_area</th></tr>\n";
echo "</table>";

//add up areas of all pictures taken below target altitude
function getArea($mission_id, $viewangle_x, $viewangle_y, $max_altitude)
{
    global $db;

    $q = "
        select * from (
            select elapsed_time, varname, value 
            from app_data 
            where mission_id = $mission_id 
              and varname='NAV_ALTITUDE' 
            union 
            select elapsed_time, varname, message value
            from app_messages 
            where mission_id = $mission_id
              and varname = 'ICAMERA_SAVEDFILE' 
         ) mytable 
         order by elapsed_time asc";

    $rs = $db->query($q);

    $alt = 6000;
    $area = 0;
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        if ("ICAMERA_SAVEDFILE" == $row->varname)
        {
            if ($alt <= $max_altitude)
            {
                $area += calculateArea($alt, $viewangle_x, $viewangle_y);
            }
        }
        else if ("NAV_ALTITUDE" == $row->varname)
        {
            $alt = $row->value;
        }
        
    }

    return $area;
}

function calculateArea($distance, $viewangle_x, $viewangle_y)
{
    $w = 2 * tan($viewangle_x / 2) * $distance;
    $h = 2 * tan($viewangle_y / 2) * $distance;
    return $w * $h;
}

function align($label, $name, $suffix)
{
    global $GET;

    $aval = "";
    if (isset($_GET[$name]))
    {
        $aval = $GET->__raw();
        $aval = $aval[$name];

        $aval = $aval->float;
    }


    $ret = "";
    $ret .= " <tr>\n";
    $ret .= "  <td>$label</td>\n";
    $ret .= "  <td><input type='text' name='$name' size='10' value='$aval' />";
    $ret .= " $suffix</td>\n";
    $ret .= " </tr>\n";
    return $ret; 
}

function getMissions($group)
{
    global $dbo;

    list($adate, $aplace, $avehicle) = explode(",", $group);

    return $dbo->mission->mission_id->Some(
        array('date' => "='$adate'",
            'location' => "='$aplace'",
            'vehicle_name' => "='$avehicle'"));
}


include("report_footer.php"); 

?>
