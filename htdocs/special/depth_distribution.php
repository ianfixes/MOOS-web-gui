<?php 

//generate a histogram of vehicle depths

$title = "Depth Distribution";
include("report_header.php"); 

?>

<h1>Odyssey IV Depth Distribution</h1>
<?php

include("get_missions.php");


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

$depths = array();
echo "Processing missions: ";
foreach ($allmissions as $m)
{
    echo "$m, ";
    flush();

    $q = "
        select round(value)
        from app_data 
        where mission_id = $m
          and varname='NAV_DEPTH'
        ";

    $rs = $db->query($q);

    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ORDERED))
    {
        $d = $row[0];
    
        //correct errors like -0 and other negative depths
        $d = intval($d) <= 0 ? 0 : intval($d);
    
        if (isset($depths[$d]))
        {
            $depths[$d]++;
        }
        else
        {
            $depths[$d] = 1;
        }
    }
}

ksort($depths);

echo "<h2>Results</h2>\n";
echo "Depth, Number of datapoints<br />\n";
foreach ($depths as $d => $c)
{
    echo "$d, $c<br />\n";
}


include("report_footer.php"); 

?>
