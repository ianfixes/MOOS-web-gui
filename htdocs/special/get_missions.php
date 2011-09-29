<?php

//generate a list of missions with checkboxes.

// this page is included by other pages, not served directly


$q = "
    select mission.date, 
        mission.location, 
        mission.vehicle_name, 
        max(mission.mission_id) maxmission
    from mission
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

        $c = isset($_GET["mission"]) && in_array($m, $_GET["mission"]) 
            ? " checked='checked' " : "";

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
    echo "</ul>\n";

}


if (isset($GET->submit))
{
    echo "</div>";
}

echo "<br><br>";
echo "<input type='submit' name='submit' "
    . "value='Process Checked Missions and Groups' />";

echo "</form>\n";



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


function getMissions($group)
{
    global $dbo;

    list($adate, $aplace, $avehicle) = explode(",", $group);

    return $dbo->mission->mission_id->Some(
        array('date' => "='$adate'",
            'location' => "='$aplace'",
            'vehicle_name' => "='$avehicle'"));
}


?>
