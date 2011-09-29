<?php 

$title = "Dashboard";
include("report_header.php"); 

?>

<h1>Odyssey IV Dashboard</h1>
<?php
    // all missions
    $q = "
        select date, 
            location, 
            vehicle_name, 
            max(mission_id) maxmission
        from mission
        group by date, location, vehicle_name
        order by maxmission desc
    ";

    $rs = $db->query($q);

    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        $thedate = date("l M j, Y", strtotime($r->date));

        $a = a("missiongroup.php",
                "group_id", "{$r->date},{$r->location},{$r->vehicle_name}");

        echo "<h2>$thedate - ";
        echo "<a href=\"$a\">{$r->vehicle_name} at {$r->location}</a></h2>\n";

        $where = array("date" => "= '{$r->date}'",
                        "location" => "= '{$r->location}'",
                        "vehicle_name" => "= '{$r->vehicle_name}'");
        $order = array("time" => "desc"); 

        echo "<ul class='missiongroup'x>\n";
        foreach ($dbo->mission->Records($where, $order) as $rec)
        {
            $a = a("mission.php",
                    "mission_id", $rec->mission_id);

            echo " <li>";
            echo "{$rec->time} - ";
            echo "<a href=\"$a\">{$rec->label}</a> ";
            echo "<span class='notes'>{$rec->notes}</span>";
            echo "</li>\n"; 
        }
        echo "</ul>\n";

    }


?>

<?php include("report_footer.php"); ?>
