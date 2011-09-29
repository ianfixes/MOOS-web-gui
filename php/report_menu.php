<?php

//make jump-to listbox
echo "Jump to: ";

$latestmission = $dbo->mission->mission_id->First(
    array(),
    array("mission_id" => "desc"));
echo "<a href='/latest.php'>Latest</a>";

echo "<form action='/mission.php' method='get'>\n";
$q = "
    select distinct date, location, vehicle_name
    from mission
    order by date desc
";

$rs = $db->query($q);

echo " <select name='mission_id' style='width:100px;'>\n";
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $thedate = date("l M j, Y", strtotime($r->date));

    $where = array("date" => "= '{$r->date}'",
                    "location" => "= '{$r->location}'",
                    "vehicle_name" => "= '{$r->vehicle_name}'");
    $order = array("time" => "desc"); 

    echo "  <optgroup label='$thedate - {$r->vehicle_name} at {$r->location}'>\n";
    foreach ($dbo->mission->Records($where, $order) as $rec)
    {
        if (isset($GET->mission_id) && $GET->mission_id->int == $rec->mission_id)
        {
            $s = ' selected="selected"';
        }
        else
        {
            $s = "";
        }

        echo "   <option value='{$rec->mission_id}' $s>";
        echo "{$rec->time} - {$rec->label}";
        echo "</option>\n"; 
    }
    echo "  </optgroup>\n";

}
echo " </select>\n";
echo "<input type='submit' value='Go' />";

echo "</form>";


//if we are in a mission group, make a menu for that too
if (isset($GET->group_id))
{
    $r = explode(",", $GET->group_id->string);

    $where = array("date" => "= '{$r[0]}'",
                    "location" => "= '{$r[1]}'",
                    "vehicle_name" => "= '{$r[2]}'");
    $order = array("time" => "desc"); 

    $a = a("missiongroup.php",
            "mission_id", NULL);

    echo "<br /><br />\n";
    echo "<a href='$a'>$r[0]</a><br />";
    echo "<a href='$a'>{$r[1]}</a>";
    echo "<div style='margin-left:-1em; font-size:smaller;'><ul>\n";
    foreach ($dbo->mission->Records($where, $order) as $rec)
    {
        $a = a("mission.php",
                "mission_id", $rec->mission_id);

        echo " <li>";
        echo "<a href=\"$a\" title='{$rec->time}'>{$rec->label}</a>";
        echo "</li>\n"; 
    }
    echo "</ul></div>\n";

}

?>
