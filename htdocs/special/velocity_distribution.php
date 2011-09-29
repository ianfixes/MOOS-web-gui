<?php 

//histogram of vehicle velocities

$title = "Velocity Distribution";
include("report_header.php"); 

?>

<h1>Odyssey IV Velocity Distribution</h1>

<?php

include("get_missions.php");

$vels = array();
echo "Processing missions: ";
foreach ($allmissions as $m)
{
    echo "$m, ";
    flush();

    $q = "
        select round(value, 1)
        from app_data 
        where mission_id = $m
          and varname='DVL_BODY_VEL_Y'
        ";

    $rs = $db->query($q);

    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ORDERED))
    {
        $v = $row[0];
    
        //correct errors like -0 and other negative depths
        $d = intval($v) <= 0 ? 0 : intval($v);
    
        if (isset($vels[$v]))
        {
            $vels[$v]++;
        }
        else
        {
            $vels[$v] = 1;
        }
    }
}

ksort($vels);

echo "<h2>Results</h2>\n";
echo "Altitude, Number of datapoints<br />\n";
foreach ($vels as $v => $c)
{
    echo "$v, $c<br />\n";
}



include("report_footer.php"); 

?>
