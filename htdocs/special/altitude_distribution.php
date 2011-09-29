<?php 

//generate a histogram of vehicle altitudes

$title = "Altitude Distribution";
include("report_header.php"); 

?>

<h1>Odyssey IV Depth Distribution</h1>

<?php

include("get_missions.php");

$alts = array();
echo "Processing missions: ";
foreach ($allmissions as $m)
{
    echo "$m, ";
    flush();

    $q = "
        select round(value)
        from app_data 
        where mission_id = $m
          and varname='NAV_ALTITUDE'
        ";

    $rs = $db->query($q);

    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ORDERED))
    {
        $al = $row[0];
    
        //correct errors like -0 and other negative depths
        $d = intval($al) <= 0 ? 0 : intval($al);
    
        if (isset($alts[$al]))
        {
            $alts[$al]++;
        }
        else
        {
            $alts[$al] = 1;
        }
    }
}

ksort($alts);

echo "<h2>Results</h2>\n";
echo "Altitude, Number of datapoints<br />\n";
foreach ($alts as $al => $c)
{
    echo "$al, $c<br />\n";
}



include("report_footer.php"); 

?>
