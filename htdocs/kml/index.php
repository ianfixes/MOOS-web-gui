<?php 

$title = "KML";
include("report_header.php"); 

//master list of available kml

?>

<h1>Odyssey IV KML</h1>
<?php

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

    echo "<h3><a href='kml_index.php?kml'>All Mission Groups</a> (kml)</h3>\n";

    echo "<ul>\n";
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        $thedate = date("l M j, Y", strtotime($r->date));

        $a = a("kml_group.php",
                "group_id", "{$r->date},{$r->location},{$r->vehicle_name}",
                "kml", "set");

        echo " <li>$thedate - ";
        echo "<a href=\"$a\">{$r->vehicle_name} at {$r->location}</a></li>\n";

    }


?>

<?php include("report_footer.php"); ?>
